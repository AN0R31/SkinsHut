<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CoinflipController extends AbstractController
{
    public function denyAccessUnlessLoggedIn(): Response|null
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_landing');
        }
        return null;
    }

    #[Route(path: 'user/getBalance/{user_id}', name: 'get_user_balance', methods: 'POST')]
    public function get_user_balance(int $user_id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);

        $balance = $user->getBalance();

        return new JsonResponse([
            'status' => true,
            'balance' => $balance,
        ]);
    }

    #[Route(path: '/coinflip', name: 'coinflip')]
    public function coinflip_render(): Response|null
    {
        $this->denyAccessUnlessLoggedIn();

        return $this->render('bet/coinflip.html.twig', []);
    }

    #[Route(path: 'coinflip/create/{user_id}/{amount}/{side}', name: 'coinflip_create', methods: 'POST')]
    public function coinflip_create(int $user_id, float $amount, string $side, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);

        $activeGame = $entityManager->getRepository(Game::class)->findOneBy([
            'Player1' => $user,
            'completedAt' => null,
        ]);
        if ($activeGame !== null) {
            return new JsonResponse([
                'status' => false,
                'error' => 'You already have an ongoing battle!'
            ]);
        } else {
            $activeGame = $entityManager->getRepository(Game::class)->findOneBy([
                'Player2' => $user,
                'completedAt' => null,
            ]);
            if ($activeGame !== null) {
                return new JsonResponse([
                    'status' => false,
                    'error' => 'You already have an ongoing battle!'
                ]);
            }
        }

        $game = new Game();
        $game->setPlayer1($user);
        $game->setCreatedAt(new \DateTimeImmutable());
        $game->setValue($amount);
        $game->setPlayer1Bet($side);
        $game->setType('COINFLIP');
        $game->setStatus(1);

        $user->setBalance($user->getBalance() - $game->getValue());

        $entityManager->persist($game);
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'status' => true,
        ]);
    }

    #[Route(path: 'coinflip/join/{user_id}/{joiner_id}', name: 'coinflip_join', methods: 'POST')]
    public function coinflip_join_as_bot(int $user_id, int $joiner_id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $user = $entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);

        if ($joiner_id === 0) {
            $joiner = null;
        } else {
            $joiner = $entityManager->getRepository(User::class)->findOneBy(['id' => $joiner_id]);
        }

        $game = $entityManager->getRepository(Game::class)->findOneBy([
            'Player1' => $user,
            'completedAt' => null,
        ]);

        $game->setPlayer2($joiner);
        $game->setFilledAt(new \DateTimeImmutable());
        if ($game->getPlayer1Bet() === 'CT') {
            $game->setPlayer2Bet('T');
        } else {
            $game->setPlayer2Bet('CT');
        }

        //GAMBLE PART FOR NO BOT
//        if ($joiner === null) {
//            if ($game->getPlayer1Bet() === 'CT') {
//                $result = "T";
//            } else {
//                $result = "CT";
//            }
//            //GAMBLE PART FOR NORMAL PvP
//        } else {
//            $result = rand(0, 100);
//            if ($result % 2 === 0) {
//                $result = "T";
//            } else {
//                $result = "CT";
//            }
//        }
        $result = rand(0, 100);
        if ($result % 2 === 0) {
            $result = "T";
        } else {
            $result = "CT";
        }

        if ($result === 'CT') {
            if ($game->getPlayer1Bet() === 'CT') {
                $winner = 1;
                $winnerName = $game->getPlayer1()->getUsername();
            } else {
                $winner = 2;
                $winnerName = $game->getPlayer2() === null ? 'BOT' : $game->getPlayer2()->getUsername();
            }
        } else {
            if ($game->getPlayer1Bet() === 'CT') {
                $winner = 2;
                $winnerName = $game->getPlayer2() === null ? 'BOT' : $game->getPlayer2()->getUsername();
            } else {
                $winner = 1;
                $winnerName = $game->getPlayer1()->getUsername();
            }
        }

        $joiner?->setBalance($joiner->getBalance() - $game->getValue());

        $game->setWinner($winner);
        $game->setStatus(2);

        $entityManager->persist($game);
        $entityManager->flush();

        return new JsonResponse([
            'status' => true,
            'result' => $result,
            'winner' => $winnerName,
            'game_id' => $game->getId(),
        ]);
    }

    #[Route(path: 'game/getLatest', methods: 'POST')]
    public function coinflip_get_latest(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $games = $entityManager->getRepository(Game::class)->findBy([], [
            'id' => 'DESC'
        ]);

        $players1 = [];
        $players2 = [];
        $winners = [];
        $eta = [];
        $gameIds = [];
        $values = [];
        $types = [];
        $key = 1;
        for ($i = 0; $i <= 4; $i++) {
            $players1[$key] = $games[$i]->getPlayer1()->getUsername();
            $types[$key] = $games[$i]->getType();
            $winners[$key] = $games[$i]->getWinner();
            $gameIds[$key] = $games[$i]->getId();
            $values[$key] = $games[$i]->getValue();
            if ($games[$i]->getCompletedAt() === null) {
                if ($games[$i]->getFilledAt() === null) {
                    $players2[$key] = 'Waiting for opponent...';
                    $eta[$key] = 1;
                } else {
                    if ($games[$i]->getPlayer2() === null) {
                        $players2[$key] = 'GABMLE BOT';
                    } else {
                        $players2[$key] = $games[$i]->getPlayer2()->getUsername();
                    }
                    $eta[$key] = 2;
                }
            } else {
                if ($games[$i]->getPlayer2() === null) {
                    $players2[$key] = 'GABMLE BOT';
                } else {
                    $players2[$key] = $games[$i]->getPlayer2()->getUsername();
                }
                $eta[$key] = 3;
            }
            $key++;
        }

        return new JsonResponse([
            'status' => true,
            'players1' => $players1,
            'players2' => $players2,
            'winners' => $winners,
            'types' => $types,
            'eta' => $eta,
            'gameIds' => $gameIds,
            'values' => $values,
        ]);
    }

    #[Route(path: 'coinflip/complete/{game_id}', methods: 'POST')]
    public function coinflip_complete(int $game_id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $game_id]);
        $game->setStatus(3);
        $game->setCompletedAt(new \DateTimeImmutable());

        $commission = ($game->getValue() / 100) * 5;
        $winValue = ($game->getValue() * 2) - $commission;

        if ($game->getWinner() === 1) {
            $game->getPlayer1()->setBalance($game->getPlayer1()->getBalance() + $winValue);
        } else {
            if ($game->getPlayer2() !== null) {
                $game->getPlayer2()->setBalance($game->getPlayer2()->getBalance() + $winValue);
            }
        }

        $entityManager->persist($game->getPlayer1());
        if ($game->getPlayer2() !== null) {
            $entityManager->persist($game->getPlayer2());
        }
        $entityManager->persist($game);
        $entityManager->flush();

        return new JsonResponse([
            'status' => true,
        ]);
    }

    #[Route(path: 'coinflip/getDetails', methods: 'POST')]
    public function coinflip_get_details(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $game = $entityManager->getRepository(Game::class)->findOneBy([
            'Player1' => $this->getUser(),
            'completedAt' => null,
        ]);

        if ($game->getPlayer2() === null) {
            return new JsonResponse([
                'status' => false,
            ]);
        } else {
            if ($game->getWinner() === 1) {
                $winnerName = $game->getPlayer1()->getUsername();
                $result = $game->getPlayer1Bet();
            } else {
                $winnerName = $game->getPlayer2()->getUsername();
                $result = $game->getPlayer2Bet();
            }
            return new JsonResponse([
                'status' => true,
                'result' => $result,
                'winner' => $winnerName,
                'gameId' => $game->getId(),
            ]);
        }
    }

    #[Route(path: 'coinflip/join/{game_id}')]
    public function coinflip_join(int $game_id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $game = $entityManager->getRepository(Game::class)->findOneBy(['id' => $game_id]);

        $player1 = $game->getPlayer1();
        $player2 = $this->getUser();

        $game->setPlayer2($this->getUser());
        $game->setStatus(2);
        $game->setFilledAt(new \DateTimeImmutable());
        if ($game->getPlayer1Bet() === 'CT') {
            $game->setPlayer2Bet('T');
        } else {
            $game->setPlayer2Bet('CT');
        }

        $result = rand(0, 100);
        if ($result % 2 === 0) {
            $result = "T";
        } else {
            $result = "CT";
        }

        if ($result === 'CT') {
            if ($game->getPlayer1Bet() === 'CT') {
                $winner = 1;
                $winnerName = $player1->getUsername();
            } else {
                $winner = 2;
                $winnerName = $player2->getUsername();
            }
        } else {
            if ($game->getPlayer1Bet() === 'CT') {
                $winner = 2;
                $winnerName = $player2->getUsername();
            } else {
                $winner = 1;
                $winnerName = $player1->getUsername();
            }
        }

        $player2->setBalance($player2->getBalance() - $game->getValue());

        $game->setWinner($winner);
        $game->setStatus(2);


        $entityManager->persist($game);
        $entityManager->persist($player1);
        $entityManager->persist($player2);
        $entityManager->flush();

        return $this->render('bet/coinflip_join.html.twig', [
            'result' => $result,
            'winner' => $winnerName,
        ]);
    }
}
