<?php

namespace App\Application\Lead;

use App\Domain\Lead\Lead as DomainLead;
use App\Entity\Lead as EntityLead;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service applicatif pour la gestion des leads
 */
class LeadService
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Calcule le score d'un lead (0-100)
     * 
     * Critères :
     * - Email fourni : +20
     * - Nom fourni : +10
     * - Outil utilisé : +30
     * - Source newsletter : +15
     * - Source contact : +25
     * - Source demo-request : +40
     * - UTM campaign : +10
     * - Consentement RGPD : +5
     */
    public function calculateScore(DomainLead $lead): int
    {
        $score = 0;

        if ($lead->getEmail()) {
            $score += 20;
        }

        if ($lead->getName()) {
            $score += 10;
        }

        if ($lead->getTool()) {
            $score += 30;
        }

        switch ($lead->getSource()) {
            case 'newsletter':
                $score += 15;
                break;
            case 'contact':
                $score += 25;
                break;
            case 'demo-request':
                $score += 40;
                break;
            case 'tool':
                $score += 20;
                break;
        }

        if ($lead->getUtmCampaign()) {
            $score += 10;
        }

        if ($lead->hasGdprConsent()) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Détermine le type de lead (B2B ou B2C)
     * 
     * Heuristique simple :
     * - Email professionnel (domaine d'entreprise) → B2B
     * - Email générique (gmail, yahoo, etc.) → B2C
     */
    public function determineType(DomainLead $lead): ?string
    {
        $email = $lead->getEmail();
        if (!$email) {
            return null;
        }

        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) {
            return null;
        }

        $b2cDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'icloud.com', 'protonmail.com', 'mail.com'];
        
        if (in_array(strtolower($domain), $b2cDomains)) {
            return 'B2C';
        }

        return 'B2B';
    }

    /**
     * Persiste un lead domain en entité Doctrine
     */
    public function persist(DomainLead $domainLead): EntityLead
    {
        $entityLead = new EntityLead();
        $entityLead->setEmail($domainLead->getEmail());
        $entityLead->setName($domainLead->getName());
        $entityLead->setSource($domainLead->getSource());
        $entityLead->setTool($domainLead->getTool());
        $entityLead->setUtmSource($domainLead->getUtmSource());
        $entityLead->setUtmMedium($domainLead->getUtmMedium());
        $entityLead->setUtmCampaign($domainLead->getUtmCampaign());
        $entityLead->setIpAddress($domainLead->getIpAddress());
        $entityLead->setUserAgent($domainLead->getUserAgent());
        $entityLead->setSessionId($domainLead->getSessionId());
        $entityLead->setGdprConsent($domainLead->hasGdprConsent());
        $entityLead->setScore($domainLead->getScore());
        $entityLead->setType($domainLead->getType());
        $entityLead->setCreatedAt($domainLead->getCreatedAt());

        $this->entityManager->persist($entityLead);
        $this->entityManager->flush();

        return $entityLead;
    }
}

