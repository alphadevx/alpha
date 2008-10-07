<?php

// $Id$

if(!isset($config))
	require_once '../../util/configLoader.inc';
$config =&configLoader::getInstance();

require_once $config->get('sysRoot').'alpha/util/handle_error.inc';

require_once $config->get('sysRoot').'alpha/model/types/Date.inc';
require_once $config->get('sysRoot').'alpha/model/types/Timestamp.inc';

/**
* Calendar HTML custom widget
* 
* @package Alpha Widgets
* @author John Collins <john@design-ireland.net>
* @copyright 2006 John Collins
*  
*/

class calendar{
	
	/**
	 * the date or timestamp object for the widget
	 * @var Date/Timestamp
	 */
	var $date_object = null;	
	
	/**
	 * the data label for the string object
	 * @var string
	 */
	var $label;
	
	/**
	 * the name of the HTML input box
	 * @var string
	 */
	var $name;
	
	/**
	 * the constructor
	 * @param object $object the date or timestamp object that will be edited by this calender
	 * @param string $label the data label for the date object
	 * @param string $name the name of the HTML input box	 
	 * @param bool $table_tags determines if table tags are also rendered for the calender
	 */
	function calendar($object, $label="", $name="", $table_tags=true) {
		
		// check the type of the object passed
		if(strtoupper(get_class($object)) == "DATE" || strtoupper(get_class($object)) == "TIMESTAMP"){
			$this->date_object = $object;
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Calendar widget can only accept a Date or Timestamp object!','calendar()','framework');
			exit;
		}
		$this->label = $label;
		$this->name = $name;
				
		// if its in a form render the input tags and calender button, else render the month for the pop-up window
		if(!empty($label)) {
			$this->render($table_tags);
		}else{
			$this->display_page_head();
			$this->render_month();
			$this->display_page_foot();
		}
	}
	
	function render($table_tags) {
		global $config;		
		
		/*
		 * decide on the size of the text box and the height of the widget pop-up, 
		 * depending on the date_object type
		 */
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP") {
			$size = 18;
			$cal_height = 230;
		}else{
			$size = 10;
			$cal_height = 230;
		}
		
		if($table_tags) {
			echo '<tr><td style="width:25%;">';
			echo $this->label;
			echo '</td>';

			echo '<td>';
			echo '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->date_object->getValue().'" readonly/>';
			$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=205,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $config->get('sysURL')."/alpha/images/icons/calendar.png");
			echo '</td></tr>';
		}else{
			echo '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->date_object->getValue().'" readonly/>';
			$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=205,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $config->get('sysURL')."/alpha/images/icons/calendar.png");
		}
	}
	
	/**
	 * renders the HTML for the current month
	 */
	function render_month() {
		global $config;
		//global $sysURL;
		
		if(isset($_GET["display_month"]))
			$month = $_GET["display_month"];
		else
			$month = $this->date_object->getMonth();
			
		if(isset($_GET["display_year"]))
			$year = $_GET["display_year"];
		else
			$year = $this->date_object->getYear();
			
		if ($month > 12){
			$month = $month - 12;
			$year++;
		}
		
		if ($month < 1) {
			$month = 12;
			$year--;
		}
		
		$date = getdate(mktime(0,0,0,$month,1,$year));
				
		$month_num = $date["mon"];
		$month_name = $date["month"];
		$year = $date["year"];
		$date_today = getdate(mktime(0,0,0,$month_num,1,$year));
		$first_week_day = $date_today["wday"];
		$cont = true;
		$today = 27;
		
		while (($today <= 32) && ($cont)) {
			$date_today = getdate(mktime(0,0,0,$month_num,$today,$year));
			
			if ($date_today["mon"] != $month_num) {
				$lastday = $today - 1;
				$cont = false;
			}
			
			$today++;
		}
		
		echo '<table cols="7" style="table-layout:fixed; width:200px; height:200px">';
		echo '<tr>';
		echo '<th colspan="7">'.$month_name.' '.$year.'</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th class="headCalendar">Sun</th>';
		echo '<th class="headCalendar">Mon</th>';
		echo '<th class="headCalendar">Tue</th>';
		echo '<th class="headCalendar">Wed</th>';
		echo '<th class="headCalendar">Thu</th>';
		echo '<th class="headCalendar">Fri</th>';
		echo '<th class="headCalendar">Sat</th>';		
		echo '</tr>';	
		
		$day = 1;
		$wday = $first_week_day;
		$firstweek = true;
		while ( $day <= $lastday) {
			if ($firstweek) {
				echo "<tr>";
				for ($i=1; $i<=$first_week_day; $i++) {
				
				echo "<td class=\"headCalendar\">&nbsp;</td>";
			}
			$firstweek = false;
			}
			if ($wday == 0) {
				echo "<tr>";
			}
						
			if (intval($month_num) < 10) {
				$new_month_num = "0$month_num";
			} elseif (intval($month_num) >= 10) { 
				$new_month_num = $month_num;
			}
			if ( intval($day) < 10) { 
				$new_day = "0$day"; 
			} elseif (intval($day) >= 10) { 
				$new_day = $day;
			}
			$link_date = "$year-$new_month_num-$new_day";
			
			if ($year == $this->date_object->getYear() && $month == $this->date_object->getMonth() && $day == $this->date_object->getDay()) {
				// today's date
				if(strtoupper(get_class($this->date_object)) == "TIMESTAMP")
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\" style=\"color:white; font-weight:bold;\">$day</td>";
				else
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\" style=\"color:white; font-weight:bold;\">$day</td>";
			}else{
				// other dates
				if(strtoupper(get_class($this->date_object)) == "TIMESTAMP")
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
				else
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
			}
			
			if ($wday == 6) {
				echo "</tr>\n";
			}
			
			$wday++;
			$wday = $wday % 7;
			$day++;
		}
		
		while ($day > $lastday && $wday != 7) {
			echo "<td class=\"headCalendar\">&nbsp;</td>";
			$wday++;
		}
		
		// if it is a timestamp that was passed, we need to render the hours:minutes:seconds		
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP"){
			echo '<tr>';
			echo '<td colspan="7" class="calendar" align="center">';
			echo '<input id="hours" value="'.$this->date_object->getHour().'" onblur="updateTime()" size="1"/>:';
			echo '<input id="minutes" value="'.$this->date_object->getMinute().'" onblur="updateTime()" size="1"/>:';
			echo '<input id="seconds" value="'.$this->date_object->getSecond().'" onblur="updateTime()" size="1"/>';
			echo '</td>';
			echo '</tr>';
		}
		
		echo '<tr>';
		echo '<td colspan="3" align="center">';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".($year-1)."&display_month=".$month."';", "Previous year", "yearPreBut", $config->get('sysURL')."/alpha/images/icons/arrow_left.png");
		echo 'Year';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".($year+1)."&display_month=".$month."';", "Next year", "yearNextBut", $config->get('sysURL')."/alpha/images/icons/arrow_right.png");
		echo '</td>';
		echo '<td>&nbsp;</td>';
		echo '<td colspan="3" align="center">';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".$year."&display_month=".($month-1)."';", "Previous month", "monthPreBut", $config->get('sysURL')."/alpha/images/icons/arrow_left.png");
		echo 'Month';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".$year."&display_month=".($month+1)."';", "Next month", "monthNextBut", $config->get('sysURL')."/alpha/images/icons/arrow_right.png");
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td colspan="7" align="center" class="headCalendar">';
		$tmp = new button("window.close();", "Cancel", "cancelBut", $config->get('sysURL')."/alpha/images/icons/cancel.png");
		echo '&nbsp;&nbsp;&nbsp;';		
		$tmp = new button("window.opener.document.getElementById('".$this->name."').value = date_selected; window.close();", "Accept", "acceptBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
	}
	
	function display_page_head() {
		global $config;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>Calendar</title>';		
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$config->get('sysTheme').'.css.php">';
		
		echo '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		echo '<script language="javascript">';
		echo 'var date_selected = "'.$this->date_object->getValue().'";';
		echo 'function selectDate(date, include_time_fields, clicked_cell) {';		
		echo '	var cells = document.getElementsByTagName("td");';
		echo '	for(var i = 0; i < cells.length; i++) {';
		echo '		cells[i].style.color = "black";';
		echo '		cells[i].style.fontWeight = "normal";';
		echo '	}';
		echo '	clicked_cell.style.color = "white";';
		echo '	clicked_cell.style.fontWeight = "bold";';
		echo '	if(include_time_fields){';
		echo '		date_selected = date+" "+';
		echo '		document.getElementById("hours").value+":"+';
		echo '		document.getElementById("minutes").value+":"+';
		echo '		document.getElementById("seconds").value;';
		echo '	}else{';
		echo '		date_selected = date;';
		echo '	}';
		echo '}';
		echo 'function updateTime() {';
		// the second param "10" in parseInt prevents a "bug" with values like 08 and 09 being mistaken as hex
		echo '	var hours = parseInt(document.getElementById("hours").value, 10);';
		echo '	var minutes = parseInt(document.getElementById("minutes").value, 10);';
		echo '	var seconds = parseInt(document.getElementById("seconds").value, 10);';
		
		// validate hours
		echo '	if(isNaN(hours) || hours < 0 || hours > 23) {';
		echo '		document.getElementById("hours").value = "00";';
		echo '		document.getElementById("hours").style.backgroundColor = "yellow";';
		echo '		return false;';
		echo '	}else{';
		echo '		document.getElementById("hours").style.backgroundColor = "white";';		
		// zero-padding
		echo '		if(hours < 10)';
		echo '			hours = "0"+hours;';
		echo '		document.getElementById("hours").value = hours;';
		echo '	}';
		
		// validate minutes
		echo '	if(isNaN(minutes) || minutes < 0 || minutes > 59) {';
		echo '		document.getElementById("minutes").value = "00";';
		echo '		document.getElementById("minutes").style.backgroundColor = "yellow";';
		echo '		return false;';
		echo '	}else{';
		echo '		document.getElementById("minutes").style.backgroundColor = "white";';		
		// zero-padding
		echo '		if(minutes < 10)';
		echo '			minutes = "0"+minutes;';
		echo '		document.getElementById("minutes").value = minutes;';
		echo '	}';
		
		// validate seconds
		echo '	if(isNaN(seconds) || seconds < 0 || seconds > 59) {';
		echo '		document.getElementById("seconds").value = "00";';
		echo '		document.getElementById("seconds").style.backgroundColor = "yellow";';
		echo '		return false;';
		echo '	}else{';
		echo '		document.getElementById("seconds").style.backgroundColor = "white";';		
		// zero-padding
		echo '		if(seconds < 10)';
		echo '			seconds = "0"+seconds;';
		echo '		document.getElementById("seconds").value = seconds;';
		echo '	}';
		
		// replace the old time with the new value
		echo '	date_selected = date_selected.substring(0, 10);';
		echo '	date_selected = date_selected+" "+hours+":"+minutes+":"+seconds;';
		echo '	return true;';		
		echo '}';
		echo '</script>';
		
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		
		echo '</head>';
		echo '<body>';		
	}
	
	function display_page_foot() {
		echo '</body>';
		echo '</html>';
	}
}

// checking to see if the calendar has been accessed directly via a pop-up
if(basename($_SERVER["PHP_SELF"]) == "calendar.php") {
	if(isset($_GET["date"])) {
		// check for the presence of colons to indicate a timestamp rather than a date
		if(strpos($_GET["date"], ':') === false)
			$date = new Date();
		else
			$date = new Timestamp();
		
		$date->populateFromString($_GET["date"]);
		// check to see if a form field name is provided
		if(!empty($_GET["name"]))
			$cal = new calendar($date, "", $_GET["name"]);
		else
			$cal = new calendar($date);
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'No date/timestamp provided on the query string!','GET','other');
		exit;
	}
}

?>