<?php
// $Id: process.php 9 2009-09-16 21:18:41Z bvamos $

// Running stat
$starttime = time();

include('includes/config.php');
include('includes/GeoIP/geoip.inc');

// Open and read config file
$CONFIG = new Config();
if(!$CONFIG) die($CONFIG->error_msg());

$DIRSEP = $CONFIG->items['Common']['DirectorySeparator'];
$ClientArray = array();

// GeoIP
$gi = geoip_open('includes/GeoIP/GeoIP.dat',GEOIP_STANDARD);

// Open log files
if ($handle = opendir($CONFIG->items['Common']['LogDirectory'])) {
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {

			// Process a log file
			//echo "File: $file\n";
			$fh = fopen($CONFIG->items['Common']['LogDirectory'].$DIRSEP.$file, 'r');
			$row = 1;
			while (($data = fgetcsv($fh, 1000, "\t")) !== FALSE) {
				// Count fields in a row
				$num = count($data);
				if($num>1){
					// Column names
					//echo "<p> $num fields in line $row: <br /></p>\n";
					if(substr($data[0], 0, 8)=='#Fields:'){
						// Row of Column names
						$ColumnNames = array();
						for ($c=0; $c < $num; $c++) {
							//echo $data[$c] . "<br />\n";
							if($c==0) $data[$c] = substr($data[$c], 9);
							$ColumnNames[$data[$c]] = $c;
						}
						//print_r($ColumnNames);
					}else{
						// Log row
						//$column = $ColumnNames['x-file-name'];
						//echo "$file ($row,".$column."): ".$data[$column]."\n";

						// Filters
						if(isset($CONFIG->items['Filter']['Virtualhost']))
						if($data[$ColumnNames['x-vhost']]!=$CONFIG->items['Filter']['Virtualhost']){
							//print "Virtualhost Filter does not match: ".$data[$ColumnNames['x-vhost']]."!=".$CONFIG->items['Filter']['Virtualhost']."\n";
							continue;
						}else{
							//print "Virtualhost Filter does match: ".$data[$ColumnNames['x-vhost']]."==".$CONFIG->items['Filter']['Virtualhost']."\n";
						}

						// Create log rows
						$column = $ColumnNames['c-client-id'];
						$d = $data[$column];
						if($d!='-'){
							$ClientArray[$d]['c-ip'] = $data[$ColumnNames['c-ip']];
							if($data[$ColumnNames['x-file-name']]!='-') $ClientArray[$d]['x-file-name'] = $data[$ColumnNames['x-file-name']];
							if($data[$ColumnNames['x-sname']]!='-') $ClientArray[$d]['x-sname'] = $data[$ColumnNames['x-sname']];
							$ClientArray[$d]['log'][$data[$ColumnNames['x-event']]] = array(
                'x-event' => $data[$ColumnNames['x-event']],
                'x-category' => $data[$ColumnNames['x-category']],
                'date' => $data[$ColumnNames['date']],
                'time' => $data[$ColumnNames['time']],
                'tz' => $data[$ColumnNames['tz']],
                'timestamp' => strtotime($data[$ColumnNames['date']].' '.$data[$ColumnNames['time']].' '.$data[$ColumnNames['tz']]),
                'x-duration' => $data[$ColumnNames['x-duration']],
                'sc-bytes' => $data[$ColumnNames['sc-bytes']],
                'sc-stream-bytes' => $data[$ColumnNames['sc-stream-bytes']],
							);
						}

					}
					$row++;
				}else{
					// Comment or empty line
				}
			}
			fclose($fh);
		}
	}
	closedir($handle);
}


// Database connection
$db_username = $CONFIG->items['Database']['Username'];
$db_password = $CONFIG->items['Database']['Password'];
$db_host = $CONFIG->items['Database']['Server'];
$db_database = $CONFIG->items['Database']['Database'];

$DB = mysql_connect($db_host, $db_username, $db_password);
mysql_select_db($db_database, $DB);

// Truncate table
$query = 'TRUNCATE TABLE fmslog';
$result = mysql_query($query, $DB);
if (!$result) {
	$message  = 'Invalid query: ' . mysql_error() . "; ";
	$message .= 'Whole query: ' . $query;
	print "$message\n";
}

$i = 0;
$j = 0;
$query = "INSERT INTO fmslog () VALUES ";
$query_values = '';
$LogArray = array();
foreach($ClientArray as $ck=>$cv){
	$c_client_id = $ck;
	$c_ip = $cv['c-ip'];
	$c_ip_country = geoip_country_name_by_addr($gi, $cv['c-ip']);
	$x_file_name = isset($cv['x-file-name']) ? substr($cv['x-file-name'], 0, 60) : '-';
	$x_sname = isset($cv['x-sname']) ? $cv['x-sname'] : '-';
	$connect_timestamp = isset($cv['log']['connect']['timestamp']) ? $cv['log']['connect']['timestamp'] : 0;
	$disconnect_timestamp = isset($cv['log']['disconnect']['timestamp']) ? $cv['log']['disconnect']['timestamp'] : 0;
	$play_timestamp = isset($cv['log']['play']['timestamp']) ? $cv['log']['play']['timestamp'] : 0;
	$stop_timestamp = isset($cv['log']['stop']['timestamp']) ? $cv['log']['stop']['timestamp'] : 0;
	$pause_timestamp = isset($cv['log']['pause']['timestamp']) ? $cv['log']['pause']['timestamp'] : 0;
	$unpause_timestamp = isset($cv['log']['unpause']['timestamp']) ? $cv['log']['unpause']['timestamp'] : 0;
	$x_duration = isset($cv['log']['stop']['x-duration']) ? $cv['log']['stop']['x-duration'] : 0;
	$sc_bytes = (isset($cv['log']['disconnect']['sc-bytes']) AND isset($cv['log']['connect']['sc-bytes'])) ? $cv['log']['disconnect']['sc-bytes'] - $cv['log']['connect']['sc-bytes'] : 0;
	$sc_stream_bytes = (isset($cv['log']['play']['sc-stream-bytes']) AND isset($cv['log']['stop']['sc-stream-bytes'])) ? $cv['log']['stop']['sc-stream-bytes'] - $cv['log']['play']['sc-stream-bytes'] : 0;

	if($j>19){
		$result = mysql_query($query.$query_values, $DB);
		if (!$result) {
			$message  = 'Invalid query: ' . mysql_error() . "; ";
			$message .= 'Whole query: ' . $query;
			print "$message\n";
		}
		$query_values = '';
		$j = 0;
	}
	if($query_values) $query_values .= ', ';
	$query_values .= "($c_client_id, '$c_ip', '$c_ip_country', '$x_file_name', '$x_sname', $connect_timestamp, $disconnect_timestamp, $play_timestamp, $stop_timestamp, $pause_timestamp, $unpause_timestamp, $x_duration, $sc_bytes, $sc_stream_bytes)";
	$j++;

	$i++;
}
$result = mysql_query($query.$query_values, $DB);
if (!$result) {
	$message  = 'Invalid query: ' . mysql_error() . "; ";
	$message .= 'Whole query: ' . $query;
	print "$message\n";
}


geoip_close($gi);

$endtime = time();
$runtime_in_sec = $endtime - $starttime;

echo "Running time: $runtime_in_sec sec; ";
echo "Rows processed: $i\n";

?>