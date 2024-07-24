<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_SECURE_') or die('Forbidden');

/**
 * Replacement for addslashes()
 *
 * @param string|array $data simple variable or array of variables
 * @return string|array
 */
function core_addslashes($data)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_addslashes($val);
		}
	} else {
		$data = addslashes($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Replacement for stripslashes()
 *
 * @param string|array $data simple variable or array of variables
 * @return string|array
 */
function core_stripslashes($data)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_stripslashes($val);
		}
	} else {
		$data = stripslashes($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Replacement for htmlspecialchars()
 *
 * @param string|array $data simple variable or array of variables
 * @return string|array
 */
function core_htmlspecialchars($data)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_htmlspecialchars($val);
		}
	} else {
		$data = htmlspecialchars($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Replacement for htmlspecialchars_decode()
 *
 * @param string|array $data simple variable or array of variables
 * @return string|array
 */
function core_htmlspecialchars_decode($data)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_htmlspecialchars_decode($val);
		}
	} else {
		$data = htmlspecialchars_decode($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Set the language for the user, if it's no defined just leave it with the default
 *
 * @param string $var_username Username
 */
function core_setuserlang($username = "")
{
	global $core_config;
	$c_lang_module = core_lang_get();
	$db_query = "SELECT language_module FROM " . _DB_PREF_ . "_tblUser WHERE flag_deleted='0' AND username='$username'";
	$db_result = dba_query($db_query);
	$db_row = dba_fetch_array($db_result);
	if (trim($db_row['language_module'])) {
		$c_lang_module = $db_row['language_module'];
	}
	if (defined('LC_MESSAGES')) {

		// linux
		setlocale(LC_MESSAGES, $c_lang_module, $c_lang_module . '.utf8', $c_lang_module . '.utf-8', $c_lang_module . '.UTF8', $c_lang_module . '.UTF-8');
	} else {

		// windows
		putenv('LC_ALL={' . $c_lang_module . '}');
	}
}

// fixme anton
// enforced to declare function _() for gettext replacement if no PHP gettext extension found
// it is also possible to completely remove gettext and change multi-lang with translation array
if (!function_exists('_')) {

	function _($text)
	{
		return $text;
	}
}

function core_sanitize_query($var)
{
	$var = str_replace("/", "", (string) $var);
	$var = str_replace("|", "", $var);
	$var = str_replace("\\", "", $var);
	$var = str_replace("\"", "", $var);
	$var = str_replace('\'', "", $var);
	$var = str_replace("..", "", $var);
	$var = strip_tags($var);
	return $var;
}

function core_sanitize_path($var)
{
	$var = str_replace("|", "", (string) $var);
	$var = str_replace("..", "", $var);
	$var = strip_tags($var);
	return $var;
}

/**
 * Sanitize filenames
 */
function core_sanitize_filename($string)
{
	$string = trim(preg_replace('/[^\p{L}\p{N}\s._-]+/u', '', $string));

	return $string;
}


function core_hook($c_plugin, $c_function, $c_param = array())
{
	$c_fn = $c_plugin . '_hook_' . $c_function;
	if ($c_plugin && $c_function && function_exists($c_fn)) {
		return call_user_func_array($c_fn, $c_param);
	}
}

/**
 * Call function that hook caller function
 *
 * @global array $core_config
 * @param string $function_name        
 * @param array $arguments        
 * @return string|int|bool|array
 */
function core_call_hook($function_name = '', $arguments = array())
{
	global $core_config;
	$ret = NULL;
	if (!$function_name) {
		if (_PHP_VER_ >= 50400) {
			$f = debug_backtrace(0, 2);

			// PHP 5.4.0 and above
		} else {
			$f = debug_backtrace();

			// PHP prior to 5.4.0
		}
		$function_name = $f[1]['function'];
		$arguments = $f[1]['args'];
	}
	if (isset($core_config['plugins']['list']['feature']) && is_array($core_config['plugins']['list']['feature'])) {
		for ($c = 0; $c < count($core_config['plugins']['list']['feature']); $c++) {
			if (isset($core_config['plugins']['list']['feature'][$c])) {
				if ($ret = core_hook($core_config['plugins']['list']['feature'][$c], $function_name, $arguments)) {
					break;
				}
			}
		}
	}
	return $ret;
}

function playsmsd()
{

	// plugin feature
	core_call_hook();

	// plugin gateway
	$smscs = gateway_getall_smsc_names();
	foreach ( $smscs as $smsc ) {
		$smsc_data = gateway_get_smscbyname($smsc);
		$gateways[] = $smsc_data['gateway'];
	}
	if (is_array($gateways)) {
		$gateways = array_unique($gateways);
		foreach ( $gateways as $gateway ) {
			core_hook($gateway, 'playsmsd');
		}
	}

	// plugin themes
	core_hook(core_themes_get(), 'playsmsd');
}

function playsmsd_once($param)
{

	// plugin feature
	core_call_hook();

	// plugin gateway
	$smscs = gateway_getall_smsc_names();
	foreach ( $smscs as $smsc ) {
		$smsc_data = gateway_get_smscbyname($smsc);
		$gateways[] = $smsc_data['gateway'];
	}
	if (is_array($gateways)) {
		$gateways = array_unique($gateways);
		foreach ( $gateways as $gateway ) {
			core_hook(
				$gateway,
				'playsmsd_once',
				array(
					$param
				)
			);
		}
	}

	// plugin themes
	core_hook(
		core_themes_get(),
		'playsmsd_once',
		array(
			$param
		)
	);
}

function core_str2hex($string)
{
	$hex = '';
	$len = strlen($string);
	for ($i = 0; $i < $len; $i++) {
		$hex .= str_pad(dechex(ord($string[$i])), 2, 0, STR_PAD_LEFT);
	}
	return $hex;
}

/**
 * Purify input
 * 
 * @param string $input input
 * @param string $type text or HTML
 * @return string purified input
 */
function core_purify($input, $type = 'text')
{
	// type of output, text or other
	$type = strtolower(trim($type));

	// decode HTML special chars
	$output = htmlspecialchars_decode($input);

	// remove php tags
	$output = str_ireplace('<?php', '', $output);
	$output = str_ireplace('<?', '', $output);
	$output = str_ireplace('?>', '', $output);
	$output = str_ireplace('`', '', $output);

	// purify it
	$config = HTMLPurifier_Config::createDefault();
	$config->set('Cache.DefinitionImpl', null);

	if ($type == 'text') {

		// if type is text then do not allow any HTML tags
		// for non-text type default purifier config will be used
		$config->set('HTML.AllowedElements', '');
		$config->set('HTML.AllowedAttributes', '');
	}

	$hp = new HTMLPurifier($config);

	$output = $hp->purify($output);

	return $output;
}

/**
 * Format input for safe HTML display on the web
 *
 * @param string|array $data HTML input
 * @return string|array safe HTML
 */
function core_display_html($data)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_display_html($val);
		}
	} else {
		$data = htmlspecialchars_decode($data);

		$data = core_stripslashes(trim($data));

		$data = core_purify($data, 'html');

		$data = htmlspecialchars($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Format text for safe display on the web
 *
 * @param string|array $data original text
 * @param int $len length of text
 * @return string|array safe text
 */
function core_display_text($data, $len = 0)
{
	if (is_array($data)) {
		$ret = [];
		foreach ( $data as $key => $val ) {
			$ret[$key] = core_display_text($val, $len);
		}
	} else {
		$data = htmlspecialchars_decode($data);

		$data = stripslashes(trim($data));

		$data = core_purify($data, 'text');

		$data = $len > 0 ? substr($data, 0, $len) . '..' : $data;

		$data = htmlspecialchars($data);

		$ret = $data;
	}

	return $ret;
}

/**
 * Sanitize untrusted user input
 *
 * @param string|array $data untrusted user input
 * @param string $type text or html format
 * @return string|array safe user input
 */
function core_sanitize_inputs($data, $type = 'text')
{
	$type = strtolower(trim($type));

	if ($type == 'text') {
		$data = core_display_text($data);
	} else {
		$data = core_display_html($data);
	}

	// consider input already sanitized by above function
	// then revert back htmlspecialchars()
	$data = core_htmlspecialchars_decode($data);

	// consider input is coming from web, we need to addslashes it
	$data = core_addslashes($data);

	return $data;
}

/**
 * Format $data for safe display on the web
 * 
 * @param string|array $data original $data
 * @param int $len length of text
 * @return string|array formatted $data
 */
function core_display_data($data, $len = 0)
{
	return core_display_text($data, $len);
}

/**
 * Fetch $_POST, $_GET, $_COOKIE or $_REQUEST safe HTML value for selected key
 * 
 * @param string $key
 * @return mixed
 */
function core_safe_html($key, $type = 'post')
{
	$type = strtolower(trim($type));

	switch ($type) {
		case 'post':
			return isset($_POST[_SAFE_HTML_KEY_]) && isset($_POST[_SAFE_HTML_KEY_][$key]) ? $_POST[_SAFE_HTML_KEY_][$key] : null;
		case 'get':
			return isset($_GET[_SAFE_HTML_KEY_]) && isset($_GET[_SAFE_HTML_KEY_][$key]) ? $_GET[_SAFE_HTML_KEY_][$key] : null;
		case 'cookie':
			return isset($_COOKIE[_SAFE_HTML_KEY_]) && isset($_COOKIE[_SAFE_HTML_KEY_][$key]) ? $_COOKIE[_SAFE_HTML_KEY_][$key] : null;
		case 'request':
			return isset($_REQUEST[_SAFE_HTML_KEY_]) && isset($_REQUEST[_SAFE_HTML_KEY_][$key]) ? $_REQUEST[_SAFE_HTML_KEY_][$key] : null;
		default:
			return null;
	}
}

/**
 * Fetch $_POST safe HTML value for selected key
 * 
 * @param string $key
 * @return mixed
 */
function core_safe_html_post($key)
{
	return core_safe_html($key, 'post');
}

/**
 * Convert timestamp to datetime in UTC
 *
 * @param $timestamp timestamp        
 * @return string current date and time
 */
function core_convert_datetime($timestamp)
{
	global $core_config;
	$tz = core_get_timezone();
	$ret = date($core_config['datetime']['format'], $timestamp);
	return $ret;
}

/**
 * Get current server date and time in GMT+0
 *
 * @return string current date and time
 */
function core_get_datetime()
{
	global $core_config;
	$tz = core_get_timezone();
	$dt = date($core_config['datetime']['format'], time());
	$ret = core_adjust_datetime($dt, $tz);
	return $ret;
}

/**
 * Get current server date in GMT+0
 *
 * @return string current date
 */
function core_get_date()
{
	$ret = core_get_datetime();
	$arr = explode(' ', $ret);
	$ret = $arr[0];
	return $ret;
}

/**
 * Get current server time in GMT+0
 *
 * @return string current time
 */
function core_get_time()
{
	$ret = core_get_datetime();
	$arr = explode(' ', $ret);
	$ret = $arr[1];
	return $ret;
}

/**
 * Get timezone
 *
 * @param $username username
 *        or empty for default timezone
 * @return string timezone
 */
function core_get_timezone($username = '')
{
	global $core_config;
	$ret = '';
	if ($username) {
		$list = dba_search(
			_DB_PREF_ . '_tblUser',
			'datetime_timezone',
			array(
				'flag_deleted' => 0,
				'username' => $username
			)
		);
		$ret = $list[0]['datetime_timezone'];
	}
	if (!$ret) {
		$ret = $core_config['main']['gateway_timezone'];
	}
	return $ret;
}

/**
 * Calculate timezone string into number of seconds offset
 *
 * @param $tz timezone        
 * @return string offset in number of seconds
 */
function core_datetime_offset($tz = 0)
{
	$n = (int) $tz;
	$m = $n % 100;
	$h = ($n - $m) / 100;
	$num = ($h * 3600) + ($m * 60);
	return ($num ? $num : 0);
}

/**
 * Format and adjust date/time from GMT+0 to user's timezone for web display purposes
 *
 * @param $time date/time        
 * @param $tz timezone        
 * @return string formatted date/time with adjusted timezone
 */
function core_display_datetime($time, $tz = 0)
{
	global $core_config, $user_config;
	$time = trim($time);
	$ret = $time;
	if ($time && ($time != '0000-00-00 00:00:00')) {
		if (!$tz) {
			if (!($tz = $user_config['datetime_timezone'])) {
				$tz = $core_config['main']['gateway_timezone'];
			}
		}
		$time = strtotime($time);
		$off = core_datetime_offset($tz);

		// the difference between core_display_datetime() and core_adjust_datetime()
		// core_display_datetime() will set to user's timezone (+offset)
		$ret = $time + $off;
		$ret = date($core_config['datetime']['format'], $ret);
	}
	return $ret;
}

/**
 * Format text to proper date/time format
 *
 * @param string $text        
 * @return string
 */
function core_format_datetime($text)
{
	global $core_config;

	$ts = strtotime($text);
	$ret = date($core_config['datetime']['format'], $ts);

	return $ret;
}

/**
 * Format and adjust date/time to GMT+0 for log or incoming SMS saving purposes
 *
 * @param $time date/time        
 * @param $tz timezone        
 * @return string formatted date/time with adjusted timezone
 */
function core_adjust_datetime($time, $tz = 0)
{
	global $core_config, $user_config;
	$time = trim($time);
	$ret = $time;
	if ($time && ($time != '0000-00-00 00:00:00')) {
		if (!$tz) {
			if (!($tz = $user_config['datetime_timezone'])) {
				$tz = $core_config['main']['gateway_timezone'];
			}
		}
		$time = strtotime($time);
		$off = core_datetime_offset($tz);

		// the difference between core_display_datetime() and core_adjust_datetime()
		// core_adjust_datetime() will set to GTM+0 (-offset)
		$ret = $time - $off;
		$ret = date($core_config['datetime']['format'], $ret);
	}
	return $ret;
}

/**
 * Format float to proper credit format
 *
 * @param float $float
 * @return string
 */
function core_display_credit($float)
{
	$credit = number_format((float) $float, 2, '.', '');

	return $credit;
}

/**
 * Generates a new string, for example a new password
 */
function core_get_random_string($length = 16, $valid_chars = '')
{
	$valid_chars = str_replace(' ', '', $valid_chars);
	if (!$valid_chars) {
		$valid_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()1234567890";
	}

	$valid_char_len = strlen($valid_chars);
	$result = "";
	for ($i = 0; $i < $length; $i++) {
		$index = mt_rand(0, $valid_char_len - 1);
		$result .= $valid_chars[$index];
	}
	return $result;
}

/**
 * Sanitize username
 */
function core_sanitize_username($username)
{
	$username = preg_replace("/[^a-z\d._-]/i", '', $username);

	return $username;
}

/**
 * Sanitize to alpha-numeric only
 */
function core_sanitize_alphanumeric($string)
{
	// $text = preg_replace("/[^A-Za-z0-9]/", '', $text);
	$string = trim(preg_replace('/[^\p{L}\p{N}]+/u', '', $string));

	return $string;
}

/**
 * Sanitize to alpha only
 */
function core_sanitize_alpha($string)
{
	// $text = preg_replace("/[^A-Za-z]/", '', $text);
	$string = trim(preg_replace('/[^\p{L}]+/u', '', $string));

	return $string;
}

/**
 * Sanitize to numeric only
 */
function core_sanitize_numeric($string)
{
	// $text = preg_replace("/[^0-9]/", '', $text);
	$string = trim(preg_replace('/[^\p{N}]+/u', '', $string));

	return $string;
}

/**
 * Sanitize HTML and PHP tags
 */
function core_sanitize_string($string)
{
	$string = trim(strip_tags($string));

	return $string;
}

/**
 * Sanitize SMS sender
 */
function core_sanitize_sender($string)
{
	// $string = core_sanitize_alphanumeric($string);
	// allows alphanumeric, space, dash, underscore
	$string = trim(preg_replace('/[^\p{L}\p{N}]\s-_+/u', '', $string));
	$string = substr($string, 0, 16);
	if (preg_match('/[^\p{L}\p{N}]\s-_+/u', $string) == TRUE) {
		$string = substr($string, 0, 11);
	}

	return $string;
}

/**
 * Sanitize SMS footer
 */
function core_sanitize_footer($text)
{
	$text = str_replace('"', "'", $text);
	if (strlen($text) > 30) {
		$text = substr($text, 0, 30);
	}

	return $text;
}

/**
 * Function: core_net_match()
 * ref: https://github.com/mlocati/ip-lib
 *
 * This function returns a boolean value.
 * Usage: core_net_match("IP RANGE", "IP ADDRESS")
 * 
 * @param string $network Network
 * @param string $ip IP to be checked within network
 * @return bool
 */
function core_net_match($network, $ip)
{
	$network = trim($network);
	$ip = trim($ip);

	if ($network && $ip && class_exists('\IPLib\Factory')) {

		// don't match with network that starts with asterisk or 0
		// to prevent matches with *.*.*.* or 0.0.0.0
		if (preg_match('/^[\*0]/', $network)) {
			_log('match all range is not allowed network:' . $network . ' ip:' . $ip, 2, 'core_net_match');

			return false;
		}

		try {
			$address = \IPLib\Factory::parseAddressString($ip);
			$range = \IPLib\Factory::parseRangeString($network);

			if (!is_object($address)) {
				_log('invalid remote network:' . $network . ' ip:' . $ip, 3, 'core_net_match');

				return false;
			}

			if (!is_object($range)) {
				_log('invalid range network:' . $network . ' ip:' . $ip, 3, 'core_net_match');

				return false;
			}

			if ($address->matches($range)) {
				_log('found match remote is in range network:' . $network . ' ip:' . $ip, 3, 'core_net_match');

				return true;
			} else {
				_log('match not found remote is not in range network:' . $network . ' ip:' . $ip, 3, 'core_net_match');

				return false;
			}
		} catch (Exception $e) {
			_log('exception network:' . $network . ' ip:' . $ip . ' error:' . $e->getMessage(), 2, 'core_net_match');

			return false;
		}
	} else {
		return false;
	}
}

/**
 * Function: core_string_to_gsm()
 * This function encodes an UTF-8 string into GSM 03.38
 * Since UTF-8 is largely ASCII compatible, and GSM 03.38 is somewhat compatible, unnecessary conversions are removed.
 * Specials chars such as € can be encoded by using an escape char \x1B in front of a backwards compatible (similar) char.
 * UTF-8 chars which doesn't have a GSM 03.38 equivalent is replaced with a question mark.
 * UTF-8 continuation bytes (\x08-\xBF) are replaced when encountered in their valid places, but
 * any continuation bytes outside of a valid UTF-8 sequence is not processed.
 * Based on https://github.com/onlinecity/php-smpp
 *
 * @param string $string        
 * @return string
 */
function core_string_to_gsm($string)
{
	$dict = array(
		'@' => "\x00",
		'£' => "\x01",
		'$' => "\x02",
		'¥' => "\x03",
		'è' => "\x04",
		'é' => "\x05",
		'ù' => "\x06",
		'ì' => "\x07",
		'ò' => "\x08",
		'Ç' => "\x09",
		'Ø' => "\x0B",
		'ø' => "\x0C",
		'Å' => "\x0E",
		'å' => "\x0F",
		'Δ' => "\x10",
		'_' => "\x11",
		'Φ' => "\x12",
		'Γ' => "\x13",
		'Λ' => "\x14",
		'Ω' => "\x15",
		'Π' => "\x16",
		'Ψ' => "\x17",
		'Σ' => "\x18",
		'Θ' => "\x19",
		'Ξ' => "\x1A",
		'Æ' => "\x1C",
		'æ' => "\x1D",
		'ß' => "\x1E",
		'É' => "\x1F",

		// all \x2? removed
		// all \x3? removed
		// all \x4? removed
		'Ä' => "\x5B",
		'Ö' => "\x5C",
		'Ñ' => "\x5D",
		'Ü' => "\x5E",
		'§' => "\x5F",
		'¿' => "\x60",
		'ä' => "\x7B",
		'ö' => "\x7C",
		'ñ' => "\x7D",
		'ü' => "\x7E",
		'à' => "\x7F",
		'^' => "\x1B\x14",
		'{' => "\x1B\x28",
		'}' => "\x1B\x29",
		'\\' => "\x1B\x2F",
		'[' => "\x1B\x3C",
		'~' => "\x1B\x3D",
		']' => "\x1B\x3E",
		'|' => "\x1B\x40",
		'€' => "\x1B\x65"
	);

	// '
	$converted = strtr($string, $dict);
	return $converted;
}

/**
 * Function: core_detect_unicode()
 * This function returns an boolean indicating if string needs to be converted to utf
 * to be send as an SMS
 *
 * @param $text string
 *        to check
 * @return int unicode
 */
function core_detect_unicode($text)
{
	$unicode = 0;
	$textgsm = core_string_to_gsm($text);

	$match = preg_match_all('/([\\xC0-\\xDF].)|([\\xE0-\\xEF]..)|([\\xF0-\\xFF]...)/m', $textgsm, $matches);
	if ($match !== FALSE) {
		if ($match == 0) {
			$unicode = 0;
		} else {
			$unicode = 1;
		}
	} else {

		//TODO broken regexp in this case, warn user
	}
	return $unicode;
}

/**
 * SMS strlen() based on unicode status
 *
 * @param string $text        
 * @param string $encoding        
 * @return integer Length of text
 */
function core_smslen($text, $encoding = "")
{
	if (function_exists('mb_strlen') && core_detect_unicode($text)) {
		if ($encoding = trim($encoding)) {
			$len = mb_strlen($text, $encoding);
		} else {
			$len = mb_strlen($text, "UTF-8");
		}
	} else if (core_detect_unicode($text)) {
		$len = strlen(utf8_decode($text));
	} else {
		$len = strlen($text);
	}

	return (int) $len;
}

/**
 * Function: array_to_xml()
 * ref: http://stackoverflow.com/a/3289602 (onokazu)
 *
 * This function returns an xml format of an array
 * Usage: core_array_to_xml(ARRAY, SimpleXMLElement OBJECT)
 * @param array $arr
 * @param SimpleXMLElement $xml
 * @return SimpleXMLElement
 */
function core_array_to_xml($arr, SimpleXMLElement $xml)
{
	foreach ( $arr as $k => $v ) {
		if (is_numeric($k)) {
			$k = 'item';
		}
		if (is_array($v)) {
			core_array_to_xml($v, $xml->addChild($k));
		} else {
			$xml->addChild($k, $v);
		}
	}

	return $xml;
}

/**
 * XML to array using SimpleXML
 */
function core_xml_to_array($xml)
{
	$loaded = simplexml_load_string($xml);
	$json = json_encode($loaded);
	$var = json_decode($json, TRUE);

	return $var;
}

/**
 * Object to array
 */
function core_object_to_array($data)
{
	if (is_object($data)) {
		$result = array();
		foreach ( (array) $data as $key => $value ) {
			$result[$key] = core_object_to_array($value);
		}

		return $result;
	}

	return $data;
}

/**
 * Convert array to CSV formatted string
 *
 * @param array $item        
 * @return string
 */
function core_csv_format($item)
{
	$ret = '';

	foreach ( $item as $row ) {

		$entry = '';
		foreach ( $row as $field ) {

			$field = str_replace('"', "'", $field);
			$entry .= '"' . $field . '",';
		}
		$entry = substr($entry, 0, -1);

		$ret .= $entry . "\n";
	}

	return $ret;
}

/**
 * Download content as a file
 *
 * @param string $content        
 * @param string $fn        
 * @param string $content_type        
 * @param string $charset        
 * @param string $content_encoding        
 * @param string $convert_encoding_to        
 */
function core_download($content, $fn = '', $content_type = '', $charset = '', $content_encoding = '', $convert_encoding_to = '')
{
	$fn = ($fn ? $fn : 'download.txt');
	$content_type = (trim($content_type) ? strtolower(trim($content_type)) : 'text/plain');
	$charset = strtolower(trim($charset));

	ob_end_clean();
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	if ($content_encoding) {
		header('Content-Encoding: ' . $content_encoding);
	}
	if ($charset) {
		header('Content-Type: ' . $content_type . '; charset=' . $charset);
	} else {
		header('Content-Type: ' . $content_type);
	}
	header('Content-Disposition: attachment; filename=' . $fn);

	if ($convert_encoding_to) {
		if (function_exists('iconv')) {
			$content = iconv($convert_encoding_to, $content_encoding, $content);
		} else if (function_exists('mb_convert_encoding')) {
			$content = mb_convert_encoding($content, $convert_encoding_to, $content_encoding);
		}
	}

	_p($content);
	die();
}

/**
 * Get default SMSC
 *
 * @global array $core_config
 * @return string
 */
function core_smsc_get()
{
	global $core_config;

	$ret = core_call_hook();
	if (!$ret) {
		return $core_config['main']['gateway_module'];
	}

	return $ret;
}

/**
 * Get default gateway based on default SMSC
 *
 * @global array $core_config
 * @return string
 */
function core_gateway_get()
{
	global $core_config;

	$ret = core_call_hook();
	if (!$ret) {
		$smsc = core_smsc_get();
		$smsc_data = gateway_get_smscbyname($smsc);
		$gateway = $smsc_data['gateway'];
		return $gateway;
	}

	return $ret;
}

/**
 * Get active language
 *
 * @global array $core_config
 * @return string
 */
function core_lang_get()
{
	global $core_config, $user_config;

	$ret = core_call_hook();
	if (!$ret) {
		return ($user_config['language_module'] ? $user_config['language_module'] : $core_config['main']['language_module']);
	}

	return $ret;
}

/**
 * Get active themes
 *
 * @global array $core_config
 * @return string
 */
function core_themes_get()
{
	global $core_config;

	$ret = core_call_hook();
	if (!$ret) {
		return $core_config['main']['themes_module'];
	}

	return $ret;
}

/**
 * Get status of plugin, loaded or not
 *
 * @param integer $uid        
 * @param string $plugin_category        
 * @param string $plugin_name        
 * @return boolean
 */
function core_plugin_get_status($uid, $plugin_category, $plugin_name)
{
	$ret = FALSE;

	// check config.php and fn.php
	$plugin_category = core_sanitize_path($plugin_category);
	$plugin_name = core_sanitize_path($plugin_name);
	$fn_cnf = _APPS_PATH_PLUG_ . '/' . $plugin_category . '/' . $plugin_name . '/config.php';
	$fn_lib = _APPS_PATH_PLUG_ . '/' . $plugin_category . '/' . $plugin_name . '/fn.php';
	if (file_exists($fn_cnf) && $fn_lib) {

		// check plugin_status registry
		$status = registry_search($uid, $plugin_category, $plugin_name, 'enabled');

		// $status = 1 for disabled
		// $status = 2 for enabled
		if ($status == 2) {
			$ret = TRUE;
		}
	}

	return $ret;
}

/**
 * Set status of plugin
 *
 * @param integer $uid        
 * @param string $plugin_category        
 * @param string $plugin_name        
 * @param boolean $plugin_status        
 * @return boolean
 */
function core_plugin_set_status($uid, $plugin_category, $plugin_name, $plugin_status)
{
	$ret = FALSE;
	$status = core_plugin_get_status($uid, $plugin_category, $plugin_name);
	if ((($status == 2) && $plugin_status) || ($status == 1 && (!$plugin_status))) {
		$ret = TRUE;
	} else {
		$plugin_status = ($plugin_status ? 2 : 1);
		$items = array(
			'enabled' => $plugin_status
		);
		if (registry_update($uid, $plugin_category, $plugin_name, $items)) {
			$ret = TRUE;
		}
	}
	return $ret;
}

/**
 * Set CSRF token value and form
 *
 * @return array array(value, form)
 */
function core_csrf_set()
{
	$ret = array();
	$csrf_token = md5(_PID_ . time());
	if ($_SESSION['X-CSRF-Token'] = $csrf_token) {
		$ret['value'] = $csrf_token;
		$ret['form'] = '<input type="hidden" name="X-CSRF-Token" value="' . $csrf_token . '">';
	}

	//_log('token:'.$csrf_token, 3, 'core_csrf_set');
	return $ret;
}

/**
 * Set CSRF token
 *
 * @return string
 */
function core_csrf_set_token()
{
	$csrf_token = md5(_PID_ . time());
	if ($_SESSION['X-CSRF-Token'] = $csrf_token) {
		$ret = $csrf_token;
	}

	//_log('token:'.$csrf_token, 3, 'core_csrf_set_token');
	return $ret;
}

/**
 * Get CSRF token value and form
 *
 * @return array array(value, form)
 */
function core_csrf_get()
{
	$ret = array();
	if ($csrf_token = $_SESSION['X-CSRF-Token']) {
		$ret['value'] = $csrf_token;
		$ret['form'] = '<input type="hidden" name="X-CSRF-Token" value="' . $csrf_token . '">';
	}

	//_log('token:'.$csrf_token, 3, 'core_csrf_get');
	return $ret;
}

/**
 * Get CSRF token
 *
 * @return string token
 */
function core_csrf_get_token()
{
	if ($csrf_token = $_SESSION['X-CSRF-Token']) {
		$ret = $csrf_token;
	}

	//_log('token:'.$csrf_token, 3, 'core_csrf_get_token');
	return $ret;
}

/**
 * Validate CSRF token
 *
 * @return boolean
 */
function core_csrf_validate()
{
	$submitted_token = $_POST['X-CSRF-Token'];
	$token = core_csrf_get_token();

	//_log('token:'.$token.' submitted_token:'.$submitted_token, 3, 'core_csrf_validate');
	if ($token && $submitted_token && ($token == $submitted_token)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Get playSMS version
 *
 * @return string
 */
function core_get_version()
{
	$version = registry_search(1, 'core', 'config', 'playsms_version');
	if ($version = $version['core']['config']['playsms_version']) {
		return $version;
	} else {
		return '';
	}
}

/**
 * Print output
 *
 * @return string
 */
function core_print($content)
{
	global $core_config;
	echo $content;

	return $content;
}

/**
 * Check playSMS daemon timer
 *
 * Usage:
 * if (! core_playsmsd_timer(40)) {
 * return;
 * }
 *
 * // do below commands every 40 seconds
 * ...
 * ...
 *
 * @param integer $period
 *        Period between last event and now (in second)
 * @return boolean TRUE for period passed
 */
function core_playsmsd_timer($period = 60)
{

	// default period is 60 seconds
	$period = ((int) $period <= 0 ? 60 : (int) $period);

	$now = time();
	$next = floor(($now / $period)) * $period + $period;
	if (($now + 1) < $next) {

		// it is not the time yet
		return FALSE;
	} else {

		// its passed the timer period
		return TRUE;
	}
}

/**
 * Get mobile format for matching purposes
 *
 * @param string $mobile        
 * @return mixed
 */
function core_mobile_matcher_format($mobile)
{
	// sanitize for mobile numbers only
	$c_mobile = sendsms_getvalidnumber($mobile);

	if (strlen($c_mobile) >= 6) {
		// remove +
		$c_mobile = str_replace('+', '', $c_mobile);

		// remove first 3 digits if phone number length more than 7
		if (strlen($c_mobile) > 7) {
			$c_mobile = substr($c_mobile, 3);
		}

		$mobile = $c_mobile;
	}

	return $mobile;
}

/**
 * Get last submitted $_POST data
 *
 * @param string $key        
 * @return mixed
 */
function core_last_post_get($key = '')
{
	$ret = '';

	$key = trim($key);
	if ($key) {
		$ret = $_SESSION['tmp']['last_post'][md5(trim(_APP_ . _INC_ . _ROUTE_))][$key];
	} else {
		$ret = $_SESSION['tmp']['last_post'][md5(trim(_APP_ . _INC_ . _ROUTE_))];
	}

	return $ret;
}

/**
 * Empty last submitted $_POST data
 *
 * @return boolean TRUE
 */
function core_last_post_empty()
{
	$_SESSION['tmp']['last_post'] = array();

	return TRUE;
}

/**
 * Include composer based packages
 */
if (file_exists(_APPS_PATH_LIBS_ . '/composer/vendor/autoload.php')) {
	include_once _APPS_PATH_LIBS_ . '/composer/vendor/autoload.php';
} else {
	die(_('FATAL ERROR') . ' : ' . _('Unable to find composer files') . ' ' . _('Please run composer.phar update'));
}

/**
 * Include core functions on plugin core
 */

$pc = 'core';

$dir = _APPS_PATH_PLUG_ . '/' . $pc . '/';
unset($core_config['plugins']['list'][$pc]);
unset($tmp_core_config['plugins']['list'][$pc]);
$fd = opendir($dir);
$pc_names = array();
while (false !== ($pl_name = readdir($fd))) {

	// plugin's dir prefixed with dot or underscore will not be loaded
	if (substr($pl_name, 0, 1) != "." && substr($pl_name, 0, 1) != "_") {
		$pc_names[] = $pl_name;
	}
}
closedir();

sort($pc_names);
for ($j = 0; $j < count($pc_names); $j++) {
	if (is_dir($dir . $pc_names[$j])) {
		$core_config['plugins']['list'][$pc][] = $pc_names[$j];
	}
}

foreach ( $core_config['plugins']['list'][$pc] as $pl ) {
	$c_fn1 = $dir . '/' . $pl . '/config.php';
	$c_fn2 = $dir . '/' . $pl . '/fn.php';
	if (file_exists($c_fn1) && file_exists($c_fn2)) {
		// config.php
		include $c_fn1;

		// fn.php
		include_once $c_fn2;
	}
}

// load shortcuts
include_once $core_config['apps_path']['libs'] . "/fn_shortcuts.php";
