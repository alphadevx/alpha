<?php

/* $Id$ */

require_once '../../config/config.conf';

header("Content-type: text/css");

echo <<<EOCSS

body {
	scrollbar-3d-light-color:#999999;
	scrollbar-arrow-color: #333E36;
	scrollbar-base-color:black;
	scrollbar-dark-shadow-color:#333E36;
	scrollbar-face-color:#DED6DB;
	scrollbar-highlight-color:white;
	scrollbar-shadow-color:black;
	margin-left:0;
	margin-right:0;
	margin-top:0;
	margin-bottom:0;
	width:100%;
	height:100%;
	background-color:#DED6DB;
	overflow:auto;
	color:black;
	font-family:arial;
	font-size:10pt;
}

.home-page {	
	background-color:#A29BA3;	
}

div {position:absolute; top:0px; left:0px; width:0px; height:0px; visibility:hidden; overflow:hidden;}
span {overflow:hidden;}

.boxout, pre.php, pre.xml, pre.javascript, pre.perl, pre.html4strict, pre.css {padding:5px; margin:8px; background-color:white; border:2px dashed #333E36;}

.norButton {background-color:#B8ADBD; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}
.oveButton {background-color:#BFB9B4; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}
.selButton {background-color:#333E36; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}

h1 {font-size:170%; font-weight:bold; border:1px solid #333E36; background-color:#B8ADBD; color:white; margin:7px;}
h2 {font-size:140%; margin:10px; color:#333E36; margin-bottom:20px; margin-top:20px;}
h3 {font-size:120%; margin:10px; font:italic; color:#333E36; margin-bottom:15px; margin-top:15px;}
h4 {font-size:110%; margin:10px; text-decoration:underline; color:#333E36; margin-bottom:15px; margin-top:15px;}

.readonly {background-color:#DCDCDC;}

p {margin-left:5px; margin-right:5px;}
.success {color:blue;}
.warning {color:red;}
.error {background-image:url($sysURL/images/icons/error_spring.png); background-repeat:no-repeat; background-position: 10px 10px; padding:5px; padding-left:60px; color:red; border:2px dotted red; position:relative; height:60px;}

a:link {color:#20557C; text-decoration:none; background-color:none;}
a:active {color:#20557C; text-decoration:none; background-color:none;}
a:visited {color:#20557C; text-decoration:none; background-color:none;}
a:hover {color:#20557C; text-decoration:underline; background-color:none;}

td {font-size:8pt;}
th {font-size:8pt;}
input {font-size:8pt;}

table.bordered {border:1px solid #333E36; background-color:whitesmoke;}
th.bordered {font-weight:bold; border:1px solid #333E36; background-color:#333E36; color:white; font-family:arial; font-weight:bold; font-size:8pt; text-align:center;}
td.bordered {border:1px solid #333E36;}

table.create_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #333E36;}
table.create_view th {background-color:#333E36; color:white; width:50%; text-align:right;}
table.create_view td {border:1px solid #333E36;}

table.list_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #333E36;}
table.list_view th {background-color:#333E36; color:white;}
table.list_view td {border:1px solid #333E36;}

table.admin_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #333E36;}
table.admin_view th {background-color:#333E36; color:white;}
table.admin_view td {border:1px solid #333E36;}

table.detailed_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #333E36;}
table.detailed_view th {background-color:#333E36; color:white; width:30%; text-align:right;}
table.detailed_view td {border:1px solid #333E36;}

table.edit_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #333E36;}
table.edit_view th {background-color:#333E36; color:white; width:50%; text-align:right;}
table.edit_view td {border:1px solid #333E36;}

.text_box {position:relative; visibility:visible; height:250px; width:100%; overflow:scroll;}

.buttonClass {width:117px; height:17px; border:1px solid black; padding:0px; margin:0px; background-color:#249FD8; color:white; font-family:arial; font-weight:bold; font-size:8pt; text-align:center; cursor:hand;}

.articleDetails{background-color:#DED6DB;}

#subDiv {background-color:#DED6DB;}
#newsDiv {background-color:#DED6DB;}

EOCSS;

?>