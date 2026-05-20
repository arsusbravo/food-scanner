<?php

return [
    'free' => [
        'ai_scans' => (int) env('PLAN_FREE_SCANS', 4),
        'exports'  => (int) env('PLAN_FREE_EXPORTS', 2),
    ],
    'pro' => [
        'ai_scans' => 1000,
        'exports'  => null, // unlimited
    ],
    'demo' => [
        // Anonymous demo allowance, per device (signed `demo_id` cookie).
        'scans'          => (int) env('DEMO_SCANS', 2),
        'reports'        => (int) env('DEMO_REPORTS', 2),
        // Per-IP/day ceiling — backstop against someone clearing the cookie
        // to reset the device allowance.
        'ip_scans_day'   => (int) env('DEMO_IP_SCANS_PER_DAY', 25),
        'ip_reports_day' => (int) env('DEMO_IP_REPORTS_PER_DAY', 8),
    ],
];
