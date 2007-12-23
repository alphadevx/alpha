<?php

// $Id$

if (!isset($sysRoot))
	require_once '../../../config/config.conf';

require_once $sysRoot.'alpha/util/handle_error.inc';

require_once $sysRoot.'alpha/model/types/Date.inc';
require_once $sysRoot.'alpha/model/types/Timestamp.inc';

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
		global $sysURL;
		
		/*
		 * decide on the size of the text box and the height of the widget pop-up, 
		 * depending on the date_object type
		 */
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP") {
			$size = 18;
			$cal_height = 200;
		}else{
			$size = 10;
			$cal_height = 200;
		}
		
		if($table_tags) {
			echo '<tr><td style="width:25%;">';
			echo $this->label;
			echo '</td>';

			echo '<td>';
			echo '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->date_object->get_value().'" readonly/>';
			$temp = new button("window.open('".$sysURL."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=200,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $sysURL."/alpha/images/icons/calendar.png");
			echo '</td></tr>';
		}else{
			echo '<input type="text" size="'.$size.'" class="readonly" name="'.$this->name.'" id="'.$this->name.'" value="'.$this->date_object->get_value().'" readonly/>';
			$temp = new button("window.open('".$sysURL."/alpha/view/widgets/calendar.php?date='+document.getElementById('".$this->name."').value+'&name=".$this->name."','calWin','toolbar=0,location=0,menuBar=0,scrollbars=1,width=200,height=".$cal_height.",left='+event.screenX+',top='+event.screenY+'');", "Open Calendar", "calBut", $sysURL."/alpha/images/icons/calendar.png");
		}
	}
	
	/**
	 * renders the HTML for the current month
	 */
	function render_month() {
		if(isset($_GET["display_month"]))
			$month = $_GET["display_month"];
		else
			$month = $this->date_object->month;
			
		if(isset($_GET["display_year"]))
			$year = $_GET["display_year"];
		else
			$year = $this->date_object->year;
			
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
		
		echo '<table border="1" cols="7" style="table-layout:fixed; width:200px; height:200px">';
		echo '<tr>';
		echo '<th colspan="7">'.$month_name.' '.$year.'</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th class="calendar">Sun</th>';
		echo '<th class="calendar">Mon</th>';
		echo '<th class="calendar">Tue</th>';
		echo '<th class="calendar">Wed</th>';
		echo '<th class="calendar">Thu</th>';
		echo '<th class="calendar">Fri</th>';
		echo '<th class="calendar">Sat</th>';		
		echo '</tr>';	
		
		$day = 1;
		$wday = $first_week_day;
		$firstweek = true;
		while ( $day <= $lastday) {
			if ($firstweek) {
				echo "<tr>";
				for ($i=1; $i<=$first_week_day; $i++) {
				
				echo "<td>&nbsp;</td>";
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
			
			if ($year == $this->date_object->year && $month == $this->date_object->month && $day == $this->date_object->day) {
				// today's date
				if(strtoupper(get_class($this->date_object)) == "TIMESTAMP")
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\"><strong>$day</strong></td>";
				else
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\"><strong>$day</strong></td>";
			}else{
				// other dates
				if(strtoupper(get_class($this->date_object)) == "TIMESTAMP")
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', true);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
				else
					echo "<td class=\"norCalendar\" onclick=\"selectDate('".$year."-".$new_month_num."-".$new_day."', false);\" onmouseover=\"this.className = 'oveCalendar'\" onmouseout=\"this.className = 'norCalendar'\">$day</td>";
			}
			
			if ($wday == 6) {
				echo "</tr>\n";
			}
			
			$wday++;
			$wday = $wday % 7;
			$day++;
		}
		
		while ($day > $lastday && $wday != 7) {
			echo "<td>&nbsp;</td>";
			$wday++;
		}
		
		// if it is a timestamp that was passed, we need to render the hours:minutes:seconds		
		if(strtoupper(get_class($this->date_object)) == "TIMESTAMP"){
			echo '<tr>';
			echo '<td colspan="7" class="calendar" align="center">';
			echo '<input id="hours" value="'.$this->date_object->get_hour().'" size="2"/>';
			echo '<input id="minutes" value="'.$this->date_object->get_minute().'" size="2"/>';
			echo '<input id="seconds" value="'.$this->date_object->get_second().'" size="2"/>';
			echo '</td>';
			echo '</tr>';
		}
		
		echo '<tr>';
		echo '<td colspan="3" class="calendar">';
		echo '<a href="calendar.php?date='.$this->date_object->get_value().'&name='.$this->name.'&display_year='.($year-1).'&display_month='.$month.'" class="calendar"><<-</a>';
		echo 'Year';
		echo '<a href="calendar.php?date='.$this->date_object->get_value().'&name='.$this->name.'&display_year='.($year+1).'&display_month='.$month.'" class="calendar">->></a>';
		echo '</td>';
		echo '<td>&nbsp;</td>';
		echo '<td colspan="3" class="calendar">';
		echo '<a href="calendar.php?date='.$this->date_object->get_value().'&name='.$this->name.'&display_year='.$year.'&display_month='.($month-1).'" class="calendar"><<-</a>';
		echo 'Month';
		echo '<a href="calendar.php?date='.$this->date_object->get_value().'&name='.$this->name.'&display_year='.$year.'&display_month='.($month+1).'" class="calendar">->></a>';
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
	}
	
	function display_page_head() {
		global $sysURL;
		global $sysTheme;		
		
		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">';
		echo '<title>Calendar</title>';		
		
		echo '<link rel="StyleSheet" type="text/css" href="'.$sysURL.'/config/css/'.$sysTheme.'.css.php">';
		
		echo '<script language="javascript">';
		echo 'function selectDate(date, include_time_fields) {';
		echo '	if(include_time_fields){';
		echo '		window.opener.document.getElementById("'.$this->name.'").value = date+" "+';
		echo '			document.getElementById("hours").value+":"+';
		echo '			document.getElementById("minutes").value+":"+';
		echo '			document.getElementById("seconds").value;';
		echo '	}else{';
		echo '		window.opener.document.getElementById("'.$this->name.'").value = date;';
		echo '	}';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		
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
		
		$date->populate_from_string($_GET["date"]);
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