<?php

namespace App\Enums;

/**
 * The outcome of a single attempt to move money through the gateway.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
}
