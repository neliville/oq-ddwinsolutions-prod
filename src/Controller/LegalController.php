<?php

namespace App\Controller;

use App\Repository\CmsPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    public function __construct(private readonly CmsPageRepository $cmsPageRepository)
    {
    }

    #[Route('/politique-de-confidentialite/', name: 'app_legal_politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->renderCmsPage('privacy-policy', [
            'title' => 'Politique de confidentialité',
            'description' => 'Découvrez comment OUTILS-QUALITÉ protège vos données personnelles et respecte la législation en vigueur.',
            'keywords' => 'politique de confidentialité, données personnelles, RGPD',
            'image' => 'img/mentions-legales.webp',
        ]);
    }

    #[Route('/conditions-utilisation/', name: 'app_legal_conditions_utilisation')]
    public function conditionsUtilisation(): Response
    {
        return $this->renderCmsPage('terms-of-use', [
            'title' => 'Conditions d\'utilisation',
            'description' => 'Conditions générales d\'utilisation des outils et services proposés par OUTILS-QUALITÉ.',
            'keywords' => 'conditions d\'utilisation, CGU, règles',
            'image' => 'img/mentions-legales.webp',
        ]);
    }

    #[Route('/mentions-legales/', name: 'app_legal_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->renderCmsPage('legal-notice', [
            'title' => 'Mentions légales',
            'description' => 'Mentions légales d\'OUTILS-QUALITÉ : éditeur du site, hébergeur et informations légales.',
            'keywords' => 'mentions légales, éditeur, hébergeur',
            'image' => 'img/mentions-legales.webp',
        ]);
    }

    private function renderCmsPage(string $slug, array $meta): Response
    {
        $cmsPage = $this->cmsPageRepository->findOneBySlug($slug);

        if (!$cmsPage) {
            throw $this->createNotFoundException(sprintf('La page "%s" est introuvable.', $slug));
        }

        return $this->render('legal/page.html.twig', [
            'page' => $cmsPage,
            'meta' => $meta,
        ]);
    }
}
