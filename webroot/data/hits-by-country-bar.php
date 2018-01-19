<?php
// $Id: hits-by-country-bar.php 6 2009-09-16 19:59:52Z bvamos $

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

$query = "SELECT IFNULL(NULLIF(`c-ip-country`, ''), 'Unknown') country, COUNT(*) cnt FROM fmslog 
WHERE FROM_UNIXTIME(`connect-timestamp`, '%Y-%m-%d')>='$from_date' AND FROM_UNIXTIME(`connect-timestamp`, '%Y-%m-%d')<'$to_date'
GROUP BY country 
ORDER BY cnt DESC
LIMIT 10;";
$result = mysql_query($query);
if (!$result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
}

$ymax = 0;
while ($row = mysql_fetch_assoc($result)) {
    $yvalues[] = (int) $row['cnt'];
    if((int) $row['cnt']>$ymax) $ymax = (int) $row['cnt'];
    $xvalues[] = $row['country'];
}

$title = new title( 'Hits by Country - TOP 10 ('.$from_date.' - '.$to_date.')' );

$bar = new bar();
$bar->set_values( $yvalues );

$chart = new open_flash_chart();
$chart->set_title( $title );
$chart->add_element( $bar );

$y = new y_axis();
$y->set_range( 0, pow(10, floor(log10($ymax))) * ((int)substr($ymax, 0,1)+1));
$chart->set_y_axis( $y );

$x_labels = new x_axis_labels();
$x_labels->set_vertical();
$x_labels->set_labels( $xvalues );

$x = new x_axis();
$x->set_labels($x_labels);
$chart->set_x_axis($x);
                    
echo $chart->toString();


?>
