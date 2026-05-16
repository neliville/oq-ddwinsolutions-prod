<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\Qse\RiskMatrixEntry;
use App\Form\Qse\RiskMatrixEntryFormType;
use Symfony\Component\Form\Test\TypeTestCase;

final class RiskMatrixEntryFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new \Symfony\Component\Form\Extension\Validator\ValidatorExtension(
                \Symfony\Component\Validator\Validation::createValidator(),
            ),
        ];
    }

    public function testIdentifiedRiskFieldName(): void
    {
        $form = $this->factory->create(RiskMatrixEntryFormType::class, new RiskMatrixEntry());
        self::assertTrue($form->has('identified_risk'));
    }
}
