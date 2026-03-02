<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Téléchargement sécurisé par token HMAC (GET /ressources/download).
 * Accessible sans authentification (route app_download).
 */
final class DownloadController extends AbstractController
{
    public function __construct(
        private readonly string $downloadSecret,
        private readonly string $downloadBasePath,
    ) {
    }

    #[Route('/ressources/download', name: 'app_download', methods: ['GET'])]
    public function download(Request $request): Response
    {
        $file = $request->query->get('file', '');
        $expires = $request->query->get('expires', '');
        $token = $request->query->get('token', '');

        if ('' === $file || '' === $expires || '' === $token) {
            return $this->errorResponse('Paramètres manquants.', Response::HTTP_FORBIDDEN);
        }

        $expiresInt = (int) $expires;
        if ($expiresInt <= 0 || time() >= $expiresInt) {
            return $this->errorResponse('Lien expiré.', Response::HTTP_FORBIDDEN);
        }

        $expectedToken = hash_hmac('sha256', $file . $expires, $this->downloadSecret);
        if (!hash_equals($expectedToken, $token)) {
            return $this->errorResponse('Lien invalide.', Response::HTTP_FORBIDDEN);
        }

        $basePath = realpath($this->downloadBasePath);
        if (false === $basePath || !is_dir($basePath)) {
            return $this->errorResponse('Configuration invalide.', Response::HTTP_FORBIDDEN);
        }

        $safeName = basename($file);
        if ('' === $safeName || $safeName !== $file) {
            return $this->errorResponse('Nom de fichier invalide.', Response::HTTP_FORBIDDEN);
        }

        $fullPath = $basePath . \DIRECTORY_SEPARATOR . $safeName;
        $resolved = realpath($fullPath);
        if (false === $resolved || !is_file($resolved)) {
            return $this->errorResponse('Fichier introuvable.', Response::HTTP_FORBIDDEN);
        }

        $basePrefix = $basePath . \DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $basePrefix)) {
            return $this->errorResponse('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        $response = new BinaryFileResponse($resolved);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $safeName
        );

        return $response;
    }

    private function errorResponse(string $message, int $status = Response::HTTP_FORBIDDEN): Response
    {
        return $this->render('download/error.html.twig', [
            'message' => $message,
        ], new Response('', $status));
    }
}
