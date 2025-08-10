<?php

namespace Marwa\App\Views;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViewExtensions extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('csrf_field', [$this, 'csrfField'], ['is_safe' => ['html']]),
            new TwigFunction('method_field', [$this, 'methodField'], ['is_safe' => ['html']]),
            new TwigFunction('old', [$this, 'old']),
            new TwigFunction('auth', [$this, 'authCheck']),
            new TwigFunction('asset', [$this, 'asset']),
        ];
    }

    public function csrfField()
    {
        return '<input type="hidden" name="_token" value="' . ($_SESSION['_token'] ?? '') . '">';
    }

    public function methodField($method)
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }

    public function old($key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }

    public function authCheck()
    {
        return isset($_SESSION['user']);
    }

    public function asset($path)
    {
        return BASE_URL . '/assets/' . ltrim($path, '/');
    }
}
