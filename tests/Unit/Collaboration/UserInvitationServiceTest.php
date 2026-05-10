<?php

declare(strict_types=1);

namespace App\Tests\Unit\Collaboration;

use App\Collaboration\CollaborationToken;
use App\Collaboration\InvitationStatus;
use App\Collaboration\UserInvitationService;
use App\Entity\User;
use App\Entity\UserInvitation;
use App\Repository\UserInvitationRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserInvitationServiceTest extends TestCase
{
    public function testFindPendingByPlainTokenExpiresInvitationAndReturnsNull(): void
    {
        $inv = new UserInvitation();
        $inv->setEmail('late@example.com');
        $inv->setStatus(InvitationStatus::ENVOYEE);
        $inv->setExpiresAt(new \DateTimeImmutable('-3 days'));
        $inv->setTokenHash(CollaborationToken::hashPlain('tok'));

        $repo = $this->createMock(UserInvitationRepository::class);
        $repo->expects($this->once())->method('findPendingByPlainToken')->with('tok')->willReturn($inv);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $mailer = $this->createStub(MailerService::class);
        $url = $this->createStub(UrlGeneratorInterface::class);

        $svc = new UserInvitationService($em, $repo, $mailer, $url, 14);

        self::assertNull($svc->findPendingByPlainToken('tok'));
        self::assertSame(InvitationStatus::EXPIREE, $inv->getStatus());
    }

    public function testTryAcceptPersistsWhenEmailMatches(): void
    {
        $inv = new UserInvitation();
        $inv->setEmail('match@example.com');
        $inv->setStatus(InvitationStatus::ENVOYEE);
        $inv->setExpiresAt(new \DateTimeImmutable('+10 days'));
        $inv->setTokenHash(CollaborationToken::hashPlain('plain-ok'));

        $repo = $this->createMock(UserInvitationRepository::class);
        $repo->expects($this->once())->method('findPendingByPlainToken')->with('plain-ok')->willReturn($inv);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->once())->method('sendInvitationAcceptedToOwnerEmail')->with($inv);

        $url = $this->createStub(UrlGeneratorInterface::class);

        $svc = new UserInvitationService($em, $repo, $mailer, $url, 14);

        $user = new User();
        $user->setEmail('match@example.com');
        $user->setPassword('x');
        $user->setRoles(['ROLE_USER']);

        self::assertTrue($svc->tryAccept($user, 'plain-ok'));
        self::assertSame(InvitationStatus::ACCEPTEE, $inv->getStatus());
        self::assertSame($user, $inv->getAcceptedBy());
    }

    public function testTryAcceptReturnsFalseWhenEmailMismatch(): void
    {
        $inv = new UserInvitation();
        $inv->setEmail('invited@example.com');
        $inv->setStatus(InvitationStatus::ENVOYEE);
        $inv->setExpiresAt(new \DateTimeImmutable('+10 days'));
        $inv->setTokenHash(CollaborationToken::hashPlain('p'));

        $repo = $this->createMock(UserInvitationRepository::class);
        $repo->method('findPendingByPlainToken')->willReturn($inv);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects($this->never())->method('sendInvitationAcceptedToOwnerEmail');

        $url = $this->createStub(UrlGeneratorInterface::class);

        $svc = new UserInvitationService($em, $repo, $mailer, $url, 14);

        $user = new User();
        $user->setEmail('other@example.com');
        $user->setPassword('x');
        $user->setRoles(['ROLE_USER']);

        self::assertFalse($svc->tryAccept($user, 'p'));
    }
}
