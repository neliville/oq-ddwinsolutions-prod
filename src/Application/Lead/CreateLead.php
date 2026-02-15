<?php

namespace App\Application\Lead;

use App\Domain\Lead\Lead as DomainLead;

/**
 * Use case : Créer un lead
 */
class CreateLead
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {
    }

    public function execute(CreateLeadRequest $request): CreateLeadResponse
    {
        $domainLead = new DomainLead(
            email: $request->email,
            name: $request->name,
            source: $request->source,
            tool: $request->tool,
            utmSource: $request->utmSource,
            utmMedium: $request->utmMedium,
            utmCampaign: $request->utmCampaign,
            ipAddress: $request->ipAddress,
            userAgent: $request->userAgent,
            sessionId: $request->sessionId,
            gdprConsent: $request->gdprConsent ?? false,
        );

        // Calcul du score
        $score = $this->leadService->calculateScore($domainLead);
        $domainLead->setScore($score);

        // Détermination du type (B2B/B2C)
        $type = $this->leadService->determineType($domainLead);
        $domainLead->setType($type);

        // Persistance
        $entityLead = $this->leadService->persist($domainLead);

        return new CreateLeadResponse(
            id: $entityLead->getId(),
            email: $entityLead->getEmail(),
            score: $entityLead->getScore(),
            type: $entityLead->getType(),
        );
    }
}
