<?php

return [
    'password_reset_enabled' => (bool) env('PASSWORD_RESET_ENABLED', false),
    'allow_admin_api' => (bool) env('ALLOW_ADMIN_API', false),
    'claim_window_days' => 30,
    'order_max_age_days' => 60,
    'slip_max_kb' => 10240,
];
