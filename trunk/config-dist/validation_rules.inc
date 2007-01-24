<?php

// $Id$

/**
 *
 * A set of standard validation regualr expressions to be used throughout
 * the application.
 * 
 * @package Alpha Config
 * @author John Collins <john@design-ireland.net>
 * @copyright 2006 John Collins
 * 
 * 
 */
 
// default validation rules for base complex types
define("DEFAULT_BOOLEAN", "/[1|0]/");
define("DEFAULT_DATE", "/.*/i");
define("DEFAULT_DOUBLE", "/^[0-9]*\.[0-9]{1,2}$/");
define("DEFAULT_INTEGER", "/^[0-9]*$/");
define("DEFAULT_STRING", "/.*/i");
define("DEFAULT_TEXT", "/.*/i");

// additional validation rules and extraction rules

// required text field
define("REQUIRED_TEXT", "/^[a-z\d\.,'][a-z\d\.,' ]{0,65535}$/i");
// extract a hostname from a URL
define("PATTERN_URL_HOSTNAME", "/^(http:\/\/)?([^\/]+)/i");
// extract a domain name from a URL
define("PATTERN_URL_DOMAINNAME", "/[^\.\/]+\.[^\.\/]+(\.[^\.\/]{2})?$/");
// validate an IP address
define("RULE_IP", "/^(((([1-9])|([1-9][\d])|(1[\d]{2})|(2[0-4][\d])|(25[0-4]))(\.(([\d])|([1-9][\d])|(1[\d]{2})|(2[0-4][\d])|(25[0-4]))){3})|(0(\.0){3}))$/");
// validate an email address
define("RULE_EMAIL", "/^[-_.a-zA-Z0-9]+@((([a-zA-Z0-9]|[-_.a-zA-Z0-9]*[a-zA-Z0-9])\.)+(ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|at|au|aw|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cs|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|in|info|int|io|iq|ir|is|it|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|mg|mh|mil|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nt|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|um|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)|(([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5])\.){3}([0-9][0-9]?|[0-1][0-9][0-9]|[2][0-4][0-9]|[2][5][0-5]))$/i");
// validate a username
define("RULE_USERNAME", "/^[-_.a-zA-Z0-9]*$/i");
// validate a URL (accepts blanks)
define("RULE_URL_BLANK", "/^(http:\/\/.*\/.*)$|^([.*]{0})$/i");

?>
