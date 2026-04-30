<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Models;

use Marwa\Framework\Database\Model;

final class AuditUser extends Model
{
    protected static ?string $table = 'audit_users';
    protected static bool $softDeletes = true;

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'email',
    ];
}
