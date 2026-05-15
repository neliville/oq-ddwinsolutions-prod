<?php

declare(strict_types=1);

namespace App\Export\Dto;

/**
 * Sous-ensemble des préférences d’export utilisateur (champs non vides uniquement).
 */
final readonly class ExportUserBranding
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $jobTitle = null,
        public ?string $companyName = null,
        public ?string $pdfFooter = null,
        public ?string $logoFilename = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->displayName === null
            && $this->jobTitle === null
            && $this->companyName === null
            && $this->pdfFooter === null
            && $this->logoFilename === null;
    }

    /**
     * @return list<string>
     */
    public function headerLines(): array
    {
        return array_values(array_filter(
            [$this->displayName, $this->jobTitle, $this->companyName],
            static fn (?string $s): bool => $s !== null && $s !== '',
        ));
    }

    public function toArray(): array
    {
        $out = [];
        if ($this->displayName !== null) {
            $out['displayName'] = $this->displayName;
        }
        if ($this->jobTitle !== null) {
            $out['jobTitle'] = $this->jobTitle;
        }
        if ($this->companyName !== null) {
            $out['companyName'] = $this->companyName;
        }
        if ($this->pdfFooter !== null) {
            $out['pdfFooter'] = $this->pdfFooter;
        }
        if ($this->logoFilename !== null) {
            $out['logoFilename'] = $this->logoFilename;
        }

        return $out;
    }
}
