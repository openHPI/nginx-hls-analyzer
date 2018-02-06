<?php

// $Id: process.php 9 2009-09-16 21:18:41Z bvamos $
// Running stat
$starttime = time();

include('includes/config.php');
include('includes/GeoIP/geoip.inc');

// Open and read config file
$CONFIG = new Config();
if (!$CONFIG)
    die($CONFIG->error_msg());

// Database connection
$db_username = $CONFIG->items['Database']['Username'];
$db_password = $CONFIG->items['Database']['Password'];
$db_host = $CONFIG->items['Database']['Server'];
$db_database = $CONFIG->items['Database']['Database'];

$DB = new mysqli($db_host, $db_username, $db_password, $db_database);

$handle = @fopen($CONFIG->items['Common']['NginxAccessLogLocation'], "r");
if ($handle) {
    while (($line = fgets($handle, 4096)) !== false) {
        
        $line_data = json_decode($line);
        if($line_data)
        {
            if($line_data->status == "200" && strpos($line_data->request, 'ET '. $CONFIG->items['Common']['NginxStreamFolder']))
            {
                //Time
                $transfer_time = logdate_to_mysqldatetime($line_data->time_local);

                //Check if time is already recorded
                $query = 'SELECT time FROM `last_update`';
                $result = $DB->query($query);
                if (!$result) {
                    $message = 'Invalid query: ' . $DB->error . "\n";
                    $message .= 'Whole query: ' . $query;
                    die($message);
                } else {
                    $resultoutput = $result->fetch_assoc();
                }
                $result->close();
                
                if($resultoutput['time'] <= $transfer_time)
                {
                    //IP-Address
                    $ip_addr = $line_data->remote_addr;

                    //User Agent
                    $user_agent = $line_data->http_user_agent;
                    
                    //Connection ID
                    $connection_id = $line_data->connection;

                    //Checksum with connection
                    $client_id_w_conn = md5($ip_addr.$connection_id.$user_agent);
                    
                    //Checksum with connection
                    $client_id = md5($ip_addr.$user_agent);

                    //Bytes Transfered
                    $bytes_transfered = $line_data->bytes_sent;

                    //StreamName
                    $PathToStream = explode(' ', $line_data->request)[1];
                    $PathToStream = explode($CONFIG->items['Common']['NginxStreamFolder'], $PathToStream)[1];
                    if(!strpos($PathToStream, '.m3u8') && $PathToStream != '')
                    {
                        $stream_path = explode('/', $PathToStream)[0];
                        $stream_path = explode('_', $stream_path);

                        $stream_name = $stream_path[0];
                        $stream_quality = $stream_path[1];

                        //TODO: Not implemented
                        $location = "";

                        //Save Log to Database
                        $DB->query('INSERT INTO `log` '
                                . '(`c-client-id`, `c-client-id-conn`, `c-ip`,`c-agent`'
                                . ',`c-ip-country`, `streamname`, `streamquality`,'
                                . '`connection-id`, `timestamp`, `bytes`) VALUES'
                                . '("'.$client_id.'", "'.$client_id_w_conn.'", "'.$ip_addr.'",'
                                . '"'.$user_agent.'", "'.$location.'",'
                                . '"'.$stream_name.'", "'.$stream_quality.'",'
                                . '"'.$connection_id.'",'
                                . '"'.$transfer_time.'", "'.$bytes_transfered.'")');
                        
                        $DB->query('UPDATE `last_update` SET time = "'.$transfer_time.'"');
                    }
                }
            }
        }
    }
    if (!feof($handle)) {
        echo "Fehler: unerwarteter fgets() Fehlschlag\n";
    }
    fclose($handle);
}

//Evaluate Inputs
//Check if time is already recorded
$query = 'SELECT `id`, `c-client-id`, `c-ip`, `c-agent`, `c-ip-country`, `streamname`, `timestamp`, `bytes` FROM '
        . '`log` WHERE evaluated = 0 ORDER BY timestamp ASC';
$result = $DB->query($query);
if (!$result) {
    $message = 'Invalid query: ' . $DB->error . "\n";
    $message .= 'Whole query: ' . $query;
    die($message);
} else {
    while ($loginfo=$result->fetch_object())
    {
        
        //Check if client-id is already recorded within last 2 minutes
        $query = 'SELECT COUNT(*) as cnt FROM `connections` '
                . 'WHERE `c-client-id` = "'.$loginfo->{'c-client-id'}.'" AND '
                . '`streamname` = "'.$loginfo->streamname.'" AND "'
                . $loginfo->timestamp.'" BETWEEN '
                . '`timestamp-end` AND DATE_ADD(`timestamp-end`, INTERVAL 1 MINUTE)';
                
        $result_connections = $DB->query($query);
        if (!$result_connections) {
            $message = 'Invalid query: ' . $DB->error . "\n";
            $message .= 'Whole query: ' . $query;
            die($message);
        } else {
            $resultoutput = $result_connections->fetch_assoc();
        }
        $result_connections->close();
        if($resultoutput['cnt'] > 0)
        {
            $query = 'UPDATE `connections` SET '
                . '`bytes` = `bytes` + '.$loginfo->bytes.', '
                . '`timestamp-end` = "'.$loginfo->timestamp.'", '
                . '`duration` = TIME_TO_SEC(TIMEDIFF("'.$loginfo->timestamp.'", `timestamp-start`)) '
                . 'WHERE `c-client-id` ="'.$loginfo->{'c-client-id'}.'" AND '
                . '`streamname` = "'.$loginfo->streamname.'" AND "'
                . $loginfo->timestamp.'" BETWEEN '
                . '`timestamp-end` AND DATE_ADD(`timestamp-end`, INTERVAL 1 MINUTE)';
                
            //Update
            $DB->query($query);
            
        } else {
            //Insert
            $DB->query('INSERT INTO `connections` '
                . '(`c-client-id`, `c-ip`,`c-agent`'
                . ',`c-ip-country`, `streamname`, `timestamp-start`,'
                . '`timestamp-end`, `bytes`, `duration`) VALUES'
                . '("'.$loginfo->{'c-client-id'}.'", "'.$loginfo->{'c-ip'}.'", "'.$loginfo->{'c-agent'}.'",'
                . '"'.$loginfo->{'c-ip-country'}.'", "'.$loginfo->streamname.'", "'.$loginfo->timestamp.'",'
                . '"'.$loginfo->timestamp.'", "'.$loginfo->bytes.'", "0")');
        }
        //Update log entry to evaluated
        $DB->query('UPDATE `log` SET evaluated = "1" WHERE id = '.$loginfo->id);
    }
    // Free result set
    $result->close();
}

// Open log files
/*if ($handle = opendir($CONFIG->items['Common']['LogDirectory'])) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {

            // Process a log file
            echo "File: $file\n";
            echo $CONFIG->items['Common']['LogDirectory'] . $DIRSEP . $file;
            $fh = fopen($CONFIG->items['Common']['LogDirectory'] . $DIRSEP . $file, 'r');
            $row = 1;
            while ($line = fgets($fh, 4096) !== FALSE) {

                echo $line;

                $whitespace_splits = explode(' ', $line);
                var_dump($whitespace_splits);
                $ip_addr = $whitespace_splits[0];
                echo $ip_addr . "<br>";

                // Count fields in a row
                 $num = count($data);
                  echo $num;
                  if ($num > 1) {
                  // Column names
                  //echo "<p> $num fields in line $row: <br /></p>\n";
                  if (substr($data[0], 0, 8) == '#Fields:') {
                  // Row of Column names
                  $ColumnNames = array();
                  for ($c = 0; $c < $num; $c++) {
                  //echo $data[$c] . "<br />\n";
                  if ($c == 0)
                  $data[$c] = substr($data[$c], 9);
                  $ColumnNames[$data[$c]] = $c;
                  }
                  //print_r($ColumnNames);
                  }else {
                  // Log row
                  //$column = $ColumnNames['x-file-name'];
                  //echo "$file ($row,".$column."): ".$data[$column]."\n";
                  // Filters
                  if (isset($CONFIG->items['Filter']['Virtualhost']))
                  if ($data[$ColumnNames['x-vhost']] != $CONFIG->items['Filter']['Virtualhost']) {
                  //print "Virtualhost Filter does not match: ".$data[$ColumnNames['x-vhost']]."!=".$CONFIG->items['Filter']['Virtualhost']."\n";
                  continue;
                  } else {
                  //print "Virtualhost Filter does match: ".$data[$ColumnNames['x-vhost']]."==".$CONFIG->items['Filter']['Virtualhost']."\n";
                  }

                  // Create log rows
                  $column = $ColumnNames['c-client-id'];
                  $d = $data[$column];
                  if ($d != '-') {
                  $ClientArray[$d]['c-ip'] = $data[$ColumnNames['c-ip']];
                  if ($data[$ColumnNames['x-file-name']] != '-')
                  $ClientArray[$d]['x-file-name'] = $data[$ColumnNames['x-file-name']];
                  if ($data[$ColumnNames['x-sname']] != '-')
                  $ClientArray[$d]['x-sname'] = $data[$ColumnNames['x-sname']];
                  $ClientArray[$d]['log'][$data[$ColumnNames['x-event']]] = array(
                  'x-event' => $data[$ColumnNames['x-event']],
                  'x-category' => $data[$ColumnNames['x-category']],
                  'date' => $data[$ColumnNames['date']],
                  'time' => $data[$ColumnNames['time']],
                  'tz' => $data[$ColumnNames['tz']],
                  'timestamp' => strtotime($data[$ColumnNames['date']] . ' ' . $data[$ColumnNames['time']] . ' ' . $data[$ColumnNames['tz']]),
                  'x-duration' => $data[$ColumnNames['x-duration']],
                  'sc-bytes' => $data[$ColumnNames['sc-bytes']],
                  'sc-stream-bytes' => $data[$ColumnNames['sc-stream-bytes']],
                  );
                  }
                  }
                  $row++;
                  }else {
                  // Comment or empty line
                  }
            }
            fclose($fh);
        }
    }
    closedir($handle);
}

$query = "INSERT INTO fmslog () VALUES ";
$query_values = '';
$LogArray = array();
foreach ($ClientArray as $ck => $cv) {
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

    if ($j > 19) {
        $result = mysql_query($query . $query_values, $DB);
        if (!$result) {
            $message = 'Invalid query: ' . mysql_error() . "; ";
            $message .= 'Whole query: ' . $query;
            print "$message\n";
        }
        $query_values = '';
        $j = 0;
    }
    if ($query_values)
        $query_values .= ', ';
    $query_values .= "($c_client_id, '$c_ip', '$c_ip_country', '$x_file_name', '$x_sname', $connect_timestamp, $disconnect_timestamp, $play_timestamp, $stop_timestamp, $pause_timestamp, $unpause_timestamp, $x_duration, $sc_bytes, $sc_stream_bytes)";
    $j++;

    $i++;
}
$result = mysql_query($query . $query_values, $DB);
if (!$result) {
    $message = 'Invalid query: ' . mysql_error() . "; ";
    $message .= 'Whole query: ' . $query;
    print "$message\n";
}*/

$endtime = time();
$runtime_in_sec = $endtime - $starttime;

echo "Running time: ".$runtime_in_sec."sec; ";

function logdate_to_mysqldatetime($timestring)
{
    $timestring = str_replace_first(':' , ' ', $timestring);
    $timestring = str_replace('/', '-', $timestring);
    
    // Instantiate a DateTime with microseconds.
    $d = new DateTime($timestring);

    // Output the date with microseconds.
    return $d->format('Y-m-d H:i:s'); // 2011-01-01 15:03:01
}

function str_replace_first($from, $to, $subject)
{
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $subject, 1);
}
?>