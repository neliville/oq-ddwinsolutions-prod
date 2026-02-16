<?php

namespace App\Lead\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactFormType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerService $mailerService,
    ) {
    }

    #[Route('/contact', name: 'app_contact_index')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contactMessage = new ContactMessage();
        
        // Si l'utilisateur est connecté, pré-remplir le nom et l'email
        if ($this->getUser()) {
            $contactMessage->setEmail($this->getUser()->getUserIdentifier());
            $contactMessage->setUser($this->getUser());
        }

        $form = $this->createForm(ContactFormType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Honeypot anti-spam : si le champ "website" est rempli, considérer comme bot
            $honeypot = $form->get('website')->getData();
            if ($honeypot !== null && trim((string) $honeypot) !== '') {
                $this->addFlash('contact_success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
                return $this->redirectToRoute('app_contact_index');
            }

            // Sauvegarder le message en base de données
            $entityManager->persist($contactMessage);
            $entityManager->flush();

            try {
                $this->mailerService->sendContactAcknowledgement($contactMessage);
            } catch (\Throwable $exception) {
                if ($_SERVER['APP_ENV'] ?? null) {
                    if ($_SERVER['APP_ENV'] === 'test') {
                        throw $exception;
                    }
                }

                error_log('Erreur lors de l\'envoi de l\'accusé de réception : ' . $exception->getMessage());
            }

            $this->addFlash('contact_success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');

            // Rediriger pour éviter la double soumission
            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
