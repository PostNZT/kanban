<?php

namespace App\Repository;

use App\Entity\Board;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    public function findByUuid(string $uuid): ?Board
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findWithColumnsAndCards(string $uuid): ?Board
    {
        return $this->createQueryBuilder('b')
            ->addSelect('c', 'card')
            ->leftJoin('b.columns', 'c')
            ->leftJoin('c.cards', 'card')
            ->where('b.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('card.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextColumnPosition(int $boardId): int
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT MAX(c.position) FROM App\Entity\BoardColumn c WHERE c.board = :boardId')
            ->setParameter('boardId', $boardId)
            ->getSingleScalarResult();

        return $result === null ? 0 : ((int) $result) + 1;
    }
}
