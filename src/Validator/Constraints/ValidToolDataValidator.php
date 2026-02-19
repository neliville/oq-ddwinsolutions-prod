<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidToolDataValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidToolData) {
            throw new UnexpectedTypeException($constraint, ValidToolData::class);
        }

        if ($value === null) {
            return;
        }

        if (!\is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        match ($constraint->tool) {
            'ishikawa' => $this->validateIshikawa($value),
            'fivewhy' => $this->validateFiveWhy($value),
            default => null,
        };
    }

    private function validateIshikawa(array $data): void
    {
        if (!isset($data['title']) || $data['title'] === '') {
            $this->context->buildViolation('Le champ title est requis.')
                ->atPath('[title]')
                ->addViolation();
        }
        if (!isset($data['content'])) {
            $this->context->buildViolation('Le champ content est requis.')
                ->atPath('[content]')
                ->addViolation();
        }
    }

    private function validateFiveWhy(array $data): void
    {
        if (!isset($data['title']) || $data['title'] === '') {
            $this->context->buildViolation('Le champ title est requis.')
                ->atPath('[title]')
                ->addViolation();
        }
        if (!isset($data['content'])) {
            $this->context->buildViolation('Le champ content est requis.')
                ->atPath('[content]')
                ->addViolation();
        }
    }
}
