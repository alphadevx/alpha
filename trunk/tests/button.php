<?php

// $Id$
 
// include the config file
require_once "../config/config.conf";

?>
<html>

<head>

<link rel="StyleSheet" type="text/css" href="<?= $sysURL ?>/config/css/winter.css">

<script language="JavaScript" src="../scripts/makeLayer.js">
/* interface

makeLayer(id, top, left, width, height, position, visibility, backgroundColor, zIndex)
defaults are:                           absolute, visible,    none,            10

leave blank for defaults to be used!
 */
</script>


<?php

require_once '../view/widgets/button.js.php';

?>

<script type="text/javascript">
var buildCalled = false;

function build() {	
	ButLay = new makeLayer('testID','265','5','95','25','','','','');
	buildCalled = true;
}
</script>

</head>
<body >

<?php

$temp = new button("","Test","testID");

?>

</body>

</html>