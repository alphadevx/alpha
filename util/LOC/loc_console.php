<?php

include 'loc.php';

$metrics = new metrics("../");
$metrics->calculate_LOC();
$metrics->results_to_console();

?>