<?php

namespace App\Site\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home_index')]
    public function index(Request $request): Response
    {
        $subscriber = new NewsletterSubscriber();
        $newsletterForm = $this->createForm(NewsletterFormType::class, $subscriber);

        return $this->render('home/index.html.twig', [
            'newsletterForm' => $newsletterForm,
        ]);
    }
}
