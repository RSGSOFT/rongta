<?php

return [

    /*
    | The one staff admin account, created or updated by AdminUserSeeder:
    |   php artisan db:seed --class=AdminUserSeeder --force
    | Change the values in .env and run the seeder again to change the login.
    */

    'name' => env('ADMIN_NAME', 'Administrator'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),

];
