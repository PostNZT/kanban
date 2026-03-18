<?php

namespace App\Tests\Unit\Security;

use App\Entity\Board;
use App\Entity\User;
use App\Security\BoardVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BoardVoterTest extends TestCase
{
    private BoardVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new BoardVoter();
    }

    public function testSupportsValidAttributes(): void
    {
        $board = new Board();
        $token = $this->createStub(TokenInterface::class);

        foreach ([BoardVoter::VIEW, BoardVoter::EDIT, BoardVoter::DELETE] as $attribute) {
            $result = $this->voter->vote($token, $board, [$attribute]);
            $this->assertNotSame(
                VoterInterface::ACCESS_ABSTAIN,
                $result,
                "Voter should not abstain for attribute $attribute with a Board subject"
            );
        }
    }

    public function testDoesNotSupportInvalidAttribute(): void
    {
        $board = new Board();
        $token = $this->createStub(TokenInterface::class);

        $result = $this->voter->vote($token, $board, ['SOME_OTHER']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportNonBoardSubject(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $result = $this->voter->vote($token, 'not-a-board', [BoardVoter::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessToOwner(): void
    {
        $owner = new User();
        $owner->setEmail('owner@example.com');
        $this->setEntityId($owner, 1);

        $board = new Board();
        $board->setTitle('My Board');
        $board->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($owner);

        $result = $this->voter->vote($token, $board, [BoardVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);

        $result = $this->voter->vote($token, $board, [BoardVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);

        $result = $this->voter->vote($token, $board, [BoardVoter::DELETE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessToNonOwner(): void
    {
        $owner = new User();
        $owner->setEmail('owner@example.com');
        $this->setEntityId($owner, 1);

        $otherUser = new User();
        $otherUser->setEmail('other@example.com');
        $this->setEntityId($otherUser, 2);

        $board = new Board();
        $board->setTitle('Owned Board');
        $board->setOwner($owner);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $board, [BoardVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);

        $result = $this->voter->vote($token, $board, [BoardVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);

        $result = $this->voter->vote($token, $board, [BoardVoter::DELETE]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessWhenNoUser(): void
    {
        $board = new Board();
        $board->setTitle('Some Board');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $board, [BoardVoter::VIEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Use reflection to set the private $id property on an entity.
     * This is necessary because Doctrine manages the ID via auto-generation
     * and no public setter is provided.
     */
    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
