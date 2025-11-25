<?php

namespace App\Controller;

use App\Dto\UnsubscribeReasonDto;
use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use App\Form\UnsubscribeReasonType;
use App\Repository\NewsletterSubscriberRepository;
use App\Service\NewsletterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly NewsletterService $newsletterService,
    ) {
    }

    #[Route('/api/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterSubscriberRepository $newsletterSubscriberRepository
    ): JsonResponse {
        $subscriber = new NewsletterSubscriber();
        // Désactiver CSRF pour l'API (plus pratique pour les tests et appels AJAX)
        // Accepter les champs supplémentaires pour éviter l'erreur "extra fields"
        $form = $this->createForm(NewsletterFormType::class, $subscriber, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
        $form->handleRequest($request);

        // Si le formulaire n'est pas soumis, retourner une erreur
        if (!$form->isSubmitted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le formulaire n\'a pas été soumis correctement.',
                'errors' => ['Veuillez réessayer.'],
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingSubscriber = $newsletterSubscriberRepository->findOneBy(['email' => $subscriber->getEmail()]);

            if ($existingSubscriber) {
                // Si l'utilisateur s'était désabonné, réactiver l'abonnement
                if (!$existingSubscriber->isActive()) {
                    $existingSubscriber->setActive(true);
                    $existingSubscriber->setSubscribedAt(new \DateTimeImmutable());
                    $existingSubscriber->setUnsubscribedAt(null);
                    $entityManager->flush();

                    // Envoyer l'email de bienvenue pour réactivation
                    try {
                        $this->newsletterService->sendWelcomeEmail($existingSubscriber);
                    } catch (\Exception $e) {
                        error_log('Erreur lors de l\'envoi de l\'email de réactivation : ' . $e->getMessage());
                    }

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

            // Envoyer l'email de bienvenue
            try {
                $this->newsletterService->sendWelcomeEmail($subscriber);
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas l'inscription
                // TODO: Logger l'erreur dans un fichier log ou service de logging
                error_log('Erreur lors de l\'envoi de l\'email de bienvenue : ' . $e->getMessage());
            }

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
        
        // Récupérer les erreurs de validation des champs
        foreach ($form->all() as $child) {
            foreach ($child->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }
        }

        $errorMessage = !empty($errors) 
            ? implode(' ', array_unique($errors))
            : 'Erreur lors de l\'inscription. Veuillez vérifier votre adresse email.';

        return new JsonResponse([
            'success' => false,
            'message' => $errorMessage,
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/newsletter/unsubscribe/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET', 'POST'])]
    public function unsubscribe(
        string $token,
        Request $request,
        NewsletterSubscriberRepository $newsletterSubscriberRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $subscriber = $newsletterSubscriberRepository->findOneBy(['unsubscribeToken' => $token]);

        if (!$subscriber) {
            $this->addFlash('error', 'Token de désabonnement invalide.');
            return $this->redirectToRoute('app_home_index');
        }

        // Si déjà désabonné, afficher un message
        if (!$subscriber->isActive()) {
            return $this->render('newsletter/unsubscribe_already.html.twig', [
                'email' => $subscriber->getEmail(),
            ]);
        }

        // Créer le formulaire avec le DTO
        $dto = new UnsubscribeReasonDto();
        $form = $this->createForm(UnsubscribeReasonType::class, $dto);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, effectuer le désabonnement
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UnsubscribeReasonDto $dto */
            $dto = $form->getData();
            $reasons = $dto->getReasons();
            $comment = $dto->getComment();

            $subscriber->unsubscribe($reasons, $comment);
            $entityManager->flush();

            return $this->render('newsletter/unsubscribe_success.html.twig', [
                'email' => $subscriber->getEmail(),
            ]);
        }

        // Afficher le formulaire de désabonnement
        return $this->render('newsletter/unsubscribe.html.twig', [
            'email' => $subscriber->getEmail(),
            'form' => $form,
        ]);
    }
}
