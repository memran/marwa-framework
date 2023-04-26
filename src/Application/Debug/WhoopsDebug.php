<?php


namespace Marwa\Application\Debug;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

class WhoopsDebug implements DebugInterface
{
    /**
     * @var
     */
    protected $_debug;

    /**
     * WhoopsDebug constructor.
     */
    public function __construct()
    {
        //enable all error
        error_reporting(E_ALL);
    }

    /**
     *
     */
    public function enable() : void
    {
        $this->_debug = new Run();
        $this->_debug->pushHandler(new PrettyPageHandler);
        $this->_debug->register();
    }
}
