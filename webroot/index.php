<?php
// $Id: index.php 11 2009-10-09 13:53:15Z bvamos $
// Open and read config file
include('../includes/config.php');
$CONFIG = new Config('../fmsloganalyzer.ini');
if ($CONFIG->error_msg())
    die($CONFIG->error_msg());


// Database connection
$db_username = $CONFIG->items['Database']['Username'];
$db_password = $CONFIG->items['Database']['Password'];
$db_host = $CONFIG->items['Database']['Server'];
$db_database = $CONFIG->items['Database']['Database'];

$DB = new mysqli($db_host, $db_username, $db_password, $db_database);
if ($DB->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
}

// Check date parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 day'));
if (!checkdate(substr($from_date, 5, 2), substr($from_date, 8, 2), substr($from_date, 0, 4)))
    $from_date = date('Y-m-d', strtotime('-30 day'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d H:m:s');
if (!checkdate(substr($to_date, 5, 2), substr($to_date, 8, 2), substr($to_date, 0, 4)))
    $to_date = date('Y-m-d H:m:s');
if (strtotime($to_date) - strtotime($from_date) > 3600 * 24 * 365) {
    die("ERROR: Time period can not be larger the 1 year.");
}

// Summary stats
$SummaryStats = array();
$SummaryStats['hits'] = summary_stats_hits($from_date, $to_date);
$SummaryStats['uniqueip'] = summary_stats_uniqueip($from_date, $to_date);
$SummaryStats['uniquecountry'] = summary_stats_uniquecountry($from_date, $to_date);
$SummaryStats['duration'] = summary_stats_duration($from_date, $to_date);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv=content-type content="text/html; charset=UTF-8" />
        <title>FMS Access Log Statistics</title>
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <script type="text/javascript" src="js/swfobject/swfobject.js"></script>
        <script type="text/javascript">
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart1", "400", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-country-bar.php?params=<?php echo "$from_date;$to_date"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart8", "400", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-country-pie.php?params=<?php echo "$from_date;$to_date"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart2", "600", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-day-bar.php?params=<?php echo "$from_date;$to_date;hit"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart3", "600", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-day-bar.php?params=<?php echo "$from_date;$to_date;traffic"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart4", "600", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-month-bar.php?params=<?php echo "$from_date;$to_date;hit"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart5", "600", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/hits-by-month-bar.php?params=<?php echo "$from_date;$to_date;traffic"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart6", "800", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/bandwidth-by-time-line.php?params=<?php echo "$from_date;$to_date;traffic"; ?>"});
            swfobject.embedSWF("OFC/open-flash-chart.swf", "chart7", "800", "300", "9.0.0", "expressInstall.swf", {"data-file": "data/peakbandwidth-by-time-line.php?params=<?php echo "$from_date;$to_date;traffic"; ?>"});
        </script>
    </head>
    <body>
        <script language="Javascript">
            function expandcollapse(obj) {
                document.getElementById(obj).style.display =
                        (document.getElementById(obj).style.display == 'none') ? '' : 'none';
            }
        </script>

        <h1>FMS Access Log Statistics - <?php print $CONFIG->items['Common']['Sitename']; ?></h1>
        <p><i>Time period: <?php print "$from_date - $to_date"; ?> 
                | Page generated: <?php print date('r'); ?></i></p>

        <table cellpadding="10" cellspacing="0" border="0" width="100%">
            <tr>
                <td width="160" valign="top" style="border-right: 1px dotted #ADADAD;">
                    <!-- Menu -->

                    <ul class="menu1">
                        <li><a href="#summarystats">Summary Stats</a></li>
                        <li><a href="#accessstats">Access Stats</a>
                            <ul class="menu2">
                                <li><a href="#top20files">TOP 20 Streams</a></li>
                            </ul></li>
                        <li><a href="#visitorstats">Visitor Stats</a>
                            <ul class="menu2">
                                <li><a href="#hitsbycountry">Hits by Country</a></li>
                                <li><a href="#top20uniqueips">TOP 20 Unique IPs</a></li>
                            </ul></li>
                        <li><a href="#activitystats">Activity Stat</a>
                            <ul class="menu2">
                                <li><a href="#dailyhits">Daily Hits</a></li>
                                <li><a href="#dailytraffic">Daily Traffic</a></li>
                                <li><a href="#monthlyhits">Monthly Hits</a></li>
                                <li><a href="#monthlytraffic">Monthly Traffic</a></li>
                                <li><a href="#bandwidth">Bandwidth</a></li>
                                <li><a href="#peakbandwidth">Peak Bandwidth</a></li>
                            </ul></li>
                    </ul>

                    <p style="border-bottom: 1px dotted #ADADAD;">&nbsp;</p>

                    <form method="get" action="" name="DatePickerForm">
                        <table cellspacing="0" cellpadding="2" border="0">
                            <tr>
                                <td>From:</td>
                                <td><input type="text" name="from_date" value="<?php echo $from_date; ?>" size="10" /></td>
                            </tr>
                            <tr>
                                <td>to:</td>
                                <td><input type="text" name="to_date" value="<?php echo $to_date; ?>" size="10" /></td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center"><input type="button" value="Reset" onclick="window.location = 'index.php';" />
                                    <input type="submit" value="Refresh" /></td>
                            </tr>
                        </table>
                    </form>

                    <!-- Menu end -->
                </td>
                <td valign="top">
                    <!-- Content -->

                    <a name="summarystats"></a><h2>Summary Stats</h2>
                    <table cellspacing="2" cellpadding="2" border="0">
                        <tr>
                            <td><b>Hits:</b></td>
                            <td align="right"><?php echo number_format($SummaryStats['hits']); ?></td>
                        </tr>
                        <tr>
                            <td><b>Unique IPs:</b></td>
                            <td align="right"><?php echo number_format($SummaryStats['uniqueip']); ?></td>
                        </tr>
                        <tr>
                            <td><b>Unique Countries:</b></td>
                            <td align="right"><?php echo number_format($SummaryStats['uniquecountry']); ?></td>
                        </tr>
                        <tr>
                            <td><b>Duration in sec <i>(Sum/Max/Min)</i>:</b></td>
                            <td align="right"><?php echo number_format($SummaryStats['duration']['sumdur']) . ' / ' . number_format($SummaryStats['duration']['maxdur']) . ' / ' . number_format($SummaryStats['duration']['mindur']); ?></td>
                        </tr>
                    </table>

                    <a name="accessstats"></a><h2>Access stats</h2>

                    <a name="top20files"></a><h3>TOP 20 streams</h3>
                    <table cellpadding="2" cellspacing="1" border="0">
                        <thead>
                            <tr>
                                <th>Stream</th>
                                <th>Hits</th>
                                <th>Traffic (MByte)</th>
                                <th>Average duration (Sec)</th>
                                <th>Average bandwidth (Kbit/sec)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $files = summary_stats_allfiles($from_date, $to_date, 1000);
                            $i = 0;
                            foreach ($files as $v) {
                                $class = ($i % 2) ? 'odd' : '';
                                if ($i == 20)
                                    print '</tbody><tbody id="top20filesmore" style="display: none;">';
                                print '<tr>
  <td class="' . $class . '">' . $v[0] . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[1]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[2]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[3]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[4]) . '</td>
  </tr>';
                                $i++;
                            }
                            ?>
                        </tbody>
                    </table>
                    <br/><a href="javascript:expandcollapse('top20filesmore')">[+/-] Show all/Show TOP 20</a>

                    <a name="visitorstats"></a><h2>Visitor stats</h2>

                    <a name="hitsbycountry"></a><h3>Hits by Country</h3>
                    <div id="chart1"></div>
                    <div id="chart8"></div>

                    <h3>TOP 20 Unique IPs</h3>
                    <table cellpadding="2" cellspacing="1" border="0">
                        <thead>
                            <tr>
                                <th>Client IP</th>
                                <th>Hits</th>
                                <th>Traffic (MByte)</th>
                                <th>Average duration (Sec)</th>
                                <th>Average bandwidth (Kbit/sec)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $files = summary_stats_uniqueip_list($from_date, $to_date, 1000);
                            $i = 0;
                            foreach ($files as $v) {
                                $class = ($i % 2) ? 'odd' : '';
                                if ($i == 20)
                                    print '</tbody><tbody id="top20uniqueips" style="display: none;">';
                                print '<tr>
  <td class="' . $class . '">' . $v[0] . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[1]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[2]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[3]) . '</td>
  <td class="' . $class . '" align="right">' . number_format($v[4]) . '</td>
  </tr>';
                                $i++;
                            }
                            ?>
                        </tbody>
                    </table>
                    <br/><a href="javascript:expandcollapse('top20uniqueips')">[+/-] Show all/Show TOP 20</a>


                    <a name="activitystats"></a><h2>Activity stats</h2>

                    <a name="dailyhits"></a><h3>Daily Hits</h3>
                    <div id="chart2"></div>

                    <a name="dailytraffic"></a><h3>Daily Traffic</h3>
                    <div id="chart3"></div>

                    <a name="monthlyhits"></a><h3>Monthly Hits</h3>
                    <div id="chart4"></div>

                    <a name="monthlytraffic"></a><h3>Monthly Traffic</h3>
                    <div id="chart5"></div>

                    <a name="bandwidth"></a><h3>Bandwidth</h3>
                    <div id="chart6"></div>

                    <a name="peakbandwidth"></a><h3>Peak Bandwidth</h3>
                    <div id="chart7"></div>


                    <!-- Content end -->
                </td>
            </tr>
        </table>

        <div id="footer">&nbsp;FMS Log Analyzer v<?php print $VERSION; ?> 
            | Copyright &copy; 2009 Balazs Vamos. All rights reserved.
            | <a href="http://www.fmsloganalyzer.com" target="_blank">http://www.fmsloganalyzer.com</a></div>

    </body>
</html>
<?php

function summary_stats_hits($from_date, $to_date) {
    global $DB;
    $query = "SELECT COUNT(*) cnt "
            . "FROM `connections` "
            . "WHERE `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "'";
    $result = $DB->query($query);

    if (!$result) {
        $message = 'Invalid query: ' . $DB->error . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {
        $resultoutput = $result->fetch_assoc();
    }
    $result->close();

    return $resultoutput['cnt'];
}

function summary_stats_uniqueip($from_date, $to_date) {
    global $DB;

    $query = "SELECT DISTINCT `c-client-id` "
            . "FROM connections "
            . "WHERE `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "' "
            . "AND `c-ip`!='-'";
    $result = $DB->query($query);
    if (!$result) {
        $message = 'Invalid query: ' . $DB->error . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {
        $resultoutput = $result->num_rows;
    }
    $result->close();

    return $resultoutput;
}

function summary_stats_uniquecountry($from_date, $to_date) {
    global $DB;

    $query = "SELECT DISTINCT `c-ip-country` "
            . "FROM connections "
            . "WHERE `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "' ";
    $result = $DB->query($query);
    if (!$result) {
        $message = 'Invalid query: ' . $DB->error . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {
        $resultoutput = $result->num_rows;
    }
    $result->close();

    return $resultoutput;
}

function summary_stats_allfiles($from_date, $to_date, $limit = 20) {
    global $DB;

    $query = "SELECT `streamname`, "
            . "COUNT(*) cnt, "
            . "ROUND(SUM(`bytes`)/1024/104, 0) AS traffic_mbyte, "
            . "ROUND(AVG(`duration`), 0) AS avgduration, "
            . "ROUND(SUM(`bytes`)*8/1024/SUM(`duration`), 0) AS avgbandwidth "
            . "FROM connections "
            . "WHERE `streamname`!='-' "
            . "AND `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "' "
            . "GROUP BY `streamname` "
            . "ORDER BY cnt DESC "
            . "LIMIT " . $limit;
    $result = $DB->query($query);
    if (!$result) {
        $message = 'Invalid query: ' . $DB->error . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {

        $ret = array();
        if ($result->num_rows == 0) {
            $resultoutput = $ret;
        }

        while ($row = $result->fetch_array()) {
            $ret[] = $row;
        }

        $resultoutput = $ret;
    }
    $result->close();

    return $resultoutput;
}

function summary_stats_uniqueip_list($from_date, $to_date, $limit = 20) {
    global $DB;

    $query = "SELECT `c-ip`, "
            . "COUNT(*) cnt, "
            . "ROUND(SUM(`bytes`)/1024/104, 0) AS traffic_mbyte, "
            . "ROUND(AVG(`duration`), 0) AS avgduration, "
            . "ROUND(SUM(`bytes`)*8/1024/SUM(`duration`), 0) AS avgbandwidth "
            . "FROM connections  "
            . "WHERE `c-ip`!='-' "
            . "AND `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "' "
            . "GROUP BY `c-ip` "
            . "ORDER BY cnt DESC "
            . "LIMIT " . $limit;
    $result = $DB->query($query);
    if (!$result) {
        $message = 'Invalid query: ' . mysql_error() . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {

        $ret = array();
        if ($result->num_rows == 0) {
            $resultoutput = $ret;
        }

        while ($row = $result->fetch_array()) {
            $ret[] = $row;
        }

        $resultoutput = $ret;
    }
    $result->close();

    return $resultoutput;
}

function summary_stats_duration($from_date, $to_date) {
    global $DB;

    $query = "SELECT SUM(`duration`) sumdur, "
            . "MAX(`duration`) maxdur, "
            . "MIN(`duration`) mindur "
            . "FROM connections "
            . "WHERE `timestamp-start` "
            . "BETWEEN '" . $from_date . "' AND '" . $to_date . "' ";
    $result = $DB->query($query);
    if (!$result) {
        $message = 'Invalid query: ' . mysql_error() . "\n";
        $message .= 'Whole query: ' . $query;
        die($message);
    } else {
        $resultoutput = $result->fetch_assoc();
    }
    $result->close();

    return array('sumdur' => $resultoutput['sumdur'],
        'maxdur' => $resultoutput['maxdur'],
        'mindur' => $resultoutput['mindur']
    );
}
?>
