<?php

namespace App\Site\Controller;

use App\Repository\HomepageTestimonialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly HomepageTestimonialRepository $homepageTestimonialRepository,
    ) {
    }

    #[Route('/', name: 'app_home_index')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'homepageTestimonials' => $this->homepageTestimonialRepository->findActiveForHomepage(2),
        ]);
    }
}
