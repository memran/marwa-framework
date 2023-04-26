<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Facades;

abstract class Facade
{
    /**
     * [protected description] app instance
     *
     * @var [type]
     */
    protected static $app;

    /**
     * [setApplication description] it will setup application instance
     *
     * @param [type] $app [description]
     */
    public static function setApplication($app)
    {
          static::$app = $app;
    }
    /**
     * [getApplication description] it will return app instance
     *
     * @return [type] [description]
     */
    protected static function getApplication()
    {
          return static::$app;
    }
    /**
     * [getInstance description] it will return class object from container
     *
     * @return [type] [description]
     */
    protected static function getInstance()
    {
        //get class alias
        $alias = static::getClassAlias();

        //if alias is not null
        if($alias !=null ) {
            return static::getApplication()->get($alias);
        }
        throw new Exception('Class not found on container');
    }

    /**
     * [getClassAlias description] it will overrides and return class aliase as string
     *
     * @return [type] [description]
     */
    protected static function getClassAlias()
    {
          throw new Exception('Class Alias did not setup');
    }

    /**
     * [__callStatic description] statically class method calls
     *
     * @param  [type] $method [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public static function __callStatic($method,$params)
    {
        $instance = static::getInstance();
        if(!is_null($instance)) {
            return $instance->$method(...$params);
        }
    }
}
