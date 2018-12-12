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

namespace MvcCore\Route;

trait InternalInits
{
	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...) This 
	 * method is not recommended to use in production mode, it's designed mostly 
	 * for development purposes, to see what could be inside route object.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & InitAll () {
		/** @var $this \MvcCore\Route */
		if ($this->match === NULL && $this->reverse === NULL) {
			$this->initMatchAndReverse();
		} else if ($this->match !== NULL && ($this->reverseParams === NULL || $this->lastPatternParam === NULL)) {
			$this->initReverse();
		}
		return $this;
	}

	/**
	 * Initialize internal properties `match`, `reverse` and other properties
	 * about those values when there is necessary to prepare pattern value for 
	 * PHP `preg_match_all()` route match processing. This method is usually 
	 * called in core request routing process from `\MvcCore\Router::Matches();` 
	 * method on each route. 
	 * @throws \LogicException Route configuration property is missing.
	 * @return void
	 */
	protected function initMatchAndReverse () {
		if ($this->pattern === NULL)
			$this->throwExceptionIfNoPattern();

		$this->lastPatternParam = NULL;
		$match = addcslashes($this->pattern, "#(){}-?!=^$.+|:*\\");
		$reverse = $this->reverse !== NULL
			? $this->reverse
			: $this->pattern;

		list($this->reverseSections, $matchSections) = $this->initSectionsInfoForMatchAndReverse(
			$reverse, $match
		);
		$this->reverse = & $reverse;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);
		$this->initFlagsByPatternOrReverse($reverse);
		$this->match = $this->initMatchComposeRegex(
			$match, $matchSections, $this->reverseParams, $this->constraints
		);
	}

	/**
	 * 
	 * @param string $match 
	 * @param string $reverse 
	 * @return array
	 */
	protected function initSectionsInfoForMatchAndReverse (& $match, & $reverse) {
		$matchInfo = [];
		$reverseInfo = [];
		$reverseIndex = 0;
		$matchIndex = 0;
		$reverseLength = mb_strlen($reverse);
		$matchLength = mb_strlen($match);
		$matchOpenPos = FALSE;
		$matchClosePos = FALSE;
		while ($reverseIndex < $reverseLength ) {
			$reverseOpenPos = mb_strpos($reverse, '[', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, ']', $reverseOpenPos);
				$matchOpenPos = mb_strpos($match, '[', $matchIndex);
				$matchClosePos = mb_strpos($match, ']', $matchOpenPos);
			}
			if ($reverseClosePos === FALSE) {
				$reverseInfo[] = (object) ['fixed' => TRUE, 'start' => $reverseIndex, 'end' => $reverseLength, 'length' => $reverseLength - $reverseIndex];
				$matchInfo[] = (object) ['fixed' => TRUE, 'start' => $matchIndex, 'end' => $matchLength, 'length' => $matchLength - $matchIndex];
				break;
			} else {
				if ($reverseIndex < $reverseOpenPos) {
					$reverseInfo[] = (object) ['fixed' => TRUE, 'start' => $reverseIndex, 'end' => $reverseOpenPos, 'length' => $reverseOpenPos - $reverseIndex];
					$matchInfo[] = (object) ['fixed' => TRUE, 'start' => $matchIndex, 'end' => $matchOpenPos, 'length' => $matchOpenPos - $matchIndex];
				}
				$reverseOpenPosPlusOne = $reverseOpenPos + 1;
				$reverseLocalLength = $reverseClosePos - $reverseOpenPosPlusOne;
				$reverse = mb_substr($reverse, 0, $reverseOpenPos) 
					. mb_substr($reverse, $reverseOpenPosPlusOne, $reverseLocalLength) 
					. mb_substr($reverse, $reverseClosePos + 1);
				$reverseLength -= 2;
				$reverseClosePos -= 1;
				$reverseInfo[] = (object) ['fixed' => FALSE, 'start' => $reverseOpenPos, 'end' => $reverseClosePos, 'length' => $reverseLocalLength];
				$matchOpenPosPlusOne = $matchOpenPos + 1;
				$matchLocalLength = $matchClosePos - $matchOpenPosPlusOne;
				$match = mb_substr($match, 0, $matchOpenPos) 
					. mb_substr($match, $matchOpenPosPlusOne, $matchLocalLength) 
					. mb_substr($match, $matchClosePos + 1);
				$matchLength -= 2;
				$matchClosePos -= 1;
				$matchInfo[] = (object) ['fixed' => FALSE, 'start' => $matchOpenPos, 'end' => $matchClosePos, 'length' => $matchLocalLength];
			}
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return [$matchInfo, $reverseInfo];
	}

	/**
	 * 
	 * @throws \LogicException Route configuration property is missing.
	 * @return void
	 */
	protected function initReverse () {
		$reverse = NULL;
		if ($this->reverse !== NULL) {
			$reverse = $this->reverse;
		} else if ($this->pattern !== NULL) {
			$reverse = $this->pattern;
		} else/* if ($this->pattern === NULL)*/ {
			if ($this->redirect !== NULL) 
				return $this->initFlagsByPatternOrReverse(
					$this->pattern !== NULL 
						? $this->pattern 
						: str_replace(['\\', '(?', ')?', '/?'], '', $this->match)
				);
			$this->throwExceptionIfNoPattern();
		}

		$this->lastPatternParam = NULL;
		
		$this->reverseSections = $this->initSectionsInfo($reverse);
		$this->reverse = $reverse;

		$match = NULL;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);

		$this->initFlagsByPatternOrReverse($reverse);
	}

	/**
	 * 
	 * @param string $pattern 
	 * @return \stdClass[]
	 */
	protected function & initSectionsInfo (& $pattern) {
		$result = [];
		$index = 0;
		$length = mb_strlen($pattern);
		while ($index < $length) {
			$openPos = mb_strpos($pattern, '[', $index);
			$closePos = FALSE;
			if ($openPos !== FALSE) 
				$closePos = mb_strpos($pattern, ']', $openPos);
			if ($closePos === FALSE) {
				$result[] = (object) ['fixed' => TRUE, 'start' => $index, 'end' => $length, 'length' => $length - $index];
				break;
			} else {
				if ($index < $openPos) 
					$result[] = (object) ['fixed' => TRUE, 'start' => $index, 'end' => $openPos, 'length' => $openPos - $index];
				$openPosPlusOne = $openPos + 1;
				$lengthLocal = $closePos - $openPosPlusOne;
				$pattern = mb_substr($pattern, 0, $openPos) 
					. mb_substr($pattern, $openPosPlusOne, $lengthLocal)
					. mb_substr($pattern, $closePos + 1);
				$length -= 2;
				$closePos -= 1;
				$result[] = (object) ['fixed' => FALSE, 'start' => $openPos, 'end' => $closePos, 'length' => $lengthLocal];
			}
			$index = $closePos;
		}
		return $result;
	}

	/**
	 * @param string		$reverse 
	 * @param \stdClass[]	$reverseSectionsInfo 
	 * @param array			$constraints 
	 * @param string|NULL	$match 
	 * @return array		An array with keys as param names and values as  
	 *						`\stdClass` objects with data about reverse params.
	 */
	protected function & initReverseParams (& $reverse, & $reverseSectionsInfo, & $constraints, & $match = NULL) {
		$result = [];
		$completeMatch = $match !== NULL;
		$reverseIndex = 0;
		$matchIndex = 0;
		$sectionIndex = 0;
		$section = $reverseSectionsInfo[$sectionIndex];
		$reverseLength = mb_strlen($reverse);
		$greedyCaught = FALSE;
		$matchOpenPos = -1;
		$matchClosePos = -1;
		$this->lastPatternParam = '';
		while ($reverseIndex < $reverseLength) {
			$reverseOpenPos = mb_strpos($reverse, '<', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, '>', $reverseOpenPos);
				if ($completeMatch) {
					$matchOpenPos = mb_strpos($match, '<', $matchIndex);
					$matchClosePos = mb_strpos($match, '>', $matchOpenPos) + 1;
				}}
			if ($reverseClosePos === FALSE) break;// no other param caught
			// check if param belongs to current section 
			// and if not, move to next (or next...) section
			$reverseClosePos += 1;
			if ($reverseClosePos > $section->end) {
				while (TRUE) {
					$nextSection = $reverseSectionsInfo[$sectionIndex + 1];
					if ($reverseClosePos > $nextSection->end) {
						$sectionIndex += 1;
					} else {
						$sectionIndex += 1;
						$section = $reverseSectionsInfo[$sectionIndex];
						break;
					}}}
			// complete param section length and param name
			$paramLength = $reverseClosePos - $reverseOpenPos;
			$paramName = mb_substr($reverse, $reverseOpenPos + 1, $paramLength - 2);
			list ($greedyFlag, $sectionIsLast) = $this->initReverseParamsGetGreedyInfo(
				$reverseSectionsInfo, $constraints, 
				$paramName, $sectionIndex, $greedyCaught
			);
			if ($greedyFlag && $sectionIsLast) {
				$lastSectionChar = mb_substr(
					$reverse, $reverseClosePos, $reverseSectionsInfo[$sectionIndex]->end - $reverseClosePos
				);
				if ($lastSectionChar == '/') {
					$lastSectionChar = '';
					$reverseSectionsInfo[$sectionIndex]->end -= 1;
				}
				if ($lastSectionChar === '')
					$this->lastPatternParam = $paramName;
			}
			$result[$paramName] = (object) [
				'name'			=> $paramName,
				'greedy'		=> $greedyFlag,
				'sectionIndex'	=> $sectionIndex,
				'length'		=> $paramLength,
				'reverseStart'	=> $reverseOpenPos,
				'reverseEnd'	=> $reverseClosePos,
				'matchStart'	=> $matchOpenPos,
				'matchEnd'		=> $matchClosePos,
			];
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return $result;
	}

	/**
	 * 
	 * @param \stdClass[]	$reverseSectionsInfo 
	 * @param array			$constraints 
	 * @param string		$paramName 
	 * @param int			$sectionIndex 
	 * @param bool			$greedyCaught 
	 * @throws \InvalidArgumentException 
	 * @return \bool[]		Array with two boolean values. First is greedy flag 
	 *						and second is about if section is last or not. The
	 *						second could be `NULL`
	 */
	protected function initReverseParamsGetGreedyInfo (& $reverseSectionsInfo, & $constraints, & $paramName, & $sectionIndex, & $greedyCaught) {
		// complete greedy flag by star character inside param name
		$greedyFlag = mb_strpos($paramName, '*') !== FALSE;
		$sectionIsLast = NULL;
		// check greedy param specifics
		if ($greedyFlag) {
			if ($greedyFlag && $greedyCaught) {
				$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
				throw new \InvalidArgumentException(
					"[".$selfClass."] Route pattern definition can have only one greedy `<param_name*>` "
					." with star (to include everything - all characters and slashes . `.*`) ($this)."
				);
			}
			$reverseSectionsCount = count($reverseSectionsInfo);
			$sectionIndexPlusOne = $sectionIndex + 1;
			if (// next section is optional
				$sectionIndexPlusOne < $reverseSectionsCount &&
				!($reverseSectionsInfo[$sectionIndexPlusOne]->fixed)
			) {
				// check if param is really greedy or not
				$constraintDefined = isset($constraints[$paramName]);
				$constraint = $constraintDefined ? $constraints[$paramName] : NULL ;
				$greedyReal = !$constraintDefined || ($constraintDefined && (
					mb_strpos($constraint, '.*') !== FALSE || mb_strpos($constraint, '.+') !== FALSE
				));
				if ($greedyReal) {
					$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
					throw new \InvalidArgumentException(
						"[".$selfClass."] Route pattern definition can not have greedy `<param_name*>` with star "
						."(to include everything - all characters and slashes . `.*`) immediately before optional "
						."section ($this)."
					);
				}
			}
			$greedyCaught = TRUE;
			$paramName = str_replace('*', '', $paramName);
			$sectionIsLast = $sectionIndexPlusOne === $reverseSectionsCount;
		}
		return [$greedyFlag, $sectionIsLast];
	}

	/**
	 * 
	 * @param string $pattern 
	 * @return void
	 */
	protected function initFlagsByPatternOrReverse ($pattern) {
		$scheme = static::FLAG_SCHEME_NO;
		if (mb_strpos($pattern, '//') === 0) {
			$scheme = static::FLAG_SCHEME_ANY;
		} else if (mb_strpos($pattern, 'http://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTP;
		} else if (mb_strpos($pattern, 'https://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTPS;
		}
		$host = static::FLAG_HOST_NO;
		if ($scheme) {
			if (mb_strpos($pattern, static::PLACEHOLDER_HOST) !== FALSE) {
				$host = static::FLAG_HOST_HOST;
			} else if (mb_strpos($pattern, static::PLACEHOLDER_DOMAIN) !== FALSE) {
				$host = static::FLAG_HOST_DOMAIN;
			} else {
				if (mb_strpos($pattern, static::PLACEHOLDER_TLD) !== FALSE) 
					$host += static::FLAG_HOST_TLD;
				if (mb_strpos($pattern, static::PLACEHOLDER_SLD) !== FALSE) 
					$host += static::FLAG_HOST_SLD;
			}
			if (mb_strpos($pattern, static::PLACEHOLDER_BASEPATH) !== FALSE) 
				$host += static::FLAG_HOST_BASEPATH;
		}
		$queryString = mb_strpos($pattern, '?') !== FALSE 
			? static::FLAG_QUERY_INCL 
			: static::FLAG_QUERY_NO;
		$this->flags = [$scheme, $host, $queryString];
	}
	
	protected function initMatchComposeRegex (& $match, & $matchSectionsInfo, & $reverseParams, & $constraints) {
		$sections = [];
		$paramIndex = 0;
		$reverseParamsKeys = array_keys($reverseParams);
		$paramsCount = count($reverseParamsKeys);
		$anyParams = $paramsCount > 0;
		$defaultPathConstraint = static::$defaultPathConstraint;
		$defaultDomainConstraint = static::$defaultDomainConstraint;
		$schemeFlag = $this->flags[0];
		$matchIsAbsolute = boolval($schemeFlag);
		$firstPathSlashPos = 0;
		if ($matchIsAbsolute) {
			$matchIsAbsolute = TRUE;
			$defaultConstraint = $defaultDomainConstraint;
			// if scheme flag is `http://` or `https://`, there is necessary to increase
			// `mb_strpos()` index by one, because there is always backslash in match pattern 
			// before `:` - like `http\://` or `https\://`
			$firstPathSlashPos = mb_strpos($match, '/', $schemeFlag + ($schemeFlag > static::FLAG_SCHEME_ANY ? 1 : 0));
		} else {
			$defaultConstraint = $defaultPathConstraint;
		}
		$pathFixedSectionsCount = 0;
		$lastPathFixedSectionIndex = 0;
		$trailingSlash = '?';
		$one = $matchIsAbsolute ? 0 : 1;
		$sectionsCountMinusOne = count($matchSectionsInfo) - 1;
		foreach ($matchSectionsInfo as $sectionIndex => $section) {
			$sectionEnd = $section->end;
			if ($anyParams) {
				$sectionOffset = $section->start;
				$sectionResult = '';
				while ($paramIndex < $paramsCount) {
					$paramKey = $reverseParamsKeys[$paramIndex];
					$param = $reverseParams[$paramKey];
					if ($param->sectionIndex !== $sectionIndex) break;
					$paramStart = $param->matchStart;
					if ($matchIsAbsolute && $paramStart > $firstPathSlashPos) 
						$defaultConstraint = $defaultPathConstraint;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($match, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $param->name;
					$customConstraint = isset($constraints[$paramName]);
					if (!$customConstraint && $param->greedy) $defaultConstraint = '.*';
					if ($customConstraint) {
						$constraint = $constraints[$paramName];
					} else {
						$constraint = $defaultConstraint;
					}
					$sectionResult .= '(?<' . $paramName . '>' . $constraint . ')';
					$paramIndex += 1;
					$sectionOffset = $param->matchEnd;
				}
				if ($sectionOffset < $sectionEnd) 
					$sectionResult .= mb_substr($match, $sectionOffset, $sectionEnd - $sectionOffset);
			} else {
				$sectionResult = mb_substr($match, $section->start, $section->length);
			}
			if ($matchIsAbsolute && $sectionEnd > $firstPathSlashPos) $one = 1;
			if ($section->fixed) {
				$pathFixedSectionsCount += $one;
				$lastPathFixedSectionIndex = $sectionIndex;
			} else {
				$sectionResult = '(' . $sectionResult . ')?';
			}
			$sections[] = $sectionResult;
		}
		if ($pathFixedSectionsCount > 0) {
			$lastFixedSectionContent = & $sections[$lastPathFixedSectionIndex];
			if ($sectionsCountMinusOne == 0 && $lastPathFixedSectionIndex == 0 && 
				$lastFixedSectionContent === '/'
			) {
				$trailingSlash = ''; // homepage -> `/`
			} else {
				$lastCharIsSlash = mb_substr($lastFixedSectionContent, -1, 1) == '/';
				if ($lastPathFixedSectionIndex == $sectionsCountMinusOne) {// last section is fixed section
					if (!$lastCharIsSlash) $trailingSlash = '/?';
				} else {// last section is optional section or sections
					$lastFixedSectionContent .= ($lastCharIsSlash ? '' : '/') . '?';
					$trailingSlash = '/?';
				}}}
		return '#^' . implode('', $sections) . $trailingSlash . '$#';
	}

	/**
	 * @throws \LogicException Route configuration property is missing.
	 * @return void
	 */
	protected function throwExceptionIfNoPattern () {
		$selfClass = version_compare(PHP_VERSION, '5.5', '>') ? self::class : __CLASS__;
		throw new \LogicException(
			"[".$selfClass."] Route configuration property `\MvcCore\Route::\$pattern` is missing "
			."to parse it and complete property (or properties) `\MvcCore\Route::\$match` "
			."(and `\MvcCore\Route::\$reverse`) correctly ($this)."
		);
	}

	/**
	 * Render all instance properties values into string.
	 * @return string
	 */
	public function __toString () {
		$type = new \ReflectionClass($this);
		/** @var $props \ReflectionProperty[] */
		$allProps = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE
		);
		$result = [];
		/** @var $prop \ReflectionProperty */
		foreach ($allProps as $prop) {
			if ($prop->isStatic()) continue;
			if ($prop->isPrivate()) $prop->setAccessible(TRUE);
			$value = NULL;
			try {
				$value = $prop->getValue($this);
			} catch (\Exception $e) {};
			$result[] = '"' . $prop->getName() . '":"' . ($value === NULL ? 'NULL' : var_export($value)) . '"';
		}
		return '{'.implode(', ', $result) . '}';
	}
}
