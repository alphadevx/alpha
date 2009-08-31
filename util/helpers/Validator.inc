<?php

/**
 * Generic validation class used throughout the Alpha Framework
 * 
 * @package alpha::util::helpers
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 */
class Validator {
	
	/**
	 * Validate that the provided value is a valid integer
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isInteger($value) {
		return is_int($value);
	}

	/**
	 * Validate that the provided value is a valid double
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isDouble($value) {
		return is_double($value);
	}

	/**
	 * Validate that the provided value is a valid alphabetic string (strictly a-zA-Z)
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isAlpha($value) {
		if (!filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z]+$/"))))
			return false;
		else
			return true;
	}

	/**
	 * Validate that the provided value is a valid alpha-numeric string (strictly a-zA-Z0-9)
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isAlphaNum($value) {
		if (!filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^[a-zA-Z0-9]+$/"))))
			return false;
		else
			return true;
	}

	/**
	 * Validate that the provided value is a valid URL
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isURL($url) {
		if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED))
			return false;
		else
			return true;
	}

	/**
	 * Validate that the provided value is a valid IP address
	 *
	 * @param $value
	 * return boolean
	 */
	public static function isIP($ip) {
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
			return false;
		else
			return true;
	}

	/**
	 * Validate that the provided value is a valid email address
	 *
	 * @param $value
	 * return boolean
	 */
	public function isEmail($email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			return false;
		else
			return true;
	}
}

?>