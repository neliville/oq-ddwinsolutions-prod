<?php

namespace App\Tests\Unit\Form;

use App\Entity\NewsletterSubscriber;
use App\Form\NewsletterFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class NewsletterFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
        ];

        $model = new NewsletterSubscriber();
        $form = $this->factory->create(NewsletterFormType::class, $model);

        $expected = new NewsletterSubscriber();
        $expected->setEmail('test@example.com');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected->getEmail(), $model->getEmail());
    }

    public function testFormHasEmailField(): void
    {
        $form = $this->factory->create(NewsletterFormType::class);

        $this->assertTrue($form->has('email'));
    }

    public function testValidationWithInvalidEmail(): void
    {
        $form = $this->factory->create(NewsletterFormType::class);
        
        $form->submit([
            'email' => 'invalid-email',
        ]);

        $this->assertFalse($form->isValid());
    }
}

