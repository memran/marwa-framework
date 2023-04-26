<?php


namespace Marwa\Application\Configs;
use Marwa\Application\Configs\Interfaces\ConfigClassInterface;
class PhpArrayClass implements ConfigClassInterface
{
    /**
     * @var array
     */
    private $__data =[];

    /**
     * PhpArrayClass constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $file
     */
    public function setFile(string $file): ConfigClassInterface
    {
        $this->__data = include $file;
        return $this;
    }

    /**
     * @return array
     */
    public function load(): array
    {
        if(!is_array($this->__data)) {
            return (array)$this->__data;
        }

        return $this->__data;
    }
}
