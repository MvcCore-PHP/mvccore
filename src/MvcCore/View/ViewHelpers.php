<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\View;

/**
 * @mixin \MvcCore\View
 */
trait ViewHelpers {

	/**
	 * @inheritDocs
	 * @return string
	 */
	public static function GetHelpersDir () {
		return static::$helpersDir;
	}

	/**
	 * @inheritDocs
	 * @param  string $helpersDir
	 * @return string
	 */
	public static function SetHelpersDir ($helpersDir = 'Helpers') {
		return static::$helpersDir = $helpersDir;
	}

	/**
	 * @inheritDocs
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function AddHelpersNamespaces ($helperNamespaces) {
		if (!static::$helpersNamespaces) self::initHelpersNamespaces();
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * @inheritDocs
	 * @param  string $helperNamespaces,... View helper classes namespace(s).
	 * @return void
	 */
	public static function SetHelpersNamespaces ($helperNamespaces) {
		static::$helpersNamespaces = [];
		$args = func_get_args();
		if (count($args) === 1 && is_array($args[0])) $args = $args[0];
		foreach ($args as $arg)
			static::$helpersNamespaces[] = '\\' . trim($arg, '\\') . '\\';
	}

	/**
	 * @inheritDocs
	 * @param  string $method            View helper method name in pascal case.
	 * @param  mixed  $arguments         View helper method arguments.
	 * @throws \InvalidArgumentException If view doesn't exist in configured namespaces.
	 * @return string|mixed              View helper string result or any other view helper result type or view helper instance, always as `\MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper` instance.
	 */
	public function __call ($method, $arguments) {
		$result = '';
		$methodCamelCase = lcfirst($method);
		$instance = & $this->GetHelper($methodCamelCase, TRUE);
		$isObject = is_object($instance);
		if ($instance instanceof \Closure || ($isObject && method_exists($instance, '__invoke'))) {
			$result = call_user_func_array($instance, $arguments);
		} else if ($isObject && method_exists($instance, $method)) {
			$result = call_user_func_array([$instance, $method], $arguments);
		} else {
			throw new \InvalidArgumentException(
				"[".get_class()."] View class instance has no method '{$method}', no view helper found."
			);
		}
		$this->__protected['helpers'][$methodCamelCase] = & $instance;
		return $result;
	}

	/**
	 * @inheritDocs
	 * @param  string $helperNameCamelCase View helper method name in camel case.
	 * @param  bool   $asClosure           Get View helper prepared as closure function, `FALSE` by default.
	 * @throws \InvalidArgumentException   If view doesn't exist in configured namespaces.
	 * @return \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed View helper instance.
	 */
	public function & GetHelper ($helperNameCamelCase, $asClosure = FALSE) {
		$setUpView = FALSE;
		$needsClosureFn = FALSE;
		$instance = NULL;
		$helpers = & $this->__protected['helpers'];
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		if (isset($helpers[$helperNameCamelCase])) {
			$instance = & $helpers[$helperNameCamelCase];
		} else if (isset(self::$globalHelpers[$helperNamePascalCase])) {
			$globalHelpersRecord = & self::$globalHelpers[$helperNamePascalCase];
			$instance = & $globalHelpersRecord[0];
			//$result = & $instance;
			$setUpView = $globalHelpersRecord[1];
			$needsClosureFn = $globalHelpersRecord[2];
		} else {
			$helperFound = FALSE;
			$toolClass = self::$toolClass ?: self::$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
			if (!static::$helpersNamespaces)
				self::initHelpersNamespaces();
			foreach (static::$helpersNamespaces as $helperClassBase) {
				$className = $helperClassBase . $helperNamePascalCase . 'Helper';
				if (!class_exists($className))
					continue;
				$helperFound = TRUE;
				if ($toolClass::CheckClassInterface($className, $helpersInterface, TRUE, FALSE)) {
					$setUpView = TRUE;
					$instance = $className::GetInstance();
				} else {
					$instance = new $className();
				}
				$needsClosureFn = (
					!($instance instanceof \Closure) &&
					!method_exists($className, '__invoke')
				);
				self::$globalHelpers[$helperNamePascalCase] = [& $instance, $setUpView, $needsClosureFn];
				break;
			}
			if (!$helperFound)
				throw new \InvalidArgumentException(
					"[".get_class()."] View helper method '{$helperNamePascalCase}' is not"
					." possible to handle by any configured view helper (View"
					." helper namespaces: '".implode("', '", static::$helpersNamespaces)."')."
				);
		}
		if ($setUpView)
			$instance->SetView($this);
		if ($needsClosureFn) {
			$result = function () use (& $instance, $helperNamePascalCase) {
				return call_user_func_array([$instance, $helperNamePascalCase], func_get_args());
			};
			$helpers[$helperNameCamelCase] = & $result;
		} else {
			$helpers[$helperNameCamelCase] = & $instance;
			$result = & $instance;
		}
		if ($asClosure) {
			return $result;
		} else {
			return $instance;
		}
	}

	/**
	 * @inheritDocs
	 * @param  string                                                                                      $helperNameCamelCase
	 *                                                                                                     View helper method name in camel case.
	 * @param  \MvcCore\Ext\Views\Helpers\AbstractHelper|\MvcCore\Ext\Views\Helpers\IHelper|\Closure|mixed $instance
	 *                                                                                                     View helper instance.
	 * @param  bool                                                                                        $forAllTemplates
	 *                                                                                                     Register this helper instance for all rendered views in the future.
	 * @return \MvcCore\View
	 */
	public function SetHelper ($helperNameCamelCase, $instance, $forAllTemplates = TRUE) {
		$implementsIHelper = FALSE;
		$toolClass = self::$toolClass ?: self::$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$helpersInterface = self::HELPERS_INTERFACE_CLASS_NAME;
		$className = get_class($instance);
		$implementsIHelper = $toolClass::CheckClassInterface($className, $helpersInterface, FALSE, FALSE);
		$helperNamePascalCase = ucfirst($helperNameCamelCase);
		$needsClosureFn = (
			!($instance instanceof \Closure) &&
			!method_exists($className, '__invoke')
		);
		if ($forAllTemplates)
			self::$globalHelpers[ucfirst($helperNameCamelCase)] = [
				& $instance, $implementsIHelper, $needsClosureFn
			];
		$helpers = & $this->__protected['helpers'];
		if ($needsClosureFn) {
			$helpers[$helperNameCamelCase] = function () use (& $instance, $helperNamePascalCase) {
				return call_user_func_array([$instance, $helperNamePascalCase], func_get_args());
			};
		} else {
			$helpers[$helperNameCamelCase] = & $instance;
		}
		return $this;
	}
}
