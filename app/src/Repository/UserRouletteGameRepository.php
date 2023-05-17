<?php

namespace App\Repository;

use App\Entity\UserRouletteGame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRouletteGame>
 *
 * @method UserRouletteGame|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRouletteGame|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRouletteGame[]    findAll()
 * @method UserRouletteGame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRouletteGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRouletteGame::class);
    }

    public function save(UserRouletteGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserRouletteGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return UserRouletteGame[] Returns an array of UserRouletteGame objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserRouletteGame
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
