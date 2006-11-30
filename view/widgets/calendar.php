<?php

// $Id$

if (!isset($sysRoot))
	$sysRoot = '../../';

if (!isset($sysURL))
	$sysURL = '../../';

require_once $sysRoot.'util/handle_error.inc';

require_once $sysRoot.'model/types/Date.inc';

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
	 * the date object for the widget
	 * @var date
	 */
	var $date_object = null;
	
	/**
	 * constructor
	 */
	function calendar($date_object) {
		$this->date_object = $date_object;
		
		$this->render_month();
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
		
		echo '<table border="1" cols="7" style="table-layout:fixed; width:260px; height:260px">';
		echo '<tr>';
		echo '<th colspan="7">'.$month_name.' '.$year.'</th>';
		echo '</tr>';
		echo '<tr>';
		echo '<th>Sun</th>';
		echo '<th>Mon</th>';
		echo '<th>Tue</th>';
		echo '<th>Wed</th>';
		echo '<th>Thu</th>';
		echo '<th>Fri</th>';
		echo '<th>Sat</th>';		
		echo '</tr>';	
		
		$day = 1;
		$wday = $first_week_day;
		$firstweek = true;
		while ( $day <= $lastday) {
			if ($firstweek) {
				echo "<TR>";
				for ($i=1; $i<=$first_week_day; $i++) {
				
				echo "<td>&nbsp;</td>";
			}
			$firstweek = false;
			}
			if ($wday==0) {
				echo "<tr>";
			}
			
			// make each day linkable to the following results.php page
			
			
			if ( intval($month_num) < 10) {
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
			
			if ($year == $this->date_object->year && $month == $this->date_object->month && $day == $this->date_object->day)
				echo "<td><strong>$day</strong></td>";
			else
				echo "<td>$day</td>";
			
			if ($wday==6) {
				echo "</tr>\n";
			}
			
			$wday++;
			$wday = $wday % 7;
			$day++;
		}
		
		while ($day > $lastday && $wday!=7) {
			echo "<td>&nbsp;</td>";
			$wday++;
		}
		
		echo '<tr>';
		echo '<td colspan="3">';
		echo '<a href="calendar.php?display_year='.($year-1).'&display_month='.$month.'"><<-</a>';
		echo 'Year';
		echo '<a href="calendar.php?display_year='.($year+1).'&display_month='.$month.'">->></a>';
		echo '</td>';
		echo '<td>&nbsp;</td>';
		echo '<td colspan="3">';
		echo '<a href="calendar.php?display_year='.$year.'&display_month='.($month-1).'"><<-</a>';
		echo 'Month';
		echo '<a href="calendar.php?display_year='.$year.'&display_month='.($month+1).'">->></a>';
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
	}
}

$date = new Date();

$cal = new calendar($date);


?>