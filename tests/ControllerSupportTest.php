<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests;

use Marwa\Framework\Application;
use Marwa\Framework\Tests\Fixtures\Controllers\InspectableController;
use Marwa\Framework\Validation\FormRequest;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Framework\Validation\ValidationException;
use Marwa\Router\Http\Input;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerSupportTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-controller-' . bin2hex(random_bytes(6));
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views', 0777, true);
        file_put_contents($this->basePath . '/resources/views/show.twig', 'Hello {{ name }}');
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=test-suite-secret\nTIMEZONE=UTC\n");

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        Input::reset();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        @unlink($this->basePath . '/resources/views/show.twig');
        @rmdir($this->basePath . '/resources/views');
        @unlink($this->basePath . '/.env');
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

    public function testControllerHelpersRenderValidateAndRedirect(): void
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
                'title' => 'Hello Framework',
                'count' => '2',
            ]
        );

        $app->add(ServerRequestInterface::class, $request);
        $app->add('request', $request);
        Input::setRequest($request);

        $controller = new InspectableController();

        self::assertSame('Hello Framework', $controller->requestValue('title'));
        self::assertSame('Hello Framework', $controller->inputValue('title'));
        self::assertSame([
            'title' => 'Hello Framework',
            'count' => '2',
        ], $controller->inputValue());

        self::assertSame([
            'title' => 'Hello Framework',
            'count' => 2,
        ], $controller->validateValue([
            'title' => 'required|string',
            'count' => 'integer',
        ]));

        $response = $controller->renderView('show', ['name' => 'Alice']);
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('Hello Alice', (string) $response->getBody());

        $json = $controller->jsonValue(['ok' => true]);
        self::assertSame('{"ok":true}', (string) $json->getBody());
        self::assertSame('http://example.test/posts', $controller->backValue()->getHeaderLine('Location'));
    }

    public function testControllerFlashesOldInputAndErrors(): void
    {
        new Application($this->basePath);
        Input::setRequest(
            RequestFactory::fromArrays(
                [
                    'REQUEST_METHOD' => 'POST',
                    'REQUEST_URI' => '/posts',
                    'HTTP_HOST' => 'example.test',
                ],
                [],
                ['title' => '']
            )
        );
        $controller = new InspectableController();

        $controller->withInputValue(['title' => '']);
        $controller->withErrorsValue(['title' => 'The title field is required.']);

        self::assertSame(['title' => ''], session(ValidationException::OLD_INPUT_KEY));
        self::assertSame([
            'title' => ['The title field is required.'],
        ], session(ValidationException::ERROR_BAG_KEY));
        self::assertSame(['title' => ''], old());
        self::assertSame('', old('title'));
    }

    public function testControllerAuthorizationHelpersReturnResponses(): void
    {
        new Application($this->basePath);
        $controller = new InspectableController();

        self::assertNull($controller->authorizeValue(true));
        self::assertInstanceOf(ResponseInterface::class, $controller->authorizeValue(false));
        self::assertSame(403, $controller->authorizeValue(false)->getStatusCode());
        self::assertSame(401, $controller->abortIfValue(true, 'Unauthorized', 401)->getStatusCode());
        self::assertNull($controller->abortUnlessValue(true));
    }

    public function testValidatedValueCanConsumeAFormRequest(): void
    {
        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts',
                'HTTP_HOST' => 'example.test',
            ],
            [],
            ['name' => ' Alice ']
        );

        $formRequest = new class ($request, new RequestValidator()) extends FormRequest {
            public function rules(): array
            {
                return ['name' => 'trim|required|string'];
            }

            protected function prepareForValidation(array $input): array
            {
                $input['name'] = trim((string) ($input['name'] ?? ''));

                return $input;
            }
        };

        $controller = new InspectableController();

        self::assertSame(['name' => 'Alice'], $controller->validatedValue($formRequest));
    }
}
