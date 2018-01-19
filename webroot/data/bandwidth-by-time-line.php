<?php
// $Id: bandwidth-by-time-line.php 10 2009-09-21 13:01:03Z bvamos $

include '../OFC/php-ofc-library/open-flash-chart.php';

// Open and read confg file
include('../../includes/config.php');
$CONFIG = new Config('../../fmsloganalyzer.ini');
if($CONFIG->error_msg()) die($CONFIG->error_msg());

// Parameters
$params = explode(';', $_GET['params']);
$from_date = $params[0];
$to_date = $params[1];

// Database connection
$db_username = $CONFIG->items['Database']['Username'];
$db_password = $CONFIG->items['Database']['Password'];
$db_host = $CONFIG->items['Database']['Server'];
$db_database = $CONFIG->items['Database']['Database'];

$DB = mysql_connect($db_host, $db_username, $db_password);
mysql_select_db($db_database, $DB); 

// Fill initial data
$start = strtotime($from_date);
$end = strtotime($to_date);
$intervallum = $end-$start;
if($intervallum<=3600*24){
	// Less than 1 days
	$step = 60;
	$dateformat_php = 'Y-m-d H:i';
	$dateformat_mysql = '%Y-%m-%d %H:%i';
	$x_step = 30;
	$title = '1 minute average';
}elseif($intervallum<=3600*24*7){
	// Less than 1 week
	$step = 3600;
	$dateformat_php = 'Y-m-d H';
	$dateformat_mysql = '%Y-%m-%d %H';
	$x_step = 4;
	$title = '1 hour average';
}elseif($intervallum<=3600*24*60){
	// Less than 2 months
	$step = 3600*24;
	$dateformat_php = 'Y-m-d';
	$dateformat_mysql = '%Y-%m-%d';
	$x_step = 2;
	$title = '1 day average';
}else{
	// More than 2 months
	$step = 3600*24*30;
	$dateformat_php = 'Y-m';
	$dateformat_mysql = '%Y-%m-01';
	$x_step = 1;
	$title = '1 month average';
}

$cur = $start;
while($cur<$end){
	$data[$cur] = 0;
	$cur += $step;
}

$query = "SELECT UNIX_TIMESTAMP(FROM_UNIXTIME(`connect-timestamp`, '$dateformat_mysql')) t, 
SUM(ROUND(`sc-stream-bytes`/1024*8/`x-duration`, 0))/$step bw
FROM fmslog
WHERE FROM_UNIXTIME(`connect-timestamp`, '%Y-%m-%d')>='$from_date' AND FROM_UNIXTIME(`connect-timestamp`, '%Y-%m-%d')<'$to_date'
AND `sc-stream-bytes`>0 
GROUP BY t;";
$result = mysql_query($query);
if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

$ymax = 0;
while ($row = mysql_fetch_assoc($result)) {
	$data[$row['t']] = (int) $row['bw'];
    if((int) $row['bw']>$ymax) $ymax = (int) $row['bw'];
}

foreach($data as $k=>$v){
	$yvalues[] = new scatter_value($k, $v);
	$xvalues[] = $k;
}

$charttitle = new title( "Bandwidth - $title ($from_date - $to_date)" );

$dot = new hollow_dot();
$dot->size(0);
$dot->halo_size(0);
$dot->tooltip("#date:$dateformat_php#<br>Value: #val# Kbit/sec");

$area = new area();
$area->set_width( 2 );
$area->set_default_dot_style($dot);
$area->set_colour( '#C4B86A' );
$area->set_fill_colour( '#C4B86A' );
$area->set_fill_alpha( 0.7 );
$area->set_values( $yvalues );

$chart = new open_flash_chart();
$chart->set_title( $charttitle );
$chart->add_element( $area );

$y = new y_axis();
$y->set_range( 0, pow(10, floor(log10($ymax))) * ((int)substr($ymax, 0,1)+1)  );
$chart->set_y_axis( $y );

$x_labels = new x_axis_labels();
$x_labels->text("#date:$dateformat_php#");
$x_labels->rotate(270);
$x_labels->set_steps( $step );
$x_labels->visible_steps($x_step);

$x = new x_axis();
$x->set_labels($x_labels);
$x->set_range($start, $end);
$x->set_steps( $step );

$chart->set_x_axis($x);
                    
echo $chart->toString();
?>
