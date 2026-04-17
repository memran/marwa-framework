# Authentication

Marwa Framework doesn't include built-in authentication, but the framework provides all the building blocks to implement secure authentication. This guide shows how to build custom authentication.

## Session-Based Authentication

### Creating an Authenticator

```php
// app/Services/Authenticator.php
namespace App\Services;

use App\Models\User;
use Marwa\Framework\Contracts\SessionInterface;

class Authenticator
{
    public function __construct(
        private SessionInterface $session
    ) {}

    public function attempt(string $email, string $password): bool
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(User $user): void
    {
        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
        $this->session->set('user_email', $user->email);
    }

    public function logout(): void
    {
        $this->session->invalidate();
    }

    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    public function id(): ?int
    {
        return $this->session->get('user_id');
    }

    public function user(): ?User
    {
        $id = $this->id();

        return $id ? User::find($id) : null;
    }
}
```

### Registering the Authenticator

```php
// app/Providers/AuthServiceProvider.php
use App\Services\Authenticator;
use Marwa\Framework\Adapters\ServiceProviderAdapter;

class AuthServiceProvider extends ServiceProviderAdapter
{
    public function register(): void
    {
        $this->getContainer()->addShared(Authenticator::class, function ($container) {
            return new Authenticator(
                $container->get(\Marwa\Framework\Contracts\SessionInterface::class)
            );
        });
    }
}
```

### Auth Controller

```php
// app/Controllers/AuthController.php
namespace App\Controllers;

use App\Services\Authenticator;
use Marwa\Framework\Http\Request;

class AuthController
{
    public function __construct(
        private Authenticator $auth
    ) {}

    public function showLogin(): mixed
    {
        return view('auth/login');
    }

    public function login(Request $request): mixed
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if ($this->auth->attempt($email, $password)) {
            return redirect('/dashboard');
        }

        return redirect('/login')->with('error', 'Invalid credentials');
    }

    public function logout(): mixed
    {
        $this->auth->logout();

        return redirect('/');
    }
}
```

### Route Protection Middleware

```php
// app/Middleware/Authenticate.php
use App\Services\Authenticator;
use Marwa\Router\Response;

class Authenticate implements \Psr\Http\Server\MiddlewareInterface
{
    public function __construct(
        private Authenticator $auth
    ) {}

    public function process(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {
        if (!$this->auth->check()) {
            return Response::redirect('/login');
        }

        return $handler->handle($request);
    }
}
```

### Login Form View

```html
<!-- resources/views/auth/login.twig -->
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    {% if session('error') %}
        <p style="color: red;">{{ session('error') }}</p>
    {% endif %}

    <form method="POST" action="/login">
        {!! csrf_field() !!}

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</body>
</html>
```

## JWT Authentication

For API-based authentication:

```php
// app/Services/JwtAuth.php
namespace App\Services;

class JwtAuth
{
    public function attempt(string $email, string $password): ?string
    {
        $user = User::where('email', $email)->first();

        if ($user === null || !password_verify($password, $user->password)) {
            return null;
        }

        return $this->generateToken($user);
    }

    public function generateToken(User $user): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub' => $user->id,
            'email' => $user->email,
            'exp' => time() + 3600,
        ]));

        $signature = hash_hmac(
            'sha256',
            "$header.$payload",
            config('app.key'),
            true
        );

        return "$header.$payload." . base64_encode($signature);
    }

    public function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expected = hash_hmac('sha256', "$header.$payload", config('app.key'), true);

        if (!hash_equals(base64_decode($signature), $expected)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);

        if ($data['exp'] < time()) {
            return null;
        }

        return $data;
    }
}
```

### API Auth Controller

```php
// app/Controllers/ApiAuthController.php
class ApiAuthController
{
    public function __construct(
        private JwtAuth $jwt
    ) {}

    public function login(Request $request): mixed
    {
        $token = $this->jwt->attempt(
            $request->input('email'),
            $request->input('password')
        );

        if ($token === null) {
            return Response::json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        return Response::json([
            'token' => $token,
        ]);
    }
}
```

## Security Considerations

1. **Hash passwords** - Always use `password_hash()` and `password_verify()`
2. **Use HTTPS** - Never transmit credentials over plain HTTP
3. **Rate limiting** - Implement login throttling
4. **Session regeneration** - Call `session()->regenerate()` on login
5. **Secure cookies** - Configure `httpOnly` and `secure` flags
6. **CSRF protection** - Use `csrf_field()` in all forms
7. **Token expiration** - Set reasonable token expiry times
