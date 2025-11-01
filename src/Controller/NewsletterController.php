<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterController extends AbstractController
{
    #[Route('/api/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterSubscriberRepository $newsletterSubscriberRepository
    ): JsonResponse {
        $subscriber = new NewsletterSubscriber();
        $form = $this->createForm(NewsletterFormType::class, $subscriber);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingSubscriber = $newsletterSubscriberRepository->findOneBy(['email' => $subscriber->getEmail()]);

            if ($existingSubscriber) {
                // Si l'utilisateur s'était désabonné, réactiver l'abonnement
                if (!$existingSubscriber->isActive()) {
                    $existingSubscriber->setActive(true);
                    $existingSubscriber->setSubscribedAt(new \DateTimeImmutable());
                    $existingSubscriber->setUnsubscribedAt(null);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre abonnement à la newsletter a été réactivé !',
                    ], Response::HTTP_OK);
                }

                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cet email est déjà abonné à la newsletter.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Définir la source (d'où vient l'inscription)
            $subscriber->setSource($request->get('source', 'website'));

            // Sauvegarder en base de données
            $entityManager->persist($subscriber);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Vous êtes maintenant abonné à notre newsletter !',
            ], Response::HTTP_CREATED);
        }

        // Si le formulaire n'est pas valide, retourner les erreurs
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Erreur lors de l\'inscription.',
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/newsletter/unsubscribe/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(
        string $token,
        NewsletterSubscriberRepository $newsletterSubscriberRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $subscriber = $newsletterSubscriberRepository->findOneBy(['unsubscribeToken' => $token]);

        if (!$subscriber) {
            $this->addFlash('error', 'Token de désabonnement invalide.');
            return $this->redirectToRoute('app_home_index');
        }

        if (!$subscriber->isActive()) {
            $this->addFlash('info', 'Vous êtes déjà désabonné de notre newsletter.');
            return $this->redirectToRoute('app_home_index');
        }

        $subscriber->unsubscribe();
        $entityManager->flush();

        $this->addFlash('success', 'Vous avez été désabonné de notre newsletter avec succès.');

        return $this->redirectToRoute('app_home_index');
    }
}
