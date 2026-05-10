<?php

declare(strict_types=1);

namespace App\Application\Analytics;

/**
 * Types d'événements métier persistés dans {@see \App\Entity\TrackingEvent}.
 */
enum TrackingEventType: string
{
    case TOOL_OPENED = 'tool_opened';
    case ACCOUNT_CREATED = 'account_created';
    case CAPA_CREATED = 'capa_created';
    case AUDIT_CREATED = 'audit_created';
    case RISK_CREATED = 'risk_created';
    case EXPORT_TRIGGERED = 'export_triggered';
    case DASHBOARD_OPENED = 'dashboard_opened';
    case LOGIN_RETURN = 'login_return';
    case AUDIT_COMPLETED = 'audit_completed';
    case SHARED_ACCESS_OPENED = 'shared_access_opened';
    case AUDIT_SHARE_INTENT = 'audit_share_intent';
    case PREMIUM_COLLABORATION_SIGNAL = 'premium_collaboration_signal';
    case COLLABORATION_SUGGESTION_SHOWN = 'collaboration_suggestion_shown';
    case COLLABORATION_SUGGESTION_DISMISSED = 'collaboration_suggestion_dismissed';

    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
