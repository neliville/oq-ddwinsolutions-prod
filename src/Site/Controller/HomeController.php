<?php

namespace App\Site\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use App\Repository\LeadRepository;
use App\Repository\NewsletterSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly NewsletterSubscriberRepository $newsletterSubscriberRepository,
    ) {
    }

    #[Route('/', name: 'app_home_index')]
    public function index(Request $request): Response
    {
        $subscriber = new NewsletterSubscriber();
        $newsletterForm = $this->createForm(NewsletterFormType::class, $subscriber);

        $leadsCount = $this->leadRepository->count([]);
        $subscribersCount = $this->newsletterSubscriberRepository->countActive();

        return $this->render('home/index.html.twig', [
            'newsletterForm' => $newsletterForm,
            'leadsCount' => $leadsCount,
            'subscribersCount' => $subscribersCount,
        ]);
    }
}
