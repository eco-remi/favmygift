<?php

namespace App\Tests;

use App\Entity\FriendRequest;
use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VerificationTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testUserCreation()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $savedUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        $this->assertNotNull($savedUser);
        $this->assertEquals('test@example.com', $savedUser->getEmail());
    }

    public function testGiftCreation()
    {
        $user = new User();
        $user->setEmail('giftowner@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $gift = new Gift();
        $gift->setName('Test Gift');
        $gift->setOwner($user);
        $this->entityManager->persist($gift);
        $this->entityManager->flush();

        $savedGift = $this->entityManager->getRepository(Gift::class)->findOneBy(['name' => 'Test Gift']);
        $this->assertNotNull($savedGift);
        $this->assertEquals($user->getId(), $savedGift->getOwner()->getId());
    }

    public function testFriendRequestFlow()
    {
        $u1 = new User();
        $u1->setEmail('u1@example.com');
        $u1->setPassword('p');

        $u2 = new User();
        $u2->setEmail('u2@example.com');
        $u2->setPassword('p');

        $this->entityManager->persist($u1);
        $this->entityManager->persist($u2);

        $req = new FriendRequest();
        $req->setRequester($u1);
        $req->setReceiver($u2);
        $this->entityManager->persist($req);
        $this->entityManager->flush();

        $this->assertEquals(FriendRequest::STATUS_PENDING, $req->getStatus());

        $req->setStatus(FriendRequest::STATUS_ACCEPTED);
        $this->entityManager->flush();

        $this->assertEquals(FriendRequest::STATUS_ACCEPTED, $req->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Cleanup not strictly necessary if using dedicated test db, but good practice if not using transaction rollback
    }
}
