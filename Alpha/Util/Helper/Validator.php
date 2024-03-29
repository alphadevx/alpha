<?php

namespace Alpha\Util\Helper;

/**
 * Generic validation class used throughout the Alpha Framework.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2021, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 */
class Validator
{
    /**
     * Allows any kind of input, including blanks.
     *
     * @var string
     *
     * @since 1.0
     */
    public const ALLOW_ALL = '/.*/';

    /**
     * Required double value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_DOUBLE = '/^-{0,1}[0-9\.]+$/';

    /**
     * Required integer value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_INTEGER = '/^-{0,1}[0-9]*$/';

    /**
     * Required text value, accepts a maximum of 65536 characters.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_TEXT = '/^[\S]{1}.{0,65535}$/';

    /**
     * Required string value, accepts a maximum of 255 characters.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_STRING = '/^[\S]{1}.{0,254}$/';

    /**
     * Required alphabet string.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_ALPHA = '/^[a-zA-Z]+$/';

    /**
     * Required uppercase alphabet string.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_ALPHA_UPPER = '/^[A-Z]+$/';

    /**
     * Required alpha-numeric string.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_ALPHA_NUMERIC = '/^[a-zA-Z0-9]+$/';

    /**
     * Required HTTP URL value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_HTTP_URL = '/^((http|https):\/\/.*)$/i';

    /**
     * Optional HTTP URL value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const OPTIONAL_HTTP_URL = '/(http|https).*|^$/i';

    /**
     * Required IP address value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_IP = '/^(((([1-9])|([1-9][\d])|(1[\d]{2})|(2[0-4][\d])|(25[0-4]))(\.(([\d])|([1-9][\d])|(1[\d]{2})|(2[0-4][\d])|(25[0-4]))){3})|(0(\.0){3}))$/';

    /**
     * Required email address value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_EMAIL = '/[-_.a-zA-Z0-9]+@((([a-zA-Z0-9]|[-_.a-zA-Z0-9]*[a-zA-Z0-9])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|email|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))/';

    /**
     * Required username (allows a-z A-Z 0-9 and -_. characters).
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_USERNAME = '/^[-_\.a-zA-Z0-9]+$/';

    /**
     * Required sequence value.
     *
     * @var string
     *
     * @since 1.0
     */
    public const REQUIRED_SEQUENCE = '/^[A-Z]*-[0-9]*$/';

    /**
     * Validate that the provided value is a valid integer.
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isInteger($value): bool
    {
        if (preg_match(self::REQUIRED_INTEGER, $value)) {
            return is_numeric($value) ? intval($value) == $value : false;
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid double.
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isDouble($value): bool
    {
        if (preg_match(self::REQUIRED_DOUBLE, $value)) {
            return is_numeric($value) ? doubleval($value) == $value : false;
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid boolean (will accept 1 or 0 as valid booleans).
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isBoolean($value): bool
    {
        $acceptable = array(true, false, 'true', 'false', 1, 0, '1', '0', 'on', 'off');

        if (!in_array($value, $acceptable, true)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate that the provided value is a valid boolean true or not (true, 'true', 1, '1', 'on').
     *
     * @param $value
     *
     * @since 2.0
     */
    public static function isBooleanTrue($value): bool
    {
        $acceptableTrue = array(true, 'true', 1, '1', 'on');

        if (!in_array($value, $acceptableTrue, true)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validate that the provided value is a valid alphabetic string (strictly a-zA-Z).
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isAlpha($value): bool
    {
        if (preg_match(self::REQUIRED_ALPHA, $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid alpha-numeric string (strictly a-zA-Z0-9).
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isAlphaNum($value): bool
    {
        if (preg_match(self::REQUIRED_ALPHA_NUMERIC, $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid Sequence string ([A-Z]-[0-9]).
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isSequence($value): bool
    {
        if (preg_match(self::REQUIRED_SEQUENCE, $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid URL.
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isURL($url): bool
    {
        if (preg_match(self::REQUIRED_HTTP_URL, $url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid IP address.
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isIP($ip): bool
    {
        if (preg_match(self::REQUIRED_IP, $ip)) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is a valid email address.
     *
     * @param $value
     *
     * @since 1.0
     */
    public static function isEmail($email): bool
    {
        if (preg_match(self::REQUIRED_EMAIL, $email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Validate that the provided value is base64 encoded (best guess by regex).
     *
     * @param $value
     *
     * @since 1.2.2
     */
    public static function isBase64($value): bool
    {
        return (bool)preg_match('/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/', $value);
    }

    /**
     * Will return true if the value provided contains any HTML code that is stripable by
     * the native strip_tags() function.
     *
     * @param $value
     *
     * @since 2.0.1
     */
    public static function isHTML($value): bool
    {
        return $value != strip_tags($value) ? true : false;
    }
}
