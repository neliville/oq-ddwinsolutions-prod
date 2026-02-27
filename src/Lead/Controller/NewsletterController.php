<?php

namespace App\Lead\Controller;

use App\Dto\UnsubscribeReasonDto;
use App\Entity\NewsletterSubscriber;
use App\Form\UnsubscribeReasonType;
use App\Newsletter\DTO\NewsletterSubscriptionDTO;
use App\Newsletter\Form\NewsletterSubscriptionFormType;
use App\Newsletter\Exception\NewsletterException;
use App\Newsletter\Service\NewsletterSubscriber as NewsletterSubscriberService;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly NewsletterSubscriberService $newsletterSubscriberService,
        private readonly NewsletterSubscriberRepository $newsletterSubscriberRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/newsletter/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $form = $this->createForm(NewsletterSubscriptionFormType::class, null, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le formulaire n\'a pas été soumis correctement.',
                'errors' => ['Veuillez réessayer.'],
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
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

        /** @var array{email: string, firstname?: string|null} $data */
        $data = $form->getData();
        $dto = new NewsletterSubscriptionDTO(
            $data['email'],
            $data['firstname'] ?? null,
        );

        try {
            $this->newsletterSubscriberService->subscribe($dto);
        } catch (NewsletterException $e) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
            if ($e->getPrevious() instanceof \App\Newsletter\Exception\MauticApiException) {
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
                $message = 'Impossible de finaliser l\'inscription à la newsletter. Veuillez réessayer plus tard.';
            }
            return new JsonResponse([
                'success' => false,
                'message' => $message,
                'errors' => [$message],
            ], $statusCode);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Vous êtes maintenant abonné à notre newsletter !',
        ], Response::HTTP_CREATED);
    }

    #[Route('/newsletter/unsubscribe/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET', 'POST'])]
    public function unsubscribe(
        string $token,
        Request $request,
    ): Response {
        $subscriber = $this->newsletterSubscriberRepository->findOneBy(['unsubscribeToken' => $token]);

        if (!$subscriber) {
            $this->addFlash('error', 'Token de désabonnement invalide.');
            return $this->redirectToRoute('app_home_index');
        }

        if (!$subscriber->isActive()) {
            return $this->render('newsletter/unsubscribe_already.html.twig', [
                'email' => $subscriber->getEmail(),
            ]);
        }

        $dto = new UnsubscribeReasonDto();
        $form = $this->createForm(UnsubscribeReasonType::class, $dto);
        $form->handleRequest($request);

        $skip = $request->request->get('skip') === '1' || $request->request->has('skip');

        if ($form->isSubmitted()) {
            if ($skip) {
                $reasons = [];
                $comment = null;
            } elseif ($form->isValid()) {
                /** @var UnsubscribeReasonDto $dto */
                $dto = $form->getData();
                $reasons = $dto->getReasons() ?? [];
                $comment = $dto->getComment();
            } else {
                return $this->render('newsletter/unsubscribe.html.twig', [
                    'email' => $subscriber->getEmail(),
                    'form' => $form,
                ]);
            }

            $subscriber->unsubscribe($reasons, $comment);
            $this->entityManager->flush();

            return $this->render('newsletter/unsubscribe_success.html.twig', [
                'email' => $subscriber->getEmail(),
            ]);
        }

        return $this->render('newsletter/unsubscribe.html.twig', [
            'email' => $subscriber->getEmail(),
            'form' => $form,
            'token' => $token,
        ]);
    }
}
