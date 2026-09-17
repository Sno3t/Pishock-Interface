<?php
return [

    /*
     * Pishock private user info
     */

    'apikey' => env('PISHOCK_API_KEY'),

    'username' => env('PISHOCK_USERNAME'),

    /*
     * Max commands a single operator (or the owner) can send per minute.
     */
    'commands_per_minute' => (int) env('PISHOCK_COMMANDS_PER_MINUTE', 20),

    /*
     * How long to keep operation history before it's pruned, and how long
     * to keep a revoked/expired operator link around before it's deleted.
     * Set either to 0 to disable that pruning entirely.
     */
    'history_retention_days' => (int) env('PISHOCK_HISTORY_RETENTION_DAYS', 180),

    'operator_token_retention_days' => (int) env('PISHOCK_OPERATOR_TOKEN_RETENTION_DAYS', 30),
];
