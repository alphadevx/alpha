<?php

/* $Id$ */

require_once '../../config/config.conf';

header("Content-type: text/css");

echo <<<EOCSS

body {
	scrollbar-3d-light-color:#999999;
	scrollbar-arrow-color: #4B3233;
	scrollbar-base-color:black;
	scrollbar-dark-shadow-color:#4B3233;
	scrollbar-face-color:#E0BBA1;
	scrollbar-highlight-color:white;
	scrollbar-shadow-color:black;
	margin-left:0;
	margin-right:0;
	margin-top:0;
	margin-bottom:0;
	width:100%;
	height:100%;
	background-color:#E0BBA1;
	overflow:auto;
	color:black;
	font-family:arial;
	font-size:10pt;
}

.home-page {	
	background-color:#7E5440;	
}

div {position:absolute; top:0px; left:0px; width:0px; height:0px; visibility:hidden; overflow:hidden;}
span {overflow:hidden;}

.boxout, pre.php, pre.xml, pre.javascript, pre.perl, pre.html4strict, pre.css {padding:5px; margin:8px; background-color:white; border:2px dashed #4B3233;}

.norButton {background-color:#D59060; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}
.oveButton {background-color:#F9C089; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}
.selButton {background-color:#4B3233; border: 2px solid white; text-align:center; overflow:hidden; font-family:arial; font-weight:bold; color:white; font-size:8pt;}

h1 {font-size:170%; font-weight:bold; border:1px solid #4B3233; background-color:#D59060; color:white; margin:7px;}
h2 {font-size:140%; margin:10px; color:#4B3233; margin-bottom:20px; margin-top:20px;}
h3 {font-size:120%; margin:10px; font:italic; color:#4B3233; margin-bottom:15px; margin-top:15px;}
h4 {font-size:110%; margin:10px; text-decoration:underline; color:#4B3233; margin-bottom:15px; margin-top:15px;}

.readonly {background-color:#DCDCDC;}

p {margin-left:5px; margin-right:5px;}
.success {color:blue;}
.warning {color:red;}
.error {background-image:url($sysURL/images/icons/error_autumn.png); background-repeat:no-repeat; background-position: 10px 10px; padding:5px; padding-left:60px; color:red; border:2px dotted red; position:relative; height:60px;}

a:link {color:#20557C; text-decoration:none; background-color:none;}
a:active {color:#20557C; text-decoration:none; background-color:none;}
a:visited {color:#20557C; text-decoration:none; background-color:none;}
a:hover {color:#20557C; text-decoration:underline; background-color:none;}

td {font-size:8pt;}
th {font-size:8pt;}
input {font-size:8pt;}

table.bordered {border:1px solid #4B3233; background-color:whitesmoke;}
th.bordered {font-weight:bold; border:1px solid #4B3233; background-color:#4B3233; color:white; font-family:arial; font-weight:bold; font-size:8pt; text-align:center;}
td.bordered {border:1px solid #4B3233;}

table.create_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #4B3233;}
table.create_view th {background-color:#4B3233; color:white; width:50%; text-align:right;}
table.create_view td {border:1px solid #4B3233;}

table.list_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #4B3233;}
table.list_view th {background-color:#4B3233; color:white;}
table.list_view td {border:1px solid #4B3233;}

table.admin_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #4B3233;}
table.admin_view th {background-color:#4B3233; color:white;}
table.admin_view td {border:1px solid #4B3233;}

table.detailed_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #4B3233;}
table.detailed_view th {background-color:#4B3233; color:white; width:30%; text-align:right;}
table.detailed_view td {border:1px solid #4B3233;}

table.edit_view {table-layout:fixed;  width:95%; margin:10px; border:1px solid #4B3233;}
table.edit_view th {background-color:#4B3233; color:white; width:50%; text-align:right;}
table.edit_view td {border:1px solid #4B3233;}

.text_box {position:relative; visibility:visible; height:250px; width:100%; overflow:scroll;}

.buttonClass {width:117px; height:17px; border:1px solid black; padding:0px; margin:0px; background-color:#249FD8; color:white; font-family:arial; font-weight:bold; font-size:8pt; text-align:center; cursor:hand;}

.articleDetails{background-color:#E0BBA1;}

#subDiv {background-color:#E0BBA1;}
#newsDiv {background-color:#E0BBA1;}

EOCSS;

?>