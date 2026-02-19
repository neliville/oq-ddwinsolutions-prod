<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ValidToolData extends Constraint
{
    public string $message = 'The tool data is invalid';
    public string $tool;

    public function __construct(
        string $tool,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        $options['tool'] = $tool;
        parent::__construct($options, $groups, $payload);
        $this->tool = $tool;
        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function getDefaultOption(): string
    {
        return 'tool';
    }

    public function getRequiredOptions(): array
    {
        return ['tool'];
    }
}
