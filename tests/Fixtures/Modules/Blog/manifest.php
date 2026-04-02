<?php

declare(strict_types=1);

return [
    'name' => 'Blog Module',
    'slug' => 'blog',
    'providers' => [
        Marwa\Framework\Tests\Fixtures\Modules\Blog\BlogModuleServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
        'commands' => 'Console/Commands',
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
];
