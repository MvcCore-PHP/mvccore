<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Interfaces;

//include_once(__DIR__.'/../Application.php');

/**
 * Responsibility - reading config file(s), detecting environment in system config.
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing ini data into `stdClass|array` by key types or typing
 *     ini values into `int|float|bool|string` for all other detected primitives.
 * - Environment management:
 *   - Simple environment name detection by comparing server and client ip.
 *   - Environment name detection by config records about computer name or ip.
 */
interface IConfig
{
	const ENVIRONMENT_DEVELOPMENT = 'development';
	const ENVIRONMENT_BETA = 'beta';
	const ENVIRONMENT_ALPHA = 'alpha';
	const ENVIRONMENT_PRODUCTION = 'production';

	/**
	 * Static initialization.
	 * - Called when file is loaded into memory.
	 * - First environment value setup - by server and client ip address.
	 * @return void
	 */
	public static function StaticInit ();

	/**
	 * Return `TRUE` if environment is `"development"`.
	 * @return bool
	 */
	public static function IsDevelopment ();

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @return bool
	 */
	public static function IsBeta ();

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @return bool
	 */
	public static function IsAlpha ();

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @return bool
	 */
	public static function IsProduction ();

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\Interfaces\IConfig::ENVIRONMENT_<environment>`.
	 * @return string
	 */
	public static function GetEnvironment ();

	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\Interfaces\IConfig::ENVIRONMENT_<environment>`.
	 * @param string $environment
	 * @return string
	 */
	public static function SetEnvironment ($environment = \MvcCore\Interfaces\IConfig::ENVIRONMENT_PRODUCTION);

	/**
	 * Get system config ini file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetSystem ();

	/**
	 * Load ini file and return parsed configuration or `FALSE` in failure.
	 * - Second environment value setup:
	 *   - Only if `$systemConfig` param is defined as `TRUE`.
	 *   - By defined IPs or computer names in ini `[environments]` section.
	 * - Load only sections for current environment name.
	 * - Retype all `raw string` values into `array`, `float`, `int` or `boolean` types.
	 * - Retype whole values level into `\stdClass`, if there are no numeric keys.
	 * @param string $configPath
	 * @param bool   $systemConfig
	 * @return array|boolean
	 */
	public function & Load ($configPath = '');
}