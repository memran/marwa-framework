<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Entity\Validation\ErrorBag;
use Marwa\Framework\Application;
use Marwa\Framework\Validation\FormRequest;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Validation\ValidationException;
use Marwa\Router\Http\RequestFactory;
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
        new Application($this->basePath);

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

        $validator = new RequestValidator();
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

        $formRequest = new class ($request, new RequestValidator()) extends FormRequest {
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
        ], $formRequest->validated());
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

        $response = (new ValidationException($errors, ['title' => '']))->toResponse($request);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://example.test/form', $response->getHeaderLine('Location'));
        self::assertSame(['title' => ''], session(ValidationException::OLD_INPUT_KEY));
        self::assertSame([
            'title' => ['The title field is required.'],
        ], session(ValidationException::ERROR_BAG_KEY));
        self::assertSame(['title' => ''], old());
        self::assertSame('', old('title'));
    }
}
