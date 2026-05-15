<?php

declare(strict_types=1);

namespace App\Export\Dto;

/**
 * Vue fusionnée : branding système + personnalisation utilisateur optionnelle.
 */
final readonly class ExportBrandingView
{
    public function __construct(
        public ExportSystemBranding $system = new ExportSystemBranding(),
        public ?ExportUserBranding $user = null,
        public ?string $profileDisplayName = null,
    ) {
    }

    public function hasUserCustomization(): bool
    {
        return $this->user !== null && !$this->user->isEmpty();
    }

    /**
     * Payload API / export JSON (rétrocompatibilité clés plates + structure structurée).
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $user = $this->user;
        $legacy = [
            'exportDisplayName' => $user?->displayName,
            'exportJobTitle' => $user?->jobTitle,
            'exportCompanyName' => $user?->companyName,
            'exportPdfFooter' => $user?->pdfFooter,
            'exportLogoFilename' => $user?->logoFilename,
            'profileDisplayName' => $this->profileDisplayName,
        ];

        $structured = [
            'system' => $this->system->toArray(),
        ];
        if ($this->hasUserCustomization() && $user !== null) {
            $structured['user'] = $user->toArray();
        }

        return array_merge($legacy, $structured);
    }
}
