<?php

namespace App\Controller;

use App\Entity\IshikawaShareVisit;
use App\Repository\IshikawaShareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IshikawaShareController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route('/ishikawa/partage/{token}', name: 'app_ishikawa_share_view')]
    public function view(string $token, IshikawaShareRepository $shareRepository, Request $request): Response
    {
        $share = $shareRepository->findValidByToken($token);

        if (!$share) {
            throw $this->createNotFoundException('Lien de partage invalide ou expiré.');
        }

        $analysis = $share->getAnalysis();
        $contentData = json_decode($analysis->getData() ?? '[]', true) ?: [];

        $visit = new IshikawaShareVisit();
        $visit->setShare($share);
        $visit->setVisitedAt(new \DateTimeImmutable());
        $visit->setIpAddress($request->getClientIp());
        $visit->setUserAgent($request->headers->get('User-Agent'));
        $visit->setReferer($request->headers->get('Referer'));

        $session = $request->getSession();
        if ($session && $session->isStarted()) {
            $visit->setSessionId($session->getId());
        }

        $user = $this->security->getUser();
        if ($user instanceof \App\Entity\User) {
            $visit->setUser($user);
        }

        $this->entityManager->persist($visit);
        $this->entityManager->flush();

        return $this->render('ishikawa/share.html.twig', [
            'share' => $share,
            'analysis' => $analysis,
            'diagramData' => $contentData,
            'show_public_navbar' => true,
        ]);
    }
}


