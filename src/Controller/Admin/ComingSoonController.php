<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
final class ComingSoonController extends AbstractController
{
    #[Route('/a-venir/{topic}', name: 'coming_soon', requirements: ['topic' => '[a-z0-9-]+'], methods: ['GET'])]
    public function topic(string $topic): Response
    {
        return $this->render('admin/coming_soon.html.twig', [
            'topic' => $topic,
        ]);
    }
}
