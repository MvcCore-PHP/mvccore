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

namespace MvcCore\Debug;

trait Props
{
	/**
	 * Email recipient to send information about exceptions or errors,
	 * `"admin@localhost"` by default.
	 * @var string
	 */
	public static $EmailRecepient = 'admin@localhost';

	/**
	 * Relative path from app root to store any log information,
	 * `"/Var/Logs"` by default.
	 * @var string
	 */
	public static $LogDirectory = '/Var/Logs';

	/**
	 * Initialize global development shorthands.
	 * @var callable
	 */
	public static $InitGlobalShortHands = [];

	/**
	 * Semaphore to execute `\MvcCore\Debug::Init();` method only once.
	 * `TRUE` if development, `FALSE` if anything else.
	 * @var boolean
	 */
	protected static $development = NULL;

	/**
	 * Debugging and logging handlers, this should be customized in extended class.
	 * @var array
	 */
	protected static $handlers = [
		'timer'				=> 'timerHandler',
		'dump'				=> 'dumpHandler',
		'barDump'			=> 'dumpHandler',
		'log'				=> 'dumpHandler',
		'exceptionHandler'	=> 'exceptionHandler',
		'shutdownHandler'	=> 'ShutdownHandler',
	];

	/**
	 * Store for printed dumps by output buffering to send it at response end.
	 * @var array
	 */
	protected static $dumps = [];

	/**
	 * Store timers start points.
	 * @var array
	 */
	protected static $timers = [];

	/**
	 * `TRUE` for configured debug class as `\MvcCore\Debug`,
	 * `FALSE` for any other configured extension.
	 * @var bool
	 */
	protected static $originalDebugClass = TRUE;

	/**
	 * `TRUE` if debug class is MvcCore original debug class and
	 * if logs directory has been already initialized.
	 * @var bool
	 */
	protected static $logDirectoryInitialized = FALSE;

	/**
	 * Reference to singleton instance in `\MvcCore\Application::GetInstance();`.
	 * @var \MvcCore\Application
	 */
	protected static $app = NULL;

	/**
	 * Reference to `\MvcCore\Application::GetInstance()->GetRequest()->GetMicrotime();`.
	 * @var float
	 */
	protected static $requestBegin;
}