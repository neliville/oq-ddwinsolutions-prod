<?php

declare(strict_types=1);

namespace App\Download\Application\Port;

/**
 * Port : soumission de formulaires vers un système externe (ex. Mautic).
 */
interface FormSubmissionPort
{
    /**
     * Soumet les champs au formulaire identifié par $formId.
     *
     * @param int   $formId ID du formulaire
     * @param array<string, string|null> $fields Champs [alias => valeur]
     *
     * @throws \RuntimeException En cas d'échec HTTP
     */
    public function submit(int $formId, array $fields): void;
}
