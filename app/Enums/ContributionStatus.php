<?php

namespace App\Enums;

/**
 * Where a contribution sits in its payment lifecycle.
 *
 * A contribution is only ever pending or paid — a failed attempt deletes the
 * contribution and leaves the failure recorded on its payment instead.
 */
enum ContributionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
