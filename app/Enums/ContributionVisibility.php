<?php

namespace App\Enums;

/**
 * How much of a contributor's identity a contribution reveals.
 *
 * Visibility governs the contributor's name and profile only — it never governs
 * the message, which only the wish's owner ever reads.
 */
enum ContributionVisibility: string
{
    case Public = 'public';
    case OwnerOnly = 'owner';
    case Hidden = 'hidden';
}
