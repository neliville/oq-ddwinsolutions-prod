<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\UserPreferences;
use App\Form\Preference\UserDashboardPreferencesFormType;
use App\Service\DashboardPreferencesService;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

final class DashboardPreferencesServiceTest extends FormIntegrationTestCase
{
    private DashboardPreferencesService $service;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardPreferencesService();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
    }

    public function testResolveVisibilityTreatsUncheckedBoxesAsFalse(): void
    {
        $prefs = new UserPreferences();
        $form = $this->formFactory->create(UserDashboardPreferencesFormType::class, null, [
            'preferences' => $prefs,
        ]);

        $form->submit([
            'dash_deadlines' => '1',
            'dash_capa' => '1',
            'dash_risks' => '1',
            // audits et pdca absents = décochés
            'dash_anomalies' => '1',
            'dash_kpi' => '1',
        ]);

        $visibility = $this->service->resolveVisibilityFromSubmittedForm($form);

        $this->assertTrue($visibility['deadlines']);
        $this->assertFalse($visibility['audits']);
        $this->assertFalse($visibility['pdca']);
    }

    public function testApplyVisibilityPersistsAllKeys(): void
    {
        $prefs = new UserPreferences();
        $this->service->applyVisibility($prefs, [
            'audits' => false,
            'pdca' => false,
            'deadlines' => true,
        ]);

        $stored = $prefs->getDashboardVisibility();
        $this->assertIsArray($stored);
        $this->assertFalse($stored['audits']);
        $this->assertFalse($stored['pdca']);
        $this->assertTrue($stored['deadlines']);
        $this->assertFalse($prefs->isDashboardSectionVisible('audits'));
        $this->assertTrue($prefs->isDashboardSectionVisible('deadlines'));
    }
}
