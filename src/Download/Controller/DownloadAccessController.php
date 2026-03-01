<?php

declare(strict_types=1);

namespace App\Download\Controller;

use App\Download\Service\DownloadAccessService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/download')]
final class DownloadAccessController extends AbstractController
{
    public function __construct(
        private readonly DownloadAccessService $downloadAccessService,
    ) {
    }

    #[Route('/access/{token}', name: 'app_download_access', methods: ['GET'])]
    public function access(string $token): Response
    {
        return $this->downloadAccessService->validateAndStream($token);
    }
}
