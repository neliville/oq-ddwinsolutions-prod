<?php

declare(strict_types=1);

namespace App\Newsletter\Controller;

use App\Newsletter\DTO\NewsletterSubscriptionDTO;
use App\Newsletter\Exception\NewsletterException;
use App\Newsletter\Service\NewsletterSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly NewsletterSubscriber $newsletterSubscriber,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/newsletter/subscribe', name: 'app_newsletter_mautic_subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $dto = NewsletterSubscriptionDTO::fromRequest($request);

        $errors = $this->validator->validate($dto);
        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => implode(' ', array_unique($errorMessages)),
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->newsletterSubscriber->subscribe($dto);
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
}
