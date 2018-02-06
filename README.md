# nginx-hls-analyzer (Analyze LiveStream Access)

nginx-hls-analyzer is a nginx (with compiled nginx-rtmp-module) HLS LiveStream access log analyzer written in PHP and using MySQL as a backend database store. Supported log file format is the access log format of nginx with the following settings (http-area-settings).

```nginx
log_format json_combined escape=json '{ "time_local": "$time_local", '
                                         '"remote_addr": "$remote_addr", '
                                         '"connection": "$connection", '
                                         '"remote_user": "$remote_user", '
                                         '"request": "$request", '
                                         '"status": "$status", '
                                         '"bytes_sent": "$bytes_sent", '
                                         '"request_time": "$request_time", '
                                         '"http_referrer": "$http_referer", '
                                         '"http_user_agent": "$http_user_agent" }';

access_log  logs/access.log  json_combined;
```

## PREREQUISITES

- A web server with PHP >= 5
- Access log files with HLS access of nginx with json log file format
- A MySQL/MariaDB database server >= 3.23.52 or any production release of 4.x or 5.x
- Web browser

## Installation

- Copy all the files into a directory and set up an alias or virtual host with webroot as the Document root.
- Create a MySQL database and create necessary tables. SQL can be found in db/install.sql.

## Usage

- Before starting FMS Log Analyzer for the first time, you should review the fmsloganalyzer.ini.sample file, rename to fmsloganalyzer.ini and adjust it as needed for your installation.
- Run process.php to generate statistic data from log files.
- You can see statistics by pointing your web browser to:
  - http://<location>/index.php
- When you load the FMS Log Analyzer home page, you will see a couple of valued reports from your FMS access logs.