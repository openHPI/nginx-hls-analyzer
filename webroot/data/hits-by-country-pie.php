<?php
// $Id: hits-by-country-pie.php 10 2009-09-21 13:01:03Z bvamos $

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
    $yvalues[] = new pie_value((int) $row['cnt'], $row['country']);
    if((int) $row['cnt']>$ymax) $ymax = (int) $row['cnt'];
    $xvalues[] = $row['country'];
}

$title = new title( 'Hits by Country - TOP 10 ('.$from_date.' - '.$to_date.')' );
//$title->set_style('font-size: 12px; font-weight: bold;');


$pie = new pie();
$pie->set_values( $yvalues );
$pie->set_tooltip('#val# of #total# (#percent#)');

$chart = new open_flash_chart();
$chart->set_title( $title );
$chart->add_element( $pie );

echo $chart->toString();


?>
