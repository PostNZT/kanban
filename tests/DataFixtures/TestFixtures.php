<?php

namespace App\Tests\DataFixtures;

use App\Entity\Board;
use App\Entity\BoardColumn;
use App\Entity\Card;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // User 1
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
        $manager->persist($user1);

        // User 2
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));
        $manager->persist($user2);

        // Board for User 1
        $board = new Board();
        $board->setTitle('Test Board');
        $board->setOwner($user1);

        $col1 = new BoardColumn();
        $col1->setTitle('To Do');
        $col1->setPosition(0);
        $board->addColumn($col1);

        $col2 = new BoardColumn();
        $col2->setTitle('In Progress');
        $col2->setPosition(1);
        $board->addColumn($col2);

        $col3 = new BoardColumn();
        $col3->setTitle('Done');
        $col3->setPosition(2);
        $board->addColumn($col3);

        // Cards in "To Do"
        $card1 = new Card();
        $card1->setTitle('Task 1');
        $card1->setDescription('First task');
        $card1->setPosition(0);
        $col1->addCard($card1);

        $card2 = new Card();
        $card2->setTitle('Task 2');
        $card2->setPosition(1);
        $col1->addCard($card2);

        // Card in "In Progress"
        $card3 = new Card();
        $card3->setTitle('Task 3');
        $card3->setPosition(0);
        $col2->addCard($card3);

        $manager->persist($board);

        // Board for User 2
        $board2 = new Board();
        $board2->setTitle('Other Board');
        $board2->setOwner($user2);

        $otherCol = new BoardColumn();
        $otherCol->setTitle('Backlog');
        $otherCol->setPosition(0);
        $board2->addColumn($otherCol);

        $manager->persist($board2);

        $manager->flush();

        $this->addReference('user1', $user1);
        $this->addReference('user2', $user2);
        $this->addReference('board1', $board);
        $this->addReference('board2', $board2);
        $this->addReference('col1', $col1);
        $this->addReference('col2', $col2);
        $this->addReference('col3', $col3);
        $this->addReference('card1', $card1);
        $this->addReference('card2', $card2);
        $this->addReference('card3', $card3);
    }
}
