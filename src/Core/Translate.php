<?php

namespace Marwa\App\Core;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class Translate
{

    /**
     * @var Translator
     */
    protected static $translator;

    /**
     * Translate constructor.
     */
    private function __construct() {}

    /**
     * @return Translator
     */
    public static function getInstance()
    {
        static::$translator = new Translator('en');
        static::$translator->addLoader('array', new ArrayLoader());
        static::$translator->setFallbackLocales(app()->getFallbackLocale());
        (new self())->loadResource();

        return static::$translator;
    }

    /**
     * Add an array of translations for a given locale and domain.
     */
    public function add(array $messages, string $locale, string $domain = 'messages'): void
    {
        static::$translator->addResource('array', $messages, $locale, $domain);
    }

    /**
     * Translate a key.
     */
    public function trans(string $key, array $replace = [], ?string $locale = null, string $domain = 'messages'): string
    {
        return static::$translator->trans($key, $replace, $domain, $locale);
    }

    /**
     * Change the current locale.
     */
    public function setLocale(string $locale): void
    {
        static::$translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return static::$translator->getLocale();
    }
    /**
     * @return bool
     */
    protected function loadResource()
    {
        $langFiles = $this->getLangMessageFile();
        if (!$langFiles) return false;

        if (is_array($langFiles)) {
            foreach ($langFiles as  $file) {
                $message = include_once($file['name']);

                if (is_array($message)) {
                    static::$translator->addResource('array', $message, $file['lang']);
                }
            }
        }

        return true;
    }

    /**
     *
     */
    protected function getLangMessageFile()
    {
        $files = glob($this->getLangDirectory() . 'messages.*.php');

        if (empty($files)) {
            return false;
        }
        $langFiles = [];
        foreach ($files as $index => $file) {
            [$name, $lang, $extension] = explode('.', basename($file));
            $temArr = ['lang' => $lang, 'name' => $file];
            array_push($langFiles, $temArr);
        }

        return $langFiles;
    }

    /**
     * @return string
     */
    private function getLangDirectory(): string
    {
        return LANG_PATH . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([static::$translator, $name], $arguments);
    }
}
