# Testing Guide

This guide covers writing and running tests for your Marwa Framework application.

## Why Test?

- Catch bugs early
- Ensure code works as expected
- Enable safe refactoring
- Document expected behavior

## Test Framework

The framework uses PHPUnit for testing.

## Running Tests

### All Tests

```bash
composer test
```

### Specific Test File

```bash
vendor/bin/phpunit tests/ControllersTest.php
```

### Specific Test Method

```bash
vendor/bin/phpunit --filter testUserCanLogin
```

### With Code Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Structure

```
tests/
├── Fixtures/
│   ├── Controllers/
│   ├── Models/
│   └── Seeders/
├── ControllersTest.php
├── ModelsTest.php
└── SupportsTest.php
```

## Writing Tests

### Basic Test

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }

    public function testExpectedValue(): void
    {
        $value = 1 + 1;
        $this->assertSame(2, $value);
    }
}
```

### Testing Controllers

```php
<?php

declare(strict_types=1);

namespace Tests;

use Tests\Fixtures\Controllers\InspectableController;
use PHPUnit\Framework\TestCase;
use Marwa\Framework\HttpKernel;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerTest extends TestCase
{
    public function testControllerReturnsResponse(): void
    {
        // Arrange
        $app = $this->createApplication();
        
        // Act
        $response = $app->handle($this->createRequest('GET', '/users'));
        
        // Assert
        $this->assertSame(200, $response->getStatusCode());
    }

    private function createApplication(): HttpKernel
    {
        // Create test application
    }

    private function createRequest(string $method, string $path): ServerRequestInterface
    {
        // Create test request
    }
}
```

### Testing Models

```php
<?php

declare(strict_types=1);

namespace Tests;

use Tests\Fixtures\Models\CrudUser;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up test database
    }

    public function testCanCreateUser(): void
    {
        // Arrange
        $user = new CrudUser();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        // Act
        $id = $user->save();

        // Assert
        $this->assertNotNull($id);
    }

    public function testCanFindUser(): void
    {
        // Arrange - create user
        $user = new CrudUser();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->save();

        // Act
        $found = CrudUser::find($user->id);

        // Assert
        $this->assertNotNull($found);
        $this->assertSame('John Doe', $found->name);
    }

    public function testCanUpdateUser(): void
    {
        // Arrange
        $user = new CrudUser();
        $user->name = 'John Doe';
        $user->save();

        // Act
        $user->name = 'Jane Doe';
        $user->save();

        // Assert
        $updated = CrudUser::find($user->id);
        $this->assertSame('Jane Doe', $updated->name);
    }

    public function testCanDeleteUser(): void
    {
        // Arrange
        $user = new CrudUser();
        $user->name = 'John Doe';
        $user->save();
        $id = $user->id;

        // Act
        $user->delete();

        // Assert
        $deleted = CrudUser::find($id);
        $this->assertNull($deleted);
    }
}
```

### Testing With Fixtures

```php
<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Marwa\DB\ORM\Model;

final class CrudUser extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $hidden = ['password'];
}
```

### Testing HTTP Requests

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Marwa\Framework\Facades\Http;

final class HttpClientTest extends TestCase
{
    public function testCanMakeGetRequest(): void
    {
        // Arrange
        $client = http();

        // Act
        $response = $client->get('https://api.example.com/users');

        // Assert
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCanMakePostRequest(): void
    {
        // Arrange
        $client = http();

        // Act
        $response = $client->post('https://api.example.com/users', [
            'json' => ['name' => 'John']
        ]);

        // Assert
        $this->assertSame(201, $response->getStatusCode());
    }
}
```

### Testing Events

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Marwa\Framework\Facades\Event;

final class EventTest extends TestCase
{
    public function testEventListenerIsCalled(): void
    {
        // Arrange
        $called = false;
        
        Event::listen(RequestHandled::class, function (RequestHandled $event) use (&$called) {
            $called = true;
        });

        // Act
        $event = new RequestHandled('GET', '/users', 200);
        app()->dispatch($event);

        // Assert
        $this->assertTrue($called);
    }
}
```

### Testing Validation

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Validation\ValidationException;

final class ValidationTest extends TestCase
{
    public function testValidDataPasses(): void
    {
        // Arrange
        $validator = new RequestValidator([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        // Act
        $result = $validator->validate([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Assert
        $this->assertTrue($result);
    }

    public function testInvalidDataFails(): void
    {
        // Arrange
        $validator = new RequestValidator([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $validator->validate([
            'name' => 'John',
            // missing email
        ]);
    }
}
```

### Testing Exceptions

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    public function testThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error message');
        
        throw new \RuntimeException('Error message');
    }
}
```

## Test Best Practices

### 1. Name Tests Clearly

```php
public function test_user_can_register_with_valid_email(): void
{
    // Test name describes expected behavior
}
```

### 2. Arrange-Act-Assert

```php
public function test_example(): void
{
    // Arrange - set up
    $user = new User();
    
    // Act - do something
    $result = $user->save();
    
    // Assert - verify
    $this->assertNotNull($result);
}
```

### 3. Test One Thing

```php
// Good - one assertion per test
public function test_user_has_name(): void
{
    $this->assertSame('John', $user->name);
}

// Good - separate tests for different behaviors
public function test_user_can_have_null_email(): void {}
public function test_user_email_must_be_unique(): void {}
```

### 4. Use Data Providers

```php
/**
 * @dataProvider userNameProvider
 */
public function testUserNameValidation(string $name, bool $valid): void
{
    // Test with multiple values
}

public function userNameProvider(): array
{
    return [
        ['John', true],
        ['', false],
        ['A', false],
    ];
}
```

## Mocking

### Using PHPUnit Mocks

```php
public function testServiceIsCalled(): void
{
    // Create mock
    $mock = $this->createMock(Service::class);
    
    // Expect method call
    $mock->expects($this->once())
        ->method('doSomething')
        ->willReturn('result');
    
    // Use mock
    $result = $mock->doSomething();
    
    $this->assertSame('result', $result);
}
```

### Using Stubs

```php
public function testReturnsCachedData(): void
{
    // Create stub
    $stub = $this->createStub(CacheInterface::class);
    $stub->method('get')
        ->willReturn(['cached' => 'data']);
    
    // Use stub
    $result = $stub->get('key');
    
    $this->assertSame(['cached' => 'data'], $result);
}
```

## CI Integration

### GitHub Actions

```yaml
# .github/workflows/test.yml
name: Test

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          
      - name: Install dependencies
        run: composer install --no-interaction
        
      - name: Run tests
        run: composer test
        
      - name: Run static analysis
        run: composer stan
```

## Running Specific Test Suites

```bash
# Unit tests only
vendor/bin/phpunit --testsuite unit

# Integration tests
vendor/bin/phpunit --testsuite integration
```

## Code Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage/

# Minimum coverage
vendor/bin/phpunit --coverage-min=80
```

## Related

- [Deployment](deployment.md) - Production deployment
- [Troubleshooting](troubleshooting.md) - Common issues
- [Application](application.md) - More guides