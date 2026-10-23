<?php

namespace App\Domain\Security\Enums;

enum SecurityEventType: string
{
    case LoginSucceeded = 'login.succeeded';
    case LoginFailed = 'login.failed';
    case LoginLockedOut = 'login.locked_out';
    case AccountUnlocked = 'account.unlocked';
    case Logout = 'logout';
    case PasswordReset = 'password.reset';
    case MfaEnabled = 'mfa.enabled';
    case MfaConfirmed = 'mfa.confirmed';
    case MfaDisabled = 'mfa.disabled';
    case MfaChallengeFailed = 'mfa.challenge_failed';
    case MfaRecoveryCodeUsed = 'mfa.recovery_code_used';
    case MfaRecoveryCodesRegenerated = 'mfa.recovery_codes_regenerated';
    case MfaReset = 'mfa.reset';
    case MfaEmailCodeSent = 'mfa.email_code_sent';
    case MfaEmailEnabled = 'mfa.email_enabled';
    case MfaEmailDisabled = 'mfa.email_disabled';
    case RoleAssigned = 'role.assigned';
    case RoleAssignmentRevoked = 'role.assignment_revoked';
    case RolePermissionsChanged = 'role.permissions_changed';
    case SensitiveDataRevealed = 'sensitive_data.revealed';
    case DataExported = 'data.exported';
    case AuditPruned = 'audit.pruned';
    case PasswordChanged = 'password.changed';
    case EmailChanged = 'email.changed';

    public function label(): string
    {
        return __('security.event_types.'.str_replace('.', '_', $this->value));
    }
}
