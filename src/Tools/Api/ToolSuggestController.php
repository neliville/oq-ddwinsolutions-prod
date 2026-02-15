<?php

namespace App\Tools\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Point d'entrée IA (stub) : suggestions par outil.
 * POST /api/tools/{tool}/suggest avec payload optionnel.
 */
#[Route('/api/tools')]
class ToolSuggestController extends AbstractController
{
    private const ALLOWED_TOOLS = ['ishikawa', 'fivewhy', 'amdec', 'pareto', 'qqoqccp', 'eightd'];

    #[Route('/{tool}/suggest', name: 'app_api_tools_suggest', methods: ['POST'])]
    public function suggest(string $tool, Request $request): JsonResponse
    {
        $tool = strtolower($tool);
        if (!\in_array($tool, self::ALLOWED_TOOLS, true)) {
            return new JsonResponse(['error' => 'Outil inconnu'], Response::HTTP_BAD_REQUEST);
        }

        $payload = [];
        try {
            $content = $request->getContent();
            if ($content !== '' && $content !== '0') {
                $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR) ?? [];
            }
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'tool' => $tool,
            'suggestions' => [],
            'message' => 'Point d\'entrée IA (stub) – à brancher sur un service IA.',
        ]);
    }
}
