<?php

//get pending mandate 

use App\Models\DebitOrder;

if (!function_exists('get_pending_debit_orders')) {
    function get_pending_debit_orders()
    {
        $debit_orders = DebitOrder::where(['approved' => 0]);

        if ($debit_orders->get()->isNotEmpty()) {
            return $debit_orders->count();
        } else {
            return null;
        }
    }
}

if (!function_exists('mandate_status_badge')) {
    function mandate_status_badge($values)
    {
        $lifecycleStatus = $values->resolved_lifecycle_status ?? $values->lifecycle_status ?? DebitOrder::LIFECYCLE_DRAFT;

<<<<<<< HEAD
        if (in_array($values->approved, [0, 1, 2]) && $values->contract_status == 'CLOSED') {
            return badge('CLOSED', 'closed');
        }

        // Approved = 1
        if ($values->approved == 1 && $values->status == 240) {
            return badge('Active', 'success');
        }

        // Approved = 0 or 2 (shared logic)
        if (in_array((int) $values->approved, [0, 2], true)) {
            // cast status
            $status = (int) $values->status;

            return match ($status) {
                100, 403, 404 => badge('Pending Registration', 'danger'),
                200           => badge('Under Review', 'info'),
                201           => badge('Pending Verification', 'info'),
                202           => badge('Verified', 'info'),
                249           => badge('Processing', 'warning'),
                405           => badge('Cancelled', 'danger'),
                430, 500      => badge('Failed Verification', 'danger'),
                default       => badge('Unknown Status', 'secondary'),
            };
        }

        return null;
=======
        return match ($lifecycleStatus) {
            DebitOrder::LIFECYCLE_DRAFT => badge('Draft', 'secondary'),
            DebitOrder::LIFECYCLE_SUBMITTED => badge('Submitted', 'info'),
            DebitOrder::LIFECYCLE_AWAITING_OTP => badge('Awaiting OTP', 'warning'),
            DebitOrder::LIFECYCLE_VERIFIED => badge('Verified', 'info'),
            DebitOrder::LIFECYCLE_ACTIVE => badge('Active', 'success'),
            DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED => badge('Cancellation Requested', 'warning'),
            DebitOrder::LIFECYCLE_CANCELLED => badge('Cancelled', 'danger'),
            DebitOrder::LIFECYCLE_CLOSED => badge('Closed', 'closed'),
            DebitOrder::LIFECYCLE_ARCHIVED => badge('Archived', 'secondary'),
            DebitOrder::LIFECYCLE_FAILED => badge('Failed', 'danger'),
            default => badge(ucwords(str_replace('_', ' ', (string) $lifecycleStatus)), 'secondary'),
        };
>>>>>>> c3bb0f30c1a5f69834d284b7d45ccc1cb584f71e
    }
}

if (!function_exists('badge')) {
    function badge($label, $type)
    {
        return sprintf(
            '<span class="badge badge-pill badge-soft-%s">%s</span>',
            e($type),
            e($label)
        );
    }
}
