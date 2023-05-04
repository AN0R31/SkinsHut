<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Roulette;
use App\Entity\User;
use App\Entity\UserRouletteGame;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RouletteController extends AbstractController
{
    public function denyAccessUnlessLoggedIn(): Response|null
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }
        return null;
    }

    public function try_roulette_create_or_get_latest(EntityManagerInterface $entityManager): int
    {
        $ongoingRoulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'completedAt' => null,
        ]);

        if ($ongoingRoulette !== null) {
            return $ongoingRoulette->getId();
        }

        $roulette = new Roulette();
        $roulette->setCreatedAt(new \DateTimeImmutable());
        $roulette->setIncome(0);
        $roulette->setOutcome(0);
        $entityManager->persist($roulette);
        $entityManager->flush();

        return $roulette->getId();
    }

    #[Route(path: '/roulette', name: 'roulette')]
    public function roulette_render(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'id' => $rouletteId,
        ]);

        return $this->render('bet/roulette.html.twig', [
            'roulette' => $roulette,
        ]);
    }

    #[Route(path: '/roulette/start', methods: 'POST')]
    public function roulette_start(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'completedAt' => null,
        ]);

        if ($roulette->getWinner() !== null) {
            return new JsonResponse([
                'status' => false,
                'winner' => $roulette->getWinner(),
            ]);
        }

        $winner = rand(0, 14);

        if ($winner === 0) {
            $bets = $entityManager->getRepository(UserRouletteGame::class)->findBy([
                'roulette' => $roulette,
                'side' => 'GREEN'
            ]);
            $multiplier = 14;
        } elseif ($winner < 8) {
            $bets = $entityManager->getRepository(UserRouletteGame::class)->findBy([
                'roulette' => $roulette,
                'side' => 'RED'
            ]);
            $multiplier = 2;
        } else {
            $bets = $entityManager->getRepository(UserRouletteGame::class)->findBy([
                'roulette' => $roulette,
                'side' => 'BLACK'
            ]);
            $multiplier = 2;
        }

        foreach ($bets as $bet) {
            $betAmount = $bet->getValue();
            $toPay = ($betAmount * $multiplier);
            $commission = ($toPay / 100) * 5;
            $toPay -= $commission;
            $user = $bet->getUser();
            $user->setBalance($user->getBalance() + $toPay);
            $bet->setIsWin(1);
            $entityManager->persist($bet);
            $entityManager->persist($user);
        }

        $roulette->setFilledAt(new \DateTimeImmutable());
        $roulette->setWinner($winner);
        $entityManager->persist($roulette);
        $entityManager->flush();

        return new JsonResponse([
            'status' => true,
            'winner' => $winner,
        ]);
    }

    #[Route(path: '/roulette/complete', methods: 'POST')]
    public function roulette_complete(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'id' => $rouletteId,
        ]);

        if ($roulette->getCompletedAt() === null && $roulette->getFilledAt() !== null) {
            $roulette->setCompletedAt(new \DateTimeImmutable());

            $bets = $entityManager->getRepository(UserRouletteGame::class)->findBy([
                'roulette' => $roulette,
            ]);
            foreach ($bets as $bet) {
                $roulette->setIncome($roulette->getIncome() + $bet->getValue());
                if ($bet->getIsWin() === true) {
                    if ($bet->getSide() === 'GREEN') {
                        $roulette->setOutcome($roulette->getOutcome() + ($bet->getValue() * 14));
                    } else {
                        $roulette->setOutcome($roulette->getOutcome() + ($bet->getValue() * 2));
                    }
                }
            }

            $entityManager->persist($roulette);
            $entityManager->flush();
        }

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'id' => $rouletteId,
        ]);

        return new JsonResponse([
            'status' => true,
            'createdAt' => $roulette->getCreatedAt()->format('i:s'),
        ]);
    }

    #[Route(path: '/roulette/getLatest', methods: 'POST')]
    public function roulette_get_latest(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $lastResults = [];
        $greens = 0;
        $reds = 0;
        $blacks = 0;
        $key = 1;
        for ($i = $rouletteId-1; $i >= $rouletteId-6; $i--) {
            $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
                'id' => $i,
            ]);
            $lastResults[$key] = $roulette->getWinner();
            $key++;
        }

        for ($i = $rouletteId-1; $i >= $rouletteId-101; $i--) {
            $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
                'id' => $i,
            ]);
            $winner = $roulette->getWinner();
            if ($winner === 0) {
                $greens++;
            } elseif ($winner < 8) {
                $reds++;
            } else {
                $blacks++;
            }
        }

        return new JsonResponse([
            'status' => true,
            'lastResults' => $lastResults,
            'greens' => $greens,
            'reds' => $reds,
            'blacks' => $blacks,
        ]);
    }

    #[Route(path: '/roulette/join/{amount}/{side}', methods: 'POST')]
    public function roulette_join(float $amount, int $side, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $user = $this->getUser();
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'id' => $user->getId(),
        ]);

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'id' => $rouletteId,
        ]);

        if ($side === 0) {
            $sideAsString = 'GREEN';
        } elseif ($side === 1) {
            $sideAsString = 'RED';
        } else {
            $sideAsString = 'BLACK';
        }


        $rouletteUserGame = new UserRouletteGame();
        $rouletteUserGame->setUser($user);
        $rouletteUserGame->setRoulette($roulette);
        $rouletteUserGame->setCreatedAt(new \DateTimeImmutable());
        $rouletteUserGame->setValue($amount);
        $rouletteUserGame->setSide($sideAsString);
        $rouletteUserGame->setIsWin(0);
        $user->setBalance($user->getBalance() - $amount);

        $entityManager->persist($user);
        $entityManager->persist($rouletteUserGame);
        $entityManager->flush();

        return new JsonResponse([
            'status' => true,
        ]);
    }

    #[Route(path: '/roulette/getUserBets', methods: 'POST')]
    public function roulette_get_user_bets(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $rouletteId = $this->try_roulette_create_or_get_latest($entityManager);
        $roulette = $entityManager->getRepository(Roulette::class)->findOneBy([
            'id' => $rouletteId,
        ]);

        $bets = $entityManager->getRepository(UserRouletteGame::class)->findBy([
            'user' => $this->getUser(),
            'roulette' => $roulette,
        ]);

        $side = [];
        $value = [];
        $isWin = [];
        $key = 1;
        foreach ($bets as $bet) {
            $side[$key] = $bet->getSide();
            $value[$key] = $bet->getValue();
            if ($roulette->getCompletedAt() === null) {
                $isWin[$key] = null;
            } else {
                $isWin[$key] = $bet->getIsWin();
            }
            $key++;
        }
        $key--;

        return new JsonResponse([
            'status' => true,
            'side' => $side,
            'value' => $value,
            'isWin' => $isWin,
            'size' => $key,
        ]);
    }
}
