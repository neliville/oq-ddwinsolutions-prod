<?php

declare(strict_types=1);

namespace App\Service;

use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

enum PasswordResetRequestOutcome
{
    case USER_NOT_FOUND;
    case THROTTLED;
    case MAIL_FAILED;
    case SENT;
}

final readonly class PasswordResetRequestResult
{
    public function __construct(
        public PasswordResetRequestOutcome $outcome,
        public ?ResetPasswordToken $tokenForSession = null,
        public ?string $errorMessage = null,
    ) {
    }

    public static function userNotFound(): self
    {
        return new self(PasswordResetRequestOutcome::USER_NOT_FOUND);
    }

    public static function throttled(): self
    {
        return new self(PasswordResetRequestOutcome::THROTTLED);
    }

    public static function mailFailed(string $message): self
    {
        return new self(PasswordResetRequestOutcome::MAIL_FAILED, null, $message);
    }

    public static function sent(ResetPasswordToken $token): self
    {
        return new self(PasswordResetRequestOutcome::SENT, $token);
    }
}
