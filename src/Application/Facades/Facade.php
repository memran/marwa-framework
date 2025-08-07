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
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance = [];

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    abstract protected static function getClassAlias(): string;

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
            if(isset(static::$resolvedInstance[$alias])) {
                return static::$resolvedInstance[$alias];

            } else if (static::getApplication()->getContainer()->has($alias)) {
                //if application has alias then return it
                static::$resolvedInstance[$alias] = static::getApplication()->get($alias);
                return static::$resolvedInstance[$alias];
            }else{
                if(class_exists($alias)) {
                    //if class exists then
                    static::$resolvedInstance[$alias] = new $alias();
                    return static::$resolvedInstance[$alias];
                } else {
                    //if class does not exists then throw exception
                    throw new \Exception("Class {$alias} does not exists");
                }
            }
        }
        throw new Exception('Facade alias not set');
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
