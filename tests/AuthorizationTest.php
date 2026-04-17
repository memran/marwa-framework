<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Authorization\AuthManager;
use Marwa\Framework\Authorization\Gate;
use Marwa\Framework\Authorization\PolicyRegistry;
use Marwa\Framework\Authorization\Policy;
use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Authorization\Contracts\UserInterface;
use Marwa\Framework\Exceptions\AuthorizationException;
use PHPUnit\Framework\TestCase;

final class AuthorizationTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-auth-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        file_put_contents($this->basePath . DIRECTORY_SEPARATOR . '.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        if (is_dir($this->basePath)) {
            $this->deleteDirectory($this->basePath);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testGateAuthorizeThrowsOnFailure(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $user = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return false;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Unauthorized');
        $gate->authorize('viewAny', PostModel::class);
    }

    public function testGateAllowsWhenUserHasPermission(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $user = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return true;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user);

        $this->assertTrue($gate->allows('viewAny', PostModel::class));
        $this->assertFalse($gate->denies('viewAny', PostModel::class));
    }

    public function testGateCheckWithPolicy(): void
    {
        $registry = new PolicyRegistry();
        $registry->register(PostModel::class, PostPolicy::class);

        $gate = new Gate($registry);

        $user = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return true;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user);

        $post = new PostModel(['user_id' => 1, 'title' => 'Test']);

        $this->assertTrue($gate->check('view', $post));
        $this->assertTrue($gate->check('update', $post));
    }

    public function testGateCheckDeniesWhenNoPermission(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $user = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return false;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user);

        $this->assertFalse($gate->allows('delete', PostModel::class));
    }

    public function testPolicyBeforeCallback(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $adminUser = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return false;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }

            public function isAdmin(): bool
            {
                return true;
            }
        };

        $gate->setUser($adminUser);

        $gate->before(function ($user, $ability, $resource) {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }
            return null;
        });

        $this->assertTrue($gate->authorize('delete', PostModel::class));
    }

    public function testAuthorizationExceptionContainsAbilityAndResource(): void
    {
        $exception = new AuthorizationException(
            'Cannot delete this post',
            'delete',
            new PostModel(['id' => 1])
        );

        $this->assertEquals('delete', $exception->getAbility());
        $this->assertInstanceOf(PostModel::class, $exception->getResource());
    }

    public function testPolicyRegistryRegisterAndResolve(): void
    {
        $registry = new PolicyRegistry();
        $registry->register(PostModel::class, PostPolicy::class);

        $this->assertTrue($registry->hasPolicy(PostModel::class));
        $this->assertEquals(PostPolicy::class, $registry->getPolicy(PostModel::class));

        $policy = $registry->resolve(PostModel::class);
        $this->assertInstanceOf(PostPolicy::class, $policy);
    }

    public function testPolicyRegistryThrowsWhenNoPolicy(): void
    {
        $registry = new PolicyRegistry();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('No policy registered for');
        $registry->resolve(PostModel::class);
    }

    public function testPolicyRegistryLoadFromConfig(): void
    {
        $registry = new PolicyRegistry();
        $registry->loadFromConfig([
            PostModel::class => PostPolicy::class,
            UserModel::class => UserPolicy::class,
        ]);

        $this->assertTrue($registry->hasPolicy(PostModel::class));
        $this->assertTrue($registry->hasPolicy(UserModel::class));
    }

    public function testGateForUserCreatesNewInstance(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $user1 = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return true;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $user2 = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return false;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user1);
        $this->assertTrue($gate->allows('viewAny'));

        $gateForUser2 = $gate->forUser($user2);
        $this->assertFalse($gateForUser2->allows('viewAny'));
    }

    public function testHelperFunctions(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);

        $user = new class implements UserInterface {
            public function hasPermission(string $permission): bool
            {
                return $permission === 'test.ability';
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $gate->setUser($user);

        $this->assertTrue($gate->check('test.ability'));
        $this->assertFalse($gate->check('other.ability'));
    }

    public function testAuthManagerSetUser(): void
    {
        $registry = new PolicyRegistry();
        $gate = new Gate($registry);
        $auth = new AuthManager($gate);

        $user = new class implements UserInterface {
            public int $id = 1;

            public function hasPermission(string $permission): bool
            {
                return true;
            }

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $auth->setUser($user);

        $this->assertTrue($auth->check());
        $this->assertFalse($auth->guest());
        $this->assertEquals(1, $auth->id());
    }
}

class PostModel
{
    /** @var array<string, mixed> */
    public array $attributes;

    public ?int $user_id = null;

    public ?int $id = null;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->user_id = $attributes['user_id'] ?? null;
        $this->id = $attributes['id'] ?? null;
    }
}

class UserModel
{
    public int $id = 1;
}

class PostPolicy extends Policy
{
    public function view(UserInterface $user, PostModel $post): bool
    {
        return $user->hasPermission('blog.post.view') || $this->isOwner($user, $post);
    }

    public function viewAny(UserInterface $user): bool
    {
        return $user->hasPermission('blog.post.viewAny');
    }

    public function create(UserInterface $user): bool
    {
        return $user->hasPermission('blog.post.create');
    }

    public function update(UserInterface $user, PostModel $post): bool
    {
        return $user->hasPermission('blog.post.update') || $this->isOwner($user, $post);
    }

    public function delete(UserInterface $user, PostModel $post): bool
    {
        return $user->hasPermission('blog.post.delete');
    }
}

class UserPolicy extends Policy
{
    public function view(UserInterface $user, UserModel $model): bool
    {
        return $user->hasPermission('admin.user.view');
    }

    public function viewAny(UserInterface $user): bool
    {
        return $user->hasPermission('admin.user.viewAny');
    }
}
