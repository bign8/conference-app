<?php

$forbidden = array(
	'error' => array(
		'code' => 403,
		'status' => 'Forbidden'
	)
);

// TODO security
$dsn = 'sqlite:' . implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'php', 'db.db'));

// N8-ADD: only allow these actions and provide these fields on these tables
$whitelist = array(
	'user' => array(
		'actions' => array('GET'),
		'fields' => array('userID', 'name', 'title', 'firm', 'phone', 'photo', 'bio', 'email', 'seen')
	),
);
// N8-END

/**
* The MIT License
* http://creativecommons.org/licenses/MIT/
*
* ArrestDB 1.8.0 (github.com/alixaxel/ArrestDB/)
* Copyright (c) 2014 Alix Axel <alix.axel@gmail.com>
**/

if (strcmp(PHP_SAPI, 'cli') === 0) exit('ArrestDB should not be run from CLI.' . PHP_EOL);

if (ArrestDB::Query($dsn) === false) {
	$result = array(
		'error' => array(
			'code' => 503,
			'status' => 'Service Unavailable',
		),
	);
	
	exit(ArrestDB::Reply($result));
}

if (array_key_exists('_method', $_GET) === true) {
	$_SERVER['REQUEST_METHOD'] = strtoupper(trim($_GET['_method']));
} else if (array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE', $_SERVER) === true) {
	$_SERVER['REQUEST_METHOD'] = strtoupper(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));
}

ArrestDB::Serve('GET', '/(#any)/(#any)/(#any)', function ($table, $id, $data) {
	global $whitelist; // N8-ADD
	$query = array(
		sprintf('SELECT %s FROM "%s"', implode(', ', $whitelist[$table]['fields']), $table), // N8-MOD
		sprintf('WHERE "%s" %s ?', $id, (ctype_digit($data) === true) ? '=' : 'LIKE'),
	);

	if (isset($_GET['by']) === true) {
		if (isset($_GET['order']) !== true) $_GET['order'] = 'ASC';

		$query[] = sprintf('ORDER BY "%s" %s', $_GET['by'], $_GET['order']);
	}

	if (isset($_GET['limit']) === true) {
		$query[] = sprintf('LIMIT %u', $_GET['limit']);

		if (isset($_GET['offset']) === true) $query[] = sprintf('OFFSET %u', $_GET['offset']);
	}

	$query = sprintf('%s;', implode(' ', $query));
	$result = ArrestDB::Query($query, $data);

	if ($result === false) {
		$result = array(
			'error' => array(
				'code' => 404,
				'status' => 'Not Found',
			),
		);
	} else if (empty($result) === true) {
		$result = array(
			'error' => array(
				'code' => 204,
				'status' => 'No Content',
			),
		);
	}

	return ArrestDB::Reply($result);
});

ArrestDB::Serve('GET', '/(#any)/(#num)?', function ($table, $id = null) {
	global $whitelist; // N8-ADD
	$query = array(
		sprintf('SELECT %s FROM "%s"', implode(', ', $whitelist[$table]['fields']), $table), // N8-MOD
	);

	if (isset($id) === true) {
		$query[] = sprintf('WHERE "%s" = ? LIMIT 1', $table.'ID');
	} else {
		if (isset($_GET['by']) === true) {
			if (isset($_GET['order']) !== true) $_GET['order'] = 'ASC';

			$query[] = sprintf('ORDER BY "%s" %s', $_GET['by'], $_GET['order']);
		}

		if (isset($_GET['limit']) === true) {
			$query[] = sprintf('LIMIT %u', $_GET['limit']);

			if (isset($_GET['offset']) === true) $query[] = sprintf('OFFSET %u', $_GET['offset']);
		}
	}

	$query = sprintf('%s;', implode(' ', $query));
	$result = (isset($id) === true) ? ArrestDB::Query($query, $id) : ArrestDB::Query($query);

	if ($result === false) {
		$result = array(
			'error' => array(
				'code' => 404,
				'status' => 'Not Found',
			),
		);
	} else if (empty($result) === true) {
		$result = array(
			'error' => array(
				'code' => 204,
				'status' => 'No Content',
			),
		);
	} else if (isset($id) === true) {
		$result = array_shift($result);
	}

	return ArrestDB::Reply($result);
});

ArrestDB::Serve('DELETE', '/(#any)/(#num)', function ($table, $id) {
	$query = array(
		sprintf('DELETE FROM "%s" WHERE "%s" = ?', $table, $table.'ID'),
	);

	$query = sprintf('%s;', implode(' ', $query));
	$result = ArrestDB::Query($query, $id);

	if ($result === false) {
		$result = array(
			'error' => array(
				'code' => 404,
				'status' => 'Not Found',
			),
		);
	} else if (empty($result) === true) {
		$result = array(
			'error' => array(
				'code' => 204,
				'status' => 'No Content',
			),
		);
	} else {
		$result = array(
			'success' => array(
				'code' => 200,
				'status' => 'OK',
			),
		);
	}

	return ArrestDB::Reply($result);
});

if (in_array($http = strtoupper($_SERVER['REQUEST_METHOD']), array('POST', 'PUT')) === true) {
	if (preg_match('~^\x78[\x01\x5E\x9C\xDA]~', $data = file_get_contents('php://input')) > 0) $data = gzuncompress($data);

	if ((array_key_exists('CONTENT_TYPE', $_SERVER) === true) && (empty($data) !== true)) {
		$_SERVER['CONTENT_TYPE'] = strtok($_SERVER['CONTENT_TYPE'], ';');;
		if (strcasecmp($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
			$GLOBALS['_' . $http] = json_decode($data, true);
		} else if ((strcasecmp($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') === 0) && (strcasecmp($_SERVER['REQUEST_METHOD'], 'PUT') === 0)) {
			parse_str($data, $GLOBALS['_' . $http]);
		}
	}

	if ((isset($GLOBALS['_' . $http]) !== true) || (is_array($GLOBALS['_' . $http]) !== true)) $GLOBALS['_' . $http] = array();

	unset($data);
}

ArrestDB::Serve('POST', '/(#any)', function ($table) {
	if (empty($_POST) === true) {
		$result = array(
			'error' => array(
				'code' => 204,
				'status' => 'No Content',
			),
		);
	} else if (is_array($_POST) === true) {
		$queries = array();

		if (count($_POST) == count($_POST, COUNT_RECURSIVE)) $_POST = array($_POST);

		foreach ($_POST as $row) {
			$data = array();

			foreach ($row as $key => $value) $data[sprintf('"%s"', $key)] = $value;

			$query = array(
				sprintf('INSERT INTO "%s" (%s) VALUES (%s)', $table, implode(', ', array_keys($data)), implode(', ', array_fill(0, count($data), '?'))),
			);

			$queries[] = array(
				sprintf('%s;', implode(' ', $query)),
				$data,
			);
		}

		if (count($queries) > 1) {
			ArrestDB::Query()->beginTransaction();

			while (is_null($query = array_shift($queries)) !== true) {
				if (($result = ArrestDB::Query($query[0], $query[1])) === false) ArrestDB::Query()->rollBack(); break;
			}

			if (($result !== false) && (ArrestDB::Query()->inTransaction() === true)) $result = ArrestDB::Query()->commit();
		} else if (is_null($query = array_shift($queries)) !== true) {
			$result = ArrestDB::Query($query[0], $query[1]);
		}

		if ($result === false) {
			$result = array(
				'error' => array(
					'code' => 409,
					'status' => 'Conflict',
				),
			);
		} else {
			$result = array(
				'success' => array(
					'code' => 201,
					'status' => 'Created',
					'data' => $result,
				),
			);
		}
	}

	return ArrestDB::Reply($result);
});

ArrestDB::Serve('PUT', '/(#any)/(#num)', function ($table, $id) {
	if (empty($GLOBALS['_PUT']) === true) {
		$result = array(
			'error' => array(
				'code' => 204,
				'status' => 'No Content',
			),
		);
	} else if (is_array($GLOBALS['_PUT']) === true) {
		$data = array();

		foreach ($GLOBALS['_PUT'] as $key => $value) $data[$key] = sprintf('"%s" = ?', $key);

		$query = array(
			sprintf('UPDATE "%s" SET %s WHERE "%s" = ?', $table, implode(', ', $data), $table.'ID'),
		);

		$query = sprintf('%s;', implode(' ', $query));
		$result = ArrestDB::Query($query, $GLOBALS['_PUT'], $id);

		if ($result === false) {
			$result = array(
				'error' => array(
					'code' => 409,
					'status' => 'Conflict',
				),
			);
		} else {
			$result = array(
				'success' => array(
					'code' => 200,
					'status' => 'OK',
				),
			);
		}
	}

	return ArrestDB::Reply($result);
});

$result = array(
	'error' => array(
		'code' => 400,
		'status' => 'Bad Request',
	),
);

exit(ArrestDB::Reply($result));

class ArrestDB {
	public static function Query($query = null) {
		static $db = null;
		static $result = array();

		try {
			if (isset($db, $query) === true) {
				if (strncasecmp($db->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql', 5) === 0) $query = strtr($query, '"', '`');

				if (empty($result[$hash = crc32($query)]) === true) $result[$hash] = $db->prepare($query);
				

				$data = array_slice(func_get_args(), 1);

				if (count($data, COUNT_RECURSIVE) > count($data)) 
					$data = iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($data)), false);

				if ($result[$hash]->execute($data) === true) {
					$sequence = null;

					if ((strncmp($db->getAttribute(\PDO::ATTR_DRIVER_NAME), 'pgsql', 5) === 0) && (sscanf($query, 'INSERT INTO %s', $sequence) > 0)) {
						$sequence = sprintf('%s_id_seq', trim($sequence, '"'));
					}

					switch (strstr($query, ' ', true)) {
						case 'INSERT':
						case 'REPLACE':
							return $db->lastInsertId($sequence);

						case 'UPDATE':
						case 'DELETE':
							return $result[$hash]->rowCount();

						case 'SELECT':
						case 'EXPLAIN':
						case 'PRAGMA':
						case 'SHOW':
							return $result[$hash]->fetchAll();
					}

					return true;
				}

				return false;
			} else if (isset($query) === true) {
				$options = array(
					PDO::ATTR_CASE => PDO::CASE_NATURAL,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
					PDO::ATTR_STRINGIFY_FETCHES => false,
				);

				if (preg_match("~^sqlite:([[:print:]]++)$~i", $query, $dsn) > 0) {
					$options += array(
						PDO::ATTR_TIMEOUT => 3,
					);

					$db = new PDO(sprintf('sqlite:%s', $dsn[1]), null, null, $options);
					$pragmas = array(
						'automatic_index' => 'ON',
						'cache_size' => '8192',
						'foreign_keys' => 'ON',
						'journal_size_limit' => '67110000',
						'locking_mode' => 'NORMAL',
						'page_size' => '4096',
						'recursive_triggers' => 'ON',
						'secure_delete' => 'ON',
						'synchronous' => 'NORMAL',
						'temp_store' => 'MEMORY',
						'journal_mode' => 'WAL',
						'wal_autocheckpoint' => '4096',
					);

					if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
						$memory = 131072;

						if (($page = intval(shell_exec('getconf PAGESIZE'))) > 0) $pragmas['page_size'] = $page;

						if (is_readable('/proc/meminfo') === true) {
							if (is_resource($handle = fopen('/proc/meminfo', 'rb')) === true) {
								while (($line = fgets($handle, 1024)) !== false) {
									if (sscanf($line, 'MemTotal: %d kB', $memory) == 1) {
										$memory = round($memory / 131072) * 131072; break;
									}
								}

								fclose($handle);
							}
						}

						$pragmas['cache_size'] = intval($memory * 0.25 / ($pragmas['page_size'] / 1024));
						$pragmas['wal_autocheckpoint'] = $pragmas['cache_size'] / 2;
					}

					foreach ($pragmas as $key => $value) $db->exec(sprintf('PRAGMA %s=%s;', $key, $value));
				} else if (preg_match('~^(mysql|pgsql)://(?:(.+?)(?::(.+?))?@)?([^/:@]++)(?::(\d++))?/(\w++)/?$~i', $query, $dsn) > 0) {
					if (strncasecmp($query, 'mysql', 5) === 0) {
						$options += array(
							PDO::ATTR_AUTOCOMMIT => true,
							PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8" COLLATE "utf8_general_ci", time_zone = "+00:00";',
							PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
						);
					}

					$db = new PDO(sprintf('%s:host=%s;port=%s;dbname=%s', $dsn[1], $dsn[4], $dsn[5], $dsn[6]), $dsn[2], $dsn[3], $options);
				}
			}
		} catch (Exception $exception) {
			return false;
		}

		return (isset($db) === true) ? $db : false;
	}

	public static function Reply($data) {
		$bitmask = 0;
		$options = array('UNESCAPED_SLASHES', 'UNESCAPED_UNICODE');

		if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) === true) $options[] = 'PRETTY_PRINT';

		foreach ($options as $option) $bitmask |= (defined('JSON_' . $option) === true) ? constant('JSON_' . $option) : 0;

		if (($result = json_encode($data, $bitmask)) !== false) {
			$callback = null;

			if (array_key_exists('callback', $_GET) === true) {
				$callback = trim(preg_replace('~[^[:alnum:]\[\]_.]~', '', $_GET['callback']));

				if (empty($callback) !== true) $result = sprintf('%s(%s);', $callback, $result);
			}

			if (headers_sent() !== true) 
				header(sprintf('Content-Type: application/%s; charset=utf-8', (empty($callback) === true) ? 'json' : 'javascript'));
		}

		return $result;
	}

	public static function Serve($on = null, $route = null, $callback = null) {
		static $root = null;

		if (isset($_SERVER['REQUEST_METHOD']) !== true) $_SERVER['REQUEST_METHOD'] = 'CLI';

		if ((empty($on) === true) || (strcasecmp($_SERVER['REQUEST_METHOD'], $on) === 0)) {
			if (is_null($root) === true) {
				$root = $_SERVER['QUERY_STRING'];
				if (substr($root, -1) != '/') $root .= '/';
			}
			if (preg_match('~^' . str_replace(array('#any', '#num'), array('[^/]++', '[0-9]++'), $route) . '~i', $root, $parts) > 0) {

				// N8-ADD: Can we perform action on table?
				global $whitelist, $forbidden;
				$actions = @$whitelist[$parts[1]]['actions'];
				if (!is_array($actions) || !in_array($on, $actions)) exit(ArrestDB::Reply($forbidden));
				// N8-END

				return (empty($callback) === true) ? true : exit(call_user_func_array($callback, array_slice($parts, 1)));
			}
		}

		return false;
	}
}
