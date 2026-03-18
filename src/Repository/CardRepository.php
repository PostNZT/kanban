<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findWithColumnBoardAndOwner(int $id): ?Card
    {
        return $this->createQueryBuilder('card')
            ->addSelect('col', 'b', 'u')
            ->join('card.boardColumn', 'col')
            ->join('col.board', 'b')
            ->join('b.owner', 'u')
            ->where('card.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
