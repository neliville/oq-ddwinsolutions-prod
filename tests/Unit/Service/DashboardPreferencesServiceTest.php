<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dashboard\DashboardLayout;
use App\Dashboard\DashboardWidgetId;
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

    public function testApplyVisibilityFromSubmittedFormPersistsVersionedLayout(): void
    {
        $prefs = new UserPreferences();
        $form = $this->formFactory->create(UserDashboardPreferencesFormType::class, null, [
            'preferences' => $prefs,
        ]);

        $form->submit([
            'dash_deadlines' => '1',
            'dash_capa' => '1',
            'dash_risks' => '1',
            // audits et pdca absents
            'dash_anomalies' => '1',
            'dash_kpi_stats' => '1',
            'dash_kpi_ai' => '1',
        ]);

        $this->service->applyVisibilityFromSubmittedForm($prefs, $form);

        $layout = $this->service->getDashboardLayout($prefs);
        $this->assertFalse($layout->isWidgetVisible('audits'));
        $this->assertFalse($layout->isWidgetVisible('pdca'));
        $this->assertSame(1, $prefs->getDashboardLayout()['version'] ?? null);
    }

    public function testGetOrderedVisibleWidgetsExcludesHidden(): void
    {
        $prefs = new UserPreferences();
        $prefs->setDashboardVisibility(['capa' => false, 'kpi' => false]);

        $visible = $this->service->getOrderedVisibleWidgets($prefs);

        $this->assertNotContains('capa', $visible);
        $this->assertNotContains('kpi_stats', $visible);
        $this->assertNotContains('kpi_ai', $visible);
        $this->assertContains('deadlines', $visible);
    }

    public function testLegacyVisibilityMapSetsAllWidgetKeys(): void
    {
        $prefs = new UserPreferences();
        $this->service->applyLegacyVisibilityMap($prefs, ['risks' => false]);

        $stored = $prefs->getDashboardLayout();
        $this->assertIsArray($stored);
        $this->assertCount(8, $stored['widgets'] ?? []);
    }
}
