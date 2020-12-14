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

namespace MvcCore\Model;

trait Props
{
	/**
	 * `\PDO` connection arguments.
	 *
	 * If you need to reconfigure connection string for any other special
	 * `\PDO` database implementation or you specific needs, patch this array
	 * in extended application base model class in base `__construct()` method by:
	 *	 `static::$connectionArguments = array_merge(static::$connectionArguments, array(...));`
	 * or by:
	 *	 `static::$connectionArguments['driverName']['dsn'] = '...';`
	 *
	 * Every key in this field is driver name, so you can use usual `\PDO` drivers:
	 * - `mysql`, `sqlite`, `sqlsrv` (mssql), `firebird`, `ibm`, `informix`, `4D`
	 * Following drivers should be used with defaults, no connection args from here are necessary:
	 * - `oci`, `pgsql`, `cubrid`, `sysbase`, `dblib`
	 *
	 * Every value in this configuration field should be defined as:
	 * - `dsn`		- connection query as first `\PDO` constructor argument
	 *				  with database config replacements.
	 * - `auth`		- if required to use database credentials for connecting or not.
	 * - `fileDb`	- if database if file database or not.
	 * - `options`	. any additional arguments array or empty array.
	 * @var array
	 */
	protected static $connectionArguments = [
		'4D'			=> [
			'dsn'		=> '{driver}:host={host};user={user};password={password};dbname={database};port={port};charset={charset}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['port' => 19812, 'charset' => 'UTF-8',],
		],
		'firebird'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database};charset={charset}',
			'auth'		=> TRUE,
			'fileDb'	=> TRUE,
			'options'	=> [],
			'defaults'	=> ['charset' => 'UTF-8',],
		],
		'ibm'			=> [
			'dsn'		=> 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE={database};HOSTNAME={host};PORT={port};PROTOCOL={protocol};',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['port' => 56789, 'protocol' => 'TCPIP',],
		],
		'informix'		=> [
			'dsn'		=> '{driver}:host={host};service={service};database={database};server={server};protocol={protocol};EnableScrollableCursors=1',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> ['service' => 9800, 'protocol' => 'onsoctcp',],
		],
		'mysql'			=> [
			'dsn'		=> '{driver}:host={host};dbname={database};port={port}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'\PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
			],
			'defaults'	=> ['port' => 3306,],
		],
		'sqlite'		=> [
			'dsn'		=> '{driver}:{database}',
			'auth'		=> FALSE,
			'fileDb'	=> TRUE,
			'options'	=> [],
			'defaults'	=> [],
		],
		'sqlsrv'		=> [
			'dsn'		=> '{driver}:Server={host};Database={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::SQLSRV_ATTR_DIRECT_QUERY'	=> FALSE,
				'\PDO::SQLSRV_ENCODING_UTF8'		=> TRUE,
			],
			'defaults'	=> ['port' => 1433,],
		],
		'default'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
			'defaults'	=> [
				'\PDO::ATTR_TIMEOUT'				=> 30,
				'\PDO::ATTR_EMULATE_PREPARES'		=> TRUE,
				'\PDO::ATTR_ERRMODE'				=> '\PDO::ERRMODE_EXCEPTION',
			],
		],
	];

	/**
	 * System config database configuration root node name, `db` by default.
	 * @var string
	 */
	protected static $systemConfigDbSectionName = 'db';

	/**
	 * System config debug configuration root node name (`debug` by default)
	 * and all it's properties names.
	 * @var string
	 */
	protected static $systemConfigModelProps = [
		'sectionName'	=> 'db',			// db section root node
		'defaultName'	=> 'defaultName',	// default db connection name
		'defaultClass'	=> 'defaultClass',	// custom \PDO implementation full class name
		'name'			=> 'name',			// runtime configuration definition property for connection name
		'index'			=> 'index',			// runtime configuration definition property for connection index
		'driver'		=> 'driver',		// connection driver
		'host'			=> 'host',			// connection host
		'port'			=> 'port',			// connection port
		'user'			=> 'user',			// connection user
		'password'		=> 'password',		// connection password
		'database'		=> 'database',		// connection database
		'options'		=> 'options',		// connection options
		'class'			=> 'class',			// custom connection class full name
	];

	/**
	 * Default database connection name/index, in system config defined in section `db.default = name`.
	 * In extended classes - use this for connection name/index of current model if different.
	 * @var string|int|NULL
	 */
	protected static $defaultConnectionName = NULL;

	/**
	 * Default database connection class name.
	 * @var string
	 */
	protected static $defaultConnectionClass = '\\PDO';

	/**
	 * `\PDO` connections array, keyed by connection indexes from system config.
	 * @var \PDO[]
	 */
	protected static $connections = [];

	/**
	 * Instance of current class, if there is necessary to use it as singleton.
	 * @var \MvcCore\Model[]|\MvcCore\IModel[]
	 */
	protected static $instances = [];

	/**
	 * System config sections array with `\stdClass` objects, keyed by connection indexes.
	 * @var \stdClass[]
	 */
	protected static $configs = NULL;

	/**
	 * Automatically initialize config, db connection and resource class
	 * for classes base on "active record" pattern.
	 * @var bool
	 */
	protected $autoInit = FALSE;

	/**
	 * `\PDO` instance.
	 * @var \PDO
	 */
	protected $connection;

	/**
	 * System config section for database under called connection index in constructor.
	 * @var \stdClass
	 */
	protected $config;

	/**
	 * Resource model class with SQL statements.
	 * @var \MvcCore\Model|\MvcCore\IModel
	 */
	protected $resource;

	/**
	 * Array with values initialized by `SetUp()` method.
	 * Usefull to recognize changed values bafore `Save()`.
	 * @var array
	 */
	protected $initialValues = [];

	/**
	 * Originally declared internal model properties to protect their
	 * possible overwriting by `__set()` or `__get()` magic methods.
	 * Keys are properties names, values are bools, if to serialize their values
	 * or not to.
	 * @var array
	 */
	protected static $protectedProperties = [
		'autoInit'		=> TRUE,
		'connection'	=> FALSE,
		'config'		=> FALSE,
		'resource'		=> FALSE,
		'initialValues'	=> FALSE,
	];
}
