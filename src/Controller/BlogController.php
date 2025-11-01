<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog_index')]
    public function index(): Response
    {
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterFormType::class, $subscriber);

        return $this->render('blog/index.html.twig', [
            'newsletterForm' => $form,
        ]);
    }
}
