<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\CAPAAction;
use App\Qse\Enum\CapaStatus;
use App\Qse\Service\CapaWorkflowValidator;
use PHPUnit\Framework\TestCase;

final class CapaWorkflowValidatorTest extends TestCase
{
    public function testAssertCanCloseRejectsWrongStatus(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::EN_COURS);
        $capa->setEffectivenessVerification('OK');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('attente de vérification');
        $v->assertCanClose($capa);
    }

    public function testAssertCanCloseRejectsMissingVerificationText(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::EN_ATTENTE_DE_VERIFICATION);
        $capa->setEffectivenessVerification('  ');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('efficacité');
        $v->assertCanClose($capa);
    }

    public function testAssertCanCloseAcceptsValidState(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::EN_ATTENTE_DE_VERIFICATION);
        $capa->setEffectivenessVerification('Vérification terrain OK.');

        $v->assertCanClose($capa);
        $this->addToAssertionCount(1);
    }

    public function testCloseAfterVerificationAppliesCloturee(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::EN_ATTENTE_DE_VERIFICATION);
        $capa->setEffectivenessVerification('Contrôle effectué.');

        $v->closeAfterVerification($capa);

        self::assertSame(CapaStatus::CLOTUREE, $capa->getStatus());
        self::assertNotNull($capa->getClosedAt());
    }

    public function testMarkImplementationDoneRejectsBrouillon(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::BROUILLON);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Statut incompatible');
        $v->markImplementationDone($capa);
    }

    public function testMarkImplementationDoneFromEnCours(): void
    {
        $v = new CapaWorkflowValidator();
        $capa = new CAPAAction();
        $capa->setStatus(CapaStatus::EN_COURS);

        $v->markImplementationDone($capa);

        self::assertSame(CapaStatus::EN_ATTENTE_DE_VERIFICATION, $capa->getStatus());
        self::assertNotNull($capa->getImplementationDoneAt());
    }
}
