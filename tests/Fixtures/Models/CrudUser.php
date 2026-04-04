<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Models;

use Marwa\Framework\Database\Model;

final class CrudUser extends Model
{
    protected static ?string $table = 'crud_users';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'email',
        'active',
        'meta',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $casts = [
        'active' => 'bool',
        'meta' => 'json',
    ];
}
