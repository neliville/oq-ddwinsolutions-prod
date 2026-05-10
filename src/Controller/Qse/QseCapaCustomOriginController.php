<?php

declare(strict_types=1);

namespace App\Controller\Qse;

use App\Entity\Qse\CapaOrigin;
use App\Entity\User;
use App\Qse\Enum\CapaOriginKind;
use App\Repository\Qse\CapaOriginRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/qse/capa/origine', name: 'app_qse_capa_origin_')]
#[IsGranted('ROLE_USER')]
final class QseCapaCustomOriginController extends AbstractController
{
    public function __construct(
        private readonly CapaOriginRepository $capaOriginRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('qse_capa_origin_new', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $name = trim($request->request->getString('name'));
        if ($name === '') {
            $this->addFlash('danger', 'Le nom de l’origine est obligatoire.');

            return $this->redirectToRefererOrCapa($request);
        }

        $base = (string) $this->slugger->slug($name)->lower();
        if ($base === '') {
            $base = 'origine';
        }
        $slug = $base;
        $i = 2;
        while ($this->capaOriginRepository->slugExistsGlobally($slug)) {
            $slug = $base . '-' . $i;
            ++$i;
        }

        $o = new CapaOrigin();
        $o->setName($name);
        $o->setSlug($slug);
        $o->setKind(CapaOriginKind::CUSTOM);
        $o->setOwner($user);
        $o->setActive(true);
        $this->entityManager->persist($o);
        $this->entityManager->flush();
        $this->addFlash('success', 'Origine personnalisée enregistrée.');

        return $this->redirectToRefererOrCapa($request);
    }

    private function redirectToRefererOrCapa(Request $request): Response
    {
        $ref = $request->headers->get('Referer');
        if (\is_string($ref) && str_contains($ref, '/dashboard/qse/capa/')) {
            return $this->redirect($ref);
        }

        return $this->redirectToRoute('app_qse_capa_index');
    }
}
