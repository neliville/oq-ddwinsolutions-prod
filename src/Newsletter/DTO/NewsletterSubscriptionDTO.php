<?php

declare(strict_types=1);

namespace App\Newsletter\DTO;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class NewsletterSubscriptionDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Veuillez saisir votre adresse email.')]
        #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
        private string $email,
        private ?string $firstname = null,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * Parse JSON or form-data from the request into a DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $contentType = $request->headers->get('Content-Type', '');
        $isJson = str_contains($contentType, 'application/json');

        if ($isJson && '' !== $content = $request->getContent()) {
            $data = json_decode($content, true);
            if (!\is_array($data)) {
                $data = [];
            }
        } else {
            $data = $request->request->all();
        }

        $email = isset($data['email']) && \is_string($data['email']) ? trim($data['email']) : '';
        $firstname = isset($data['firstname']) && \is_string($data['firstname'])
            ? trim($data['firstname'])
            : null;
        $firstname = '' === $firstname ? null : $firstname;

        return new self($email, $firstname);
    }
}
