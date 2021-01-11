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

namespace MvcCore\Session;

interface IConstants {

	/**
	 * Metadata key in `$_SESSION` storage.
	 * @var string
	 */
	const SESSION_METADATA_KEY = '__MC';

	/**
	 * Default session namespace name.
	 * @var string
	 */
	const DEFAULT_NAMESPACE_NAME = 'default';


	/**
	 * Number of seconds for 1 minute (60).
	 */
	const EXPIRATION_SECONDS_MINUTE	= 60;

	/**
	 * Number of seconds for 1 hour (60 * 60 = 3600).
	 */
	const EXPIRATION_SECONDS_HOUR	= 3600;

	/**
	 * Number of seconds for 1 day (60 * 60 * 24 = 86400).
	 */
	const EXPIRATION_SECONDS_DAY	= 86400;

	/**
	 * Number of seconds for 1 week (60 * 60 * 24 * 7 = 3600).
	 */
	const EXPIRATION_SECONDS_WEEK	= 604800;

	/**
	 * Number of seconds for 1 month, 30 days (60 * 60 * 24 * 30 = 3600).
	 */
	const EXPIRATION_SECONDS_MONTH	= 2592000;

	/**
	 * Number of seconds for 1 year, 365 days (60 * 60 * 24 * 365 = 3600).
	 */
	const EXPIRATION_SECONDS_YEAR	= 31536000;
}