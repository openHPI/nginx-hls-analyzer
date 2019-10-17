<?php
// $Id: index.php 11 2009-10-09 13:53:15Z bvamos $

// Open and read config file
include('../includes/config.php');
$CONFIG = new Config('../fmsloganalyzer.ini');
if($CONFIG->error_msg()) die($CONFIG->error_msg());

// Database connection
$db_username = $CONFIG->items['Database']['Username'];
$db_password = $CONFIG->items['Database']['Password'];
$db_host = $CONFIG->items['Database']['Server'];
$db_database = $CONFIG->items['Database']['Database'];

$DB = mysql_connect($db_host, $db_username, $db_password);
if(!$DB) die(mysql_error());
$result = mysql_select_db($db_database, $DB);
if(!$result) die(mysql_error());

$xtime = 0;

if (isset ($_REQUEST["xtime"]))
{
  $xtime = $_REQUEST["xtime"] + 0;
}

$liveonly = isset ($_REQUEST["live"]);
$teletestonly = isset ($_REQUEST["teletest"]);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv=content-type content="text/html; charset=UTF-8" />
  <title>FMS Access Log Statistics</title>
  <link rel="stylesheet" type="text/css" href="css/style.css" />
</head>
<body>

<h1>FMS Access Log Statistics - <?php print $CONFIG->items['Common']['Sitename']; ?></h1>
<p><i>Time period:  last 80 days
| Page generated: <?php print date('r'); ?></i></p>




<?php 
  if ($xtime == 0)
  {
?>

<h3>last 80 days</h3>
<table cellpadding="2" cellspacing="1" border="0">
  <thead>
  <tr>
    <th>Date</th>
    <th>concurrent connections</th>
  </tr>
  </thead>
  <tbody>
<?php
$files = summary_concurrent();
$i = 0;
foreach($files as $v){
  $class = ($i%2) ? 'odd' : '';
  print '<tr>
  <td class="'.$class.'"><a href="concurrent.php?xtime='.$v[1].'">'.$v[0].'</a></td>
  <td class="'.$class.'" align="right">'.number_format($v[2]).'</td>
  </tr>';
  $i++;
}
?>
  </tbody>
</table>

<?php
 }
 else
 {
?>
<h3> Concurrent Connections </h3>
<table cellpadding="2" cellspacing="1" border="0">
  <thead>
  <tr>
    <th>Date</th>
    <th>Stream name</th>
    <th>concurrent connections</th>
  </tr>
  </thead>
  <tbody>
<?php
$files = concurrent_streamname();
$i = 0;
foreach($files as $v){
  $class = ($i%2) ? 'odd' : '';
  print '<tr>
  <td class="'.$class.'">'.$v[0].'</td>
  <td class="'.$class.'">'.$v[1].'</td>
  <td class="'.$class.'" align="right">'.number_format($v[2]).'</td>
  </tr>';
  $i++;
}
?>
  </tbody>
</table>

<br/>

<h3> Details: </h3>
<table cellpadding="2" cellspacing="1" border="0">
  <thead>
  <tr>
    <th>Date</th>
    <th>Stream name</th>
    <th>number of seconds this stream was active</th>
  </tr>
  </thead>
  <tbody>
<?php
$files = concurrent_detail();
$i = 0;
foreach($files as $v){
  $class = ($i%2) ? 'odd' : '';
  print '<tr>
  <td class="'.$class.'">'.$v[0].'</td>
  <td class="'.$class.'">'.$v[1].'</td>
  <td class="'.$class.'" align="right">'.number_format($v[2]).'</td>
  </tr>';
  $i++;
}
?>
  </tbody>
</table>


<?php
 }
?>

<div id="footer">&nbsp;FMS Log Analyzer v<?php print $VERSION; ?> 
| Copyright &copy; 2009 Balazs Vamos. All rights reserved.
| <a href="http://www.fmsloganalyzer.com" target="_blank">http://www.fmsloganalyzer.com</a></div>

</body>
</html>
<?php

function summary_concurrent(){
  global $DB;
  global $liveonly;
  global $teletestonly;

  $where = "";
  if ($liveonly)
  {
    $where = "WHERE streamname LIKE 'live%'";
  }
  if ($teletestonly)
  {
    $where = "WHERE streamname LIKE 'vod/WS_2013/SVV%'";
  }

  $query = "SELECT FROM_UNIXTIME(xtime) AS utime, xtime, COUNT(*) AS conc FROM concurrent $where GROUP BY xtime ORDER BY xtime DESC ";


  $result = mysql_query($query, $DB);
  if (!$result) {
      $message  = 'Invalid query: ' . mysql_error() . "\n";
      $message .= 'Whole query: ' . $query;
      die($message);
  }

 $ret = array();
  if (mysql_num_rows($result) == 0) {
    return $ret;
  }

  while($row = mysql_fetch_array($result)){
    $ret[] = $row;
  }
  return $ret;

}

function concurrent_detail(){
  global $DB;
  global $xtime;

  $query = "SELECT FROM_UNIXTIME(xtime), streamname, viewlength FROM concurrent where xtime = ".$xtime." ORDER BY streamname";

//  print $query;

  $result = mysql_query($query, $DB);
  if (!$result) {
      $message  = 'Invalid query: ' . mysql_error() . "\n";
      $message .= 'Whole query: ' . $query;
      die($message);
  }

 $ret = array();
  if (mysql_num_rows($result) == 0) {
    return $ret;
  }

  while($row = mysql_fetch_array($result)){
    $ret[] = $row;
  }
  return $ret;

}

function concurrent_streamname(){
  global $DB;
  global $xtime;

  $query = "SELECT FROM_UNIXTIME(xtime), streamname, count(*) FROM concurrent where xtime = ".$xtime." GROUP BY streamname ORDER BY streamname";

//  print $query;

  $result = mysql_query($query, $DB);
  if (!$result) {
      $message  = 'Invalid query: ' . mysql_error() . "\n";
      $message .= 'Whole query: ' . $query;
      die($message);
  }

 $ret = array();
  if (mysql_num_rows($result) == 0) {
    return $ret;
  }

  while($row = mysql_fetch_array($result)){
    $ret[] = $row;
  }
  return $ret;

}




?>

