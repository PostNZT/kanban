<?php

namespace App\Repository;

use App\Entity\BoardColumn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BoardColumn>
 */
class BoardColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardColumn::class);
    }

    public function findWithBoardAndOwner(int $id): ?BoardColumn
    {
        return $this->createQueryBuilder('col')
            ->addSelect('b', 'u')
            ->join('col.board', 'b')
            ->join('b.owner', 'u')
            ->where('col.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findWithBoardOwnerAndCards(int $id): ?BoardColumn
    {
        return $this->createQueryBuilder('col')
            ->addSelect('b', 'u', 'card')
            ->join('col.board', 'b')
            ->join('b.owner', 'u')
            ->leftJoin('col.cards', 'card')
            ->where('col.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextPosition(int $columnId): int
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT MAX(c.position) FROM App\Entity\Card c WHERE c.boardColumn = :colId')
            ->setParameter('colId', $columnId)
            ->getSingleScalarResult();

        return $result === null ? 0 : ((int) $result) + 1;
    }
}
