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
];
