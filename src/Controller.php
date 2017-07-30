<?php

namespace Sober\Controller;

class Controller
{
	protected $active           = true;
	protected $tree             = false;
	protected $data             = [];
	protected $ignoredPrefixes  = ['_','get'];

	private $class;
	private $methods;

	public function __setup()
	{
		$this->__setClass();
		$this->__setMethods();
		$this->__runMethods();
	}

	/**
	 * Set Class
	 *
	 * Create a ReflectionClass object for this instance
	 */
	private function __setClass()
	{
		$this->class = new \ReflectionClass($this);
	}

	/**
	 * Set Methods
	 *
	 * Set all Class public methods for this instance
	 */
	private function __setMethods()
	{
		$this->methods = $this->class->getMethods(\ReflectionMethod::IS_PUBLIC);
	}

	/**
	 * Set Tree Data
	 *
	 * @return array
	 */
	public function __setTreeData($data)
	{
		if (!$this->class->implementsInterface('\Sober\Controller\Module\Tree') && $this->tree === false) {
			$data = [];
		}
		return $data;
	}

	/**
	 * Is Controller Method
	 *
	 * Return true if the method belongs to the parent class
	 * @return boolean
	 */
	private function __isControllerMethod($method)
	{
		$excls = get_class_methods(__CLASS__);
		$excls[] = '__construct';
		return (in_array($method->name, $excls));
	}

	/**
	 * Is Static Method
	 *
	 * Return true if the method is static
	 * @return boolean
	 */
	private function __isStaticMethod($method)
	{
		$excls = [];
		$statics = $this->class->getMethods(\ReflectionMethod::IS_STATIC);
		foreach ($statics as $static) {
			$excls[] = $static->name;
		}
		return (in_array($method->name, $excls));
	}

	/**
	 * Is Magic Method
	 *
	 * Return true if the method is magical,
	 * and should be left the FUCK alone
	 *
	 * @access private
	 * @param $method
	 * @return bool
	 */
	private function __isMagicMethod($method) {
		return (in_array($method->name, ['__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone', '__debugInfo']));
	}


	/**
	 *
	 */

	private function __isIgnoredMethod($method) {
		return array_reduce(
			 ($this->ignoredPrefixes ?? []),
			function( $matched, $prefix ) use( $method ) {
						return $matched ?: substr( $method, 0, count($prefix) )==$prefix;
					},
			 false
		);
	}

	/**
	 * Sanitize Method
	 *
	 * Change method name from camel case to snake case
	 * @return string
	 */
	private function __sanitizeMethod($method)
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
	}

	/**
	 * Run Methods
	 *
	 * Run and convert each of the child class public methods
	 */
	private function __runMethods()
	{
		foreach ($this->methods as $method) {
			if ($this->__isControllerMethod($method) || $this->__isStaticMethod($method) || $this->__isMagicMethod( $method ) || $this->__isIgnoredMethod( $method ) ) {
				continue;
			}
			$this->data[$this->__sanitizeMethod($method->name)] = $this->{$method->name}();
		}
	}

	/**
	 * Returns Data
	 *
	 * Set the class methods to be run
	 * @return array
	 */
	public function __getData()
	{
		return ($this->active ? $this->data : []);
	}

	/**
	 * __get
	 *
	 * @access
	 * @param $prop
	 * @return mixed|null
	 */
	function __get( $prop )
	{
		return $this->data[$prop] ?? null;
	}

	/**
	 * bindStatic
	 *
	 * @param $methodName
	 * @return \Closure
	 */
	protected static function bindStatic( $methodName )
	{
		$class = get_called_class();
		return function() use ( $class, $methodName )
		{
			if( method_exists($class, $methodName) )
			{
				return $class::$methodName( ...func_get_args() );
			}
			return null;
		};
	}

	/**
	 * employ
	 * called during Loader iteration to
	 * run any required static instantiations
	 */
	public static function employ(){}
}
