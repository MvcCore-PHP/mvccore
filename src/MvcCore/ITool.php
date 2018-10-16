<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore;

/**
 * Responsibility - static helpers for core classes inheritance, string conversions and JSON.
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static function to check core classes inheritance.
 */
interface ITool
{
	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '');

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @throws \Exception
	 * @return string
	 */
	public static function EncodeJson (& $data);

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * Result has always keys:
	 * - `"success"`	- decoding boolean success
	 * - `"data"`		- decoded json data as stdClass/array
	 * - `"errorData"`	- array with possible decoding error message and error code
	 * @param string $jsonStr
	 * @return object
	 */
	public static function DecodeJson (& $jsonStr);

	/**
	 * Safely invoke internal PHP function with it's own error handler.
	 * Error handler accepts arguments: 
	 * - `string $errMessage`	- Error message.
	 * - `int $errLevel`		- Level of the error raised.
	 * - `string $errFile`		- Optional, full path to error file name where error was raised.
	 * - `int $errLine`			- Optional, The error file line number.
	 * - `array $errContext`	- Optional, array that points to the active symbol table at the 
	 *							  point the error occurred. In other words, errcontext will contain 
	 *							  an array of every variable that existed in the scope the error 
	 *							  was triggered in. User error handler must not modify error context.
	 *							  Warning: This parameter has been DEPRECATED as of PHP 7.2.0. 
	 *							  Relying on it is highly discouraged.
	 * If the custom error handler returns `FALSE`, normal internal error handler continues.
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+:
	 * https://github.com/nette/utils/blob/b623b2deec8729c8285d269ad991a97504f76bd4/src/Utils/Callback.php#L63-L84
	 * @param string $internalFuncName 
	 * @param array $args 
	 * @param callable $onError 
	 * @return mixed
	 */
	public static function Invoke ($internalFuncName, array $args, callable $onError);

	/**
	 * Check if given class implements given interface, else throw an exception.
	 *
	 * @param string $testClassName Full test class name.
	 * @param string $interfaceName Full interface class name.
	 * @param bool $checkStaticMethods Check implementation of all static methods by interface static methods.
	 * @param bool $throwException If `TRUE`, throw an exception if something is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName, $checkStaticMethods = FALSE, $throwException = TRUE);

	/**
	 * Check if given class implements given trait, else throw an exception.
	 * @param string $testClassName Full test class name.
	 * @param string $traitName Full trait class name.
	 * @param bool $throwException If `TRUE`, throw an exception if trait is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassTrait ($testClassName, $traitName, $throwException = TRUE);
}