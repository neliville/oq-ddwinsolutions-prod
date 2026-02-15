<?php

namespace App\Tools\Share;

use App\Entity\EightDShareVisit;
use App\Repository\EightDShareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EightDShareController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route('/methode-8d/partage/{token}', name: 'app_eightd_share_view')]
    public function view(string $token, EightDShareRepository $shareRepository, Request $request): Response
    {
        $share = $shareRepository->findValidByToken($token);

        if (!$share) {
            throw $this->createNotFoundException('Lien de partage invalide ou expiré.');
        }

        $analysis = $share->getAnalysis();
        $content = json_decode($analysis->getData() ?? '[]', true) ?: [];

        $visit = new EightDShareVisit();
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

        return $this->render('share/share_placeholder.html.twig', [
            'share' => $share,
            'analysis' => $analysis,
            'analysisData' => $content,
            'tool' => [
                'slug' => 'eightd',
                'name' => 'Méthode 8D',
                'originRoute' => 'app_eightd_index',
            ],
            'show_public_navbar' => true,
        ]);
    }
}

