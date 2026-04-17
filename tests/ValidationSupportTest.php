<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Adapters\Validation\FormRequestAdapter;
use Marwa\Framework\Adapters\Validation\RequestValidatorAdapter;
use Marwa\Framework\Adapters\Validation\ValidationExceptionResponder;
use Marwa\Framework\Application;
use Marwa\Framework\Tests\Fixtures\Validation\StartsWithRule;
use Marwa\Router\Http\RequestFactory;
use Marwa\Support\Validation\ErrorBag;
use Marwa\Support\Validation\RuleRegistry;
use Marwa\Support\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ValidationSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-validation-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=test-suite-secret\nTIMEZONE=UTC\n");

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        foreach ([
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath . '/config');
        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_ENV['TIMEZONE'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY'],
            $_SERVER['TIMEZONE']
        );
    }

    public function testValidatorNormalizesCommonTypesAndSupportsHelper(): void
    {
        $app = new Application($this->basePath);

        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts',
                'HTTP_HOST' => 'example.test',
            ],
            [],
            [
                'title' => '  Hello World  ',
                'published' => 'true',
                'age' => '21',
            ]
        );

        $validator = $app->make(RequestValidatorAdapter::class);
        $validated = $validator->validateRequest($request, [
            'title' => 'trim|required|string|min:3',
            'published' => 'boolean',
            'age' => 'integer',
        ]);

        self::assertSame('Hello World', $validated['title']);
        self::assertTrue($validated['published']);
        self::assertSame(21, $validated['age']);
        self::assertSame($validated, validate_request([
            'title' => 'trim|required|string|min:3',
            'published' => 'boolean',
            'age' => 'integer',
        ], request: $request));
    }

    public function testFormRequestSupportsPrepareAndPassedValidationHooks(): void
    {
        $app = new Application($this->basePath);

        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts',
                'HTTP_HOST' => 'example.test',
            ],
            [],
            [
                'title' => '  Hello World  ',
                'published' => '1',
            ]
        );

        $formRequest = new class ($request, $app->make(RequestValidatorAdapter::class)) extends FormRequestAdapter {
            public function rules(): array
            {
                return [
                    'title' => 'required|string|min:3',
                    'published' => 'boolean',
                ];
            }

            protected function prepareForValidation(array $input): array
            {
                $input['title'] = trim((string) ($input['title'] ?? ''));

                return $input;
            }

            protected function passedValidation(array $validated): array
            {
                $validated['slug'] = strtolower(str_replace(' ', '-', $validated['title']));

                return $validated;
            }
        };

        self::assertSame([
            'title' => 'Hello World',
            'published' => true,
            'slug' => 'hello-world',
        ], $formRequest->validate());
    }

    public function testValidationExceptionRedirectsAndFlashesOldInputForHtmlRequests(): void
    {
        $app = new Application($this->basePath);

        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts',
                'HTTP_HOST' => 'example.test',
                'HTTP_REFERER' => 'https://example.test/form',
                'HTTP_ACCEPT' => 'text/html',
            ],
            [],
            [
                'title' => '',
            ]
        );

        $errors = new ErrorBag();
        $errors->add('title', 'The title field is required.');

        $response = (new ValidationExceptionResponder())->toResponse(
            new ValidationException($errors, ['title' => '']),
            $request
        );

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://example.test/form', $response->getHeaderLine('Location'));
        self::assertSame(['title' => ''], session('_old_input'));
        self::assertSame([
            'title' => ['The title field is required.'],
        ], session('errors'));
        self::assertSame(['title' => ''], old());
        self::assertSame('', old('title'));
    }

    public function testValidatorResolvesNamedCustomRules(): void
    {
        $app = new Application($this->basePath);

        $validator = $app->make(RequestValidatorAdapter::class);

        self::assertSame([
            'code' => 'beta',
        ], $validator->validateInputWithCustomRules(
            ['code' => 'beta'],
            ['code' => 'starts_with:b'],
            [],
            [],
            ['starts_with' => StartsWithRule::class]
        ));
    }

    public function testSupportRuleRegistryResolvesAndRegistersCustomRules(): void
    {
        $app = new Application($this->basePath);
        $registry = $app->make(RuleRegistry::class);

        $registry->register('starts_with', StartsWithRule::class);

        self::assertSame('starts_with', $registry->resolve('starts_with', 'b')?->name());
    }

    public function testSupportValidatorHandlesBuiltInValidationRules(): void
    {
        $app = new Application($this->basePath);

        $validated = $app->make(RequestValidatorAdapter::class)->validateInput(
            ['title' => '  Hello  ', 'active' => '1'],
            ['title' => 'trim|required|string', 'active' => 'boolean'],
            [],
            []
        );

        self::assertSame('Hello', $validated['title']);
        self::assertTrue($validated['active']);
    }

    public function testValidatorReportsNamedCustomRuleFailures(): void
    {
        $app = new Application($this->basePath);

        $this->expectException(ValidationException::class);

        $app->make(RequestValidatorAdapter::class)->validateInputWithCustomRules(
            ['code' => 'alpha'],
            ['code' => 'starts_with:b'],
            [],
            [],
            ['starts_with' => StartsWithRule::class]
        );
    }
}
