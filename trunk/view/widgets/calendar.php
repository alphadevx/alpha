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
	 */
	function calendar($object, $label="", $name="") {
		
		// check the type of the object passed
		if(strtoupper(get_class($object)) == "DATE" || strtoupper(get_class($object)) == "TIMESTAMP"){
			$this->date_object = $object;
		}else{
			$error = new handle_error($_SERVER["PHP_SELF"],'Calendar widget can only accept a Date or Timestamp object!','calendar()','framework');
			exit;
		}
		$this->label = $label;
		$this->name = $name;
	}
	
	/**
	 * Renders the text box and icon to open the calendar pop-up
	 *
	 * @param bool $table_tags
	 * @return string
	 */
	function render($table_tags=true) {
		global $config;
		$html = '';		
		
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
		
		$value = $this->date_object->getValue();
		if($value == '0000-00-00')
			$value = '';
		
		if($table_tags) {
			$html .= '<tr><td style="width:25%;">';
			$html .= $this->label;
			$html .= '</td>';

			$html .= '<td>';
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=205,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $config->get('sysURL')."/alpha/images/icons/calendar.png");
			$html .= $tmp->render();
			$html .= '</td></tr>';
		}else{
			$html .= '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$value.'" readonly/>';
			$tmp = new button("window.open('".$config->get('sysURL')."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=205,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $config->get('sysURL')."/alpha/images/icons/calendar.png");
			$html .= $tmp->render();
		}
		
		return $html;
	}
	
	/**
	 * renders the HTML for the current month
	 * 
	 * @return string
	 */
	function render_month() {
		global $config;
		$html = '';
		
		$html .= $this->display_page_head();
		
		$value = $this->date_object->getValue();
		if($value == '0000-00-00') {
			$this->date_object->populateFromString(date('Y-m-d'));			
		}
		
		if($value == '0000-00-00 00:00:00') {
			$this->date_object->populateFromString(date('Y-m-d'));			
		}
		
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
		
		$html .= '<table cols="7" style="table-layout:fixed; width:200px; height:200px">';
		$html .= '<tr>';
		$html .= '<th colspan="7">'.$month_name.' '.$year.'</th>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<th class="headCalendar">Sun</th>';
		$html .= '<th class="headCalendar">Mon</th>';
		$html .= '<th class="headCalendar">Tue</th>';
		$html .= '<th class="headCalendar">Wed</th>';
		$html .= '<th class="headCalendar">Thu</th>';
		$html .= '<th class="headCalendar">Fri</th>';
		$html .= '<th class="headCalendar">Sat</th>';		
		$html .= '</tr>';	
		
		$day = 1;
		$wday = $first_week_day;
		$firstweek = true;
		while ( $day <= $lastday) {
			if ($firstweek) {
				$html .= "<tr>";
				for ($i=1; $i<=$first_week_day; $i++) {				
					$html .= "<td class=\"headCalendar\">&nbsp;</td>";
			}
			$firstweek = false;
			}
			if ($wday == 0) {
				$html .= "<tr>";
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
					$html .= "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\" style=\"color:white; font-weight:bold;\">$day</td>";
				else
					$html .= "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\" style=\"color:white; font-weight:bold;\">$day</td>";
			}else{
				// other dates
				if(strtoupper(get_class($this->date_object)) == "TIMESTAMP")
					$html .= "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
				else
					$html .= "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false, this);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
			}
			
			if ($wday == 6) {
				$html .= "</tr>\n";
			}
			
			$wday++;
			$wday = $wday % 7;
			$day++;
		}
		
		while ($day > $lastday && $wday != 7) {
			$html .= "<td class=\"headCalendar\">&nbsp;</td>";
			$wday++;
		}
		
		// if it is a timestamp that was passed, we need to render the hours:minutes:seconds		
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP"){
			$html .= '<tr>';
			$html .= '<td colspan="7" class="calendar" align="center">';
			$html .= '<input id="hours" value="'.$this->date_object->getHour().'" onblur="updateTime()" size="1"/>:';
			$html .= '<input id="minutes" value="'.$this->date_object->getMinute().'" onblur="updateTime()" size="1"/>:';
			$html .= '<input id="seconds" value="'.$this->date_object->getSecond().'" onblur="updateTime()" size="1"/>';
			$html .= '</td>';
			$html .= '</tr>';
		}
		
		$html .= '<tr>';
		$html .= '<td colspan="3" align="center">';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".($year-1)."&display_month=".$month."';", "Previous year", "yearPreBut", $config->get('sysURL')."/alpha/images/icons/arrow_left.png");
		$html .= $tmp->render();
		$html .= 'Year';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".($year+1)."&display_month=".$month."';", "Next year", "yearNextBut", $config->get('sysURL')."/alpha/images/icons/arrow_right.png");
		$html .= $tmp->render();
		$html .= '</td>';
		$html .= '<td>&nbsp;</td>';
		$html .= '<td colspan="3" align="center">';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".$year."&display_month=".($month-1)."';", "Previous month", "monthPreBut", $config->get('sysURL')."/alpha/images/icons/arrow_left.png");
		$html .= $tmp->render();
		$html .= 'Month';
		$tmp = new button("document.location='calendar.php?date=".$this->date_object->getValue()."&name=".$this->name."&display_year=".$year."&display_month=".($month+1)."';", "Next month", "monthNextBut", $config->get('sysURL')."/alpha/images/icons/arrow_right.png");
		$html .= $tmp->render();
		$html .= '</td>';
		$html .= '</tr>';
		
		$html .= '<tr>';
		$html .= '<td colspan="7" align="center" class="headCalendar">';
		$tmp = new button("window.close();", "Cancel", "cancelBut", $config->get('sysURL')."/alpha/images/icons/cancel.png");
		$html .= $tmp->render();
		$html .= '&nbsp;&nbsp;&nbsp;';		
		$tmp = new button("window.opener.document.getElementById('".$this->name."').value = date_selected; window.close();", "Accept", "acceptBut", $config->get('sysURL')."/alpha/images/icons/accept.png");
		$html .= $tmp->render();
		$html .= '</td>';
		$html .= '</tr>';
		
		$html .= '</table>';
		
		$html .= $this->display_page_head();
		
		return $html;
	}
	
	function display_page_head() {
		global $config;
		$html = '';		
		
		$html .= '<html>';
		$html .= '<head>';
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		$html .= '<title>Calendar</title>';		
		
		$html .= '<link rel="StyleSheet" type="text/css" href="'.$config->get('sysURL').'/config/css/'.$config->get('sysTheme').'.css.php">';
		
		$html .= '<script language="JavaScript" src="'.$config->get('sysURL').'/alpha/scripts/addOnloadEvent.js"></script>';
		
		$html .= '<script language="javascript">';
		$html .= 'var date_selected = "'.$this->date_object->getValue().'";';
		$html .= 'function selectDate(date, include_time_fields, clicked_cell) {';		
		$html .= '	var cells = document.getElementsByTagName("td");';
		$html .= '	for(var i = 0; i < cells.length; i++) {';
		$html .= '		cells[i].style.color = "black";';
		$html .= '		cells[i].style.fontWeight = "normal";';
		$html .= '	}';
		$html .= '	clicked_cell.style.color = "white";';
		$html .= '	clicked_cell.style.fontWeight = "bold";';
		$html .= '	if(include_time_fields){';
		$html .= '		date_selected = date+" "+';
		$html .= '		document.getElementById("hours").value+":"+';
		$html .= '		document.getElementById("minutes").value+":"+';
		$html .= '		document.getElementById("seconds").value;';
		$html .= '	}else{';
		$html .= '		date_selected = date;';
		$html .= '	}';
		$html .= '}';
		$html .= 'function updateTime() {';
		// the second param "10" in parseInt prevents a "bug" with values like 08 and 09 being mistaken as hex
		$html .= '	var hours = parseInt(document.getElementById("hours").value, 10);';
		$html .= '	var minutes = parseInt(document.getElementById("minutes").value, 10);';
		$html .= '	var seconds = parseInt(document.getElementById("seconds").value, 10);';
		
		// validate hours
		$html .= '	if(isNaN(hours) || hours < 0 || hours > 23) {';
		$html .= '		document.getElementById("hours").value = "00";';
		$html .= '		document.getElementById("hours").style.backgroundColor = "yellow";';
		$html .= '		return false;';
		$html .= '	}else{';
		$html .= '		document.getElementById("hours").style.backgroundColor = "white";';		
		// zero-padding
		$html .= '		if(hours < 10)';
		$html .= '			hours = "0"+hours;';
		$html .= '		document.getElementById("hours").value = hours;';
		$html .= '	}';
		
		// validate minutes
		$html .= '	if(isNaN(minutes) || minutes < 0 || minutes > 59) {';
		$html .= '		document.getElementById("minutes").value = "00";';
		$html .= '		document.getElementById("minutes").style.backgroundColor = "yellow";';
		$html .= '		return false;';
		$html .= '	}else{';
		$html .= '		document.getElementById("minutes").style.backgroundColor = "white";';		
		// zero-padding
		$html .= '		if(minutes < 10)';
		$html .= '			minutes = "0"+minutes;';
		$html .= '		document.getElementById("minutes").value = minutes;';
		$html .= '	}';
		
		// validate seconds
		$html .= '	if(isNaN(seconds) || seconds < 0 || seconds > 59) {';
		$html .= '		document.getElementById("seconds").value = "00";';
		$html .= '		document.getElementById("seconds").style.backgroundColor = "yellow";';
		$html .= '		return false;';
		$html .= '	}else{';
		$html .= '		document.getElementById("seconds").style.backgroundColor = "white";';		
		// zero-padding
		$html .= '		if(seconds < 10)';
		$html .= '			seconds = "0"+seconds;';
		$html .= '		document.getElementById("seconds").value = seconds;';
		$html .= '	}';
		
		// replace the old time with the new value
		$html .= '	date_selected = date_selected.substring(0, 10);';
		$html .= '	date_selected = date_selected+" "+hours+":"+minutes+":"+seconds;';
		$html .= '	return true;';		
		$html .= '}';
		$html .= '</script>';
		
		require_once $config->get('sysRoot').'alpha/view/widgets/button.js.php';
		
		$html .= '</head>';
		$html .= '<body>';

		return $html;
	}
	
	function display_page_foot() {
		return '</body></html>';
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
		if(!empty($_GET["name"])) {
			$cal = new calendar($date, "", $_GET["name"]);
			echo $cal->render_month();
		}else{
			$cal = new calendar($date);
			echo $cal->render_month();
		}
	}else{
		$error = new handle_error($_SERVER["PHP_SELF"],'No date/timestamp provided on the query string!','GET','other');
		exit;
	}
}

?>