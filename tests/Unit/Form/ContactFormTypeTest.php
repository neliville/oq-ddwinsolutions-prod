<?php

namespace App\Tests\Unit\Form;

use App\Entity\ContactMessage;
use App\Form\ContactFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class ContactFormTypeTest extends TypeTestCase
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
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'support',
            'message' => 'Ceci est un message de test.',
            'website' => '', // Honeypot : doit rester vide
        ];

        $model = new ContactMessage();
        $form = $this->factory->create(ContactFormType::class, $model);

        $expected = new ContactMessage();
        $expected->setName('John Doe');
        $expected->setEmail('john@example.com');
        $expected->setSubject('support');
        $expected->setMessage('Ceci est un message de test.');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected->getName(), $model->getName());
        $this->assertEquals($expected->getEmail(), $model->getEmail());
        $this->assertEquals($expected->getSubject(), $model->getSubject());
        $this->assertEquals($expected->getMessage(), $model->getMessage());
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(ContactFormType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('message'));
        $this->assertTrue($form->has('website')); // Honeypot anti-spam
    }

    public function testValidationConstraints(): void
    {
        $form = $this->factory->create(ContactFormType::class);
        
        // Tester avec des données invalides
        $form->submit([
            'name' => '',
            'email' => 'invalid-email',
            'subject' => '',
            'message' => '',
            'website' => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, count($form->getErrors(true)));
    }
}

