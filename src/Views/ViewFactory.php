<?php

namespace Marwa\App\Views;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ViewFactory
{
    protected $twig;

    public function __construct($path)
    {
        $loader = new FilesystemLoader($path);
        $this->twig = new Environment($loader, [
            'cache' => env('VIEW_CACHE', false),
            'auto_reload' => true,
        ]);
    }

    public function addExtension($extension)
    {
        $this->twig->addExtension($extension);
    }

    public function make($view, array $data = [])
    {
        return $this->twig->render($view . '.twig', $data);
    }

    public function getEngine()
    {
        return $this->twig;
    }
}
