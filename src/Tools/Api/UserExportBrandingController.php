<?php

declare(strict_types=1);

namespace App\Tools\Api;

use App\Entity\User;
use App\Service\Export\ExportBrandingResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user', name: 'api_user_')]
final class UserExportBrandingController extends AbstractController
{
    public function __construct(
        private readonly ExportBrandingResolver $exportBrandingResolver,
    ) {
    }

    /**
     * Métadonnées d’en-tête / pied de page pour exports PDF côté client (jsPDF, etc.).
     */
    #[Route('/export-branding', name: 'export_branding', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Connectez-vous pour récupérer les paramètres d’export.')]
    public function exportBranding(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'not_authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $view = $this->exportBrandingResolver->resolveForUser($user);

        return new JsonResponse($view->toApiArray());
    }
}
