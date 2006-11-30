<?php

// $Id$

// include the config file
require_once "../config/config.conf";

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Design-Ireland.net - Web Design Articles and Tutorials</title>
<meta name="Keywords" content="web design ireland, design ireland, web design, web development, dhtml tutorials, html tutorials, advanced web design, dynamic html, web graphics, digital photography">
<meta name="Description" content="Business, Design, Imagery, Browsing.  Helpful advice and tutorials.">
<meta name="Author" content="john collins">
<meta name="copyright" content="copyright Design-Ireland.net">
<meta name="identifier" content="http://<?= $sysURL ?>/">
<meta name="revisit-after" content="7 days">
<meta name="expires" content="never">
<meta name="language" content="en">
<meta name="distribution" content="global">
<meta name="title" content="Design-Ireland.net">
<meta name="robots" content="index,follow">
<meta http-equiv="imagetoolbar" content="no">

<link rel="StyleSheet" type="text/css" href="<?= $sysURL ?>/config/css/<?= $sysTheme ?>.css">

</head>

<body>

<?php
 
 require_once '../lib/markdown.php';
 require_once '../lib/geshi.php';
 
 $article = file_get_contents('browsing-13.text');
 
 $render = Markdown($article);
 
 echo $render; 
 
?>

</body>

</html>
