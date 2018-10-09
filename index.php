<?php

function parce_to_row($line) {
    global $file;
    $matches=parce_log_string($file,$line);
    $date         =  $matches[1];
    $host         =  $matches[2];
    $switch_date  =  $matches[3];
    $switch_log   =  $matches[4];
    if (isset($date)) {
        $row .= "
            <tr>
                <td>" . $date        . "</td>
                <td>" . $host        . "</td>
                <td>" . $switch_date . "</td>
                <td>" . $switch_log  . "</td>
            </tr>";
    }
    return $row;
}

function parce_log_string($file,$line) {
    if (strpos($file, '172.21.199') !== false) {
        preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s*[\d|\.]*\s*\d*\:*\s*(\w+\S+\[[\d|\.]+\])\s*%(\w+\s+\d+\s\d\d:\d\d:\d\d\s\d+)\s+(.+)/',$line,$matches);
    } elseif (strpos($file, '172.21.200') !== false) {
        preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s([\d|\.]+)\s*\d*\:*\s(\w+\s+\d+\s\d\d:\d\d:\d\d)[\s|\.\d]*\s*\S*\s+\%(.*)/',$line,$matches);
    }
    return $matches;
}

function spoiler($data,$name) {
    return "
        <div class='spoiler'>
            <input type='button' onclick='showSpoiler(this);' value='$name' />
            <div class='inner' style='display:none;'>
                $data
                <hr/>
            </div>
        </div>";
}

function get_info($key,$ip) {
    $result = shell_exec("/bin/bash switch_info.sh $key $ip");
    return $result;
}

function mstp_ip_count($log) {
    $count = substr_count($log,'MSTP');
    return $count;
}

function mstp($files) {
    $mstp_table = "
        <table border='0'>";
	foreach (explode("<br/>",$files) as $line) {
        $content = file_get_contents($line);
        if (strripos($content, 'MSTP') !== false) {
            $string  = preg_match('/.*PTSM.*/',strrev($content),$matches);
            $parced_data = parce_log_string($line,strrev($matches[0]));
            $switch_date = $parced_data[3];
            $mstp_table .= "
            <tr>
                <td>" . str_replace('.log','',$line) . "</td>
                <td>Last MSTP in log: <b>" . $switch_date . "</b></td>
            </tr>";
	    }
    }
    $mstp_table .="
        </table>";
    return $mstp_table;
   
}

if ($handle = opendir('.')) {
    $files   = "";
    while (false !== ($files_list = readdir($handle))) {
        if (strpos($files_list, '.log') !== false && strpos($files_list, '.gz') == false) {
            $files   .= $files_list . "<br/>";
        }
    }
    closedir($handle);
}
$list_ip = str_replace('.log','',$files);

$page = "
<!DOCTYPE html>
<html>
    <head>
        <style type='text/css'>
            body,input
            {
                font-family:'Trebuchet ms',arial;font-size:0.9em;
                color:#333;
            }
            .spoiler
            {
                border:0px;
                padding:3px;
            }
            .spoiler .inner
            {
                border:0px;
                padding:3px;margin:3px;
            }
        </style>
        <script type='text/javascript'>
        function showSpoiler(obj)
        {
            var inner = obj.parentNode.getElementsByTagName('div')[0];
            if (inner.style.display == 'none')
                inner.style.display = '';
            else
                inner.style.display = 'none';
        }
        </script>
        <title>SysLog</title>
    </head>
    <body>";

$ip_input = trim($_POST['switch_ip_input']);
$file = $ip_input . ".log";
$text = preg_replace("'  '", ' ', file_get_contents("$file"));
if (isset($_POST['mstp'])) {
	$mstp_count = mstp($files);
}
$page .= $mstp_count;


$table ="
        <table width='100%' border='1' cellpadding='5' cellspacing='2'>
            <tr align='center'>
                <td><b>Date</b></td>
                <td><b>Host</b></td>
                <td><b>Switch Date</b></td>
                <td><b>Switch Log</b></td>
            </tr>";

$log_table = 'Enter ip and press button [<b>Log</b>]';
if (isset($_POST['Log']) || isset($_POST['info+log'])) {
    $log_table = $table;
    foreach (explode("\n", $text) as $line) {
        $log_table .= parce_to_row ($line);
    }
    $log_table .= "
        </table>";
}

$mstp_on_ip = mstp_ip_count($log_table);
if ($mstp_on_ip !=0) {
    echo "Count of MSTP for <b>$ip_input: [ $mstp_on_ip ]</b>";
}

$page .= "
        <form name='form_1' action='' method='POST'>
            <input style='width:150px; height:20px;' name='switch_ip_input' type='text' placeholder='Enter ip' autofocus>
            <button type='submit' name='Log' value='Submit'>Log</button>
            <button type='submit' name='info' value='Submit'>info</button>
            <button type='submit' name='info+log' value='Submit'>info+Log</button>
            <button type='submit' name='switch_name' value='Submit'>Get Names</button>
            <button type='submit' name='mstp' value='Submit'>MSTP ?</button>
        </form><br/>";

if (isset($_POST['switch_name'])) {
    $snmp_names = "
        <table border='0'>";
    foreach (explode("<br/>",$list_ip) as $line) {
        if ($line != '') {
            $snmp_names .= get_info('name',$line);
        }
    }
    $snmp_names .= "
        </table>";
    $page .= spoiler($snmp_names,'ip+Names');
} else {
    $page .= spoiler($list_ip,'List ip');
}


$switch_info = 'Enter ip and press button [<b>info</b>]';
if (isset($_POST['info']) || isset($_POST['info+log'])) {
    if (strpos($ip_input,'172.') !== false) {
        $switch_info = get_info('info',$ip_input);
    }
}
$page.= spoiler($log_table,'Log');
$page.= spoiler($switch_info,'Switch info');
$page .= "
    </body>
</html>";

echo $page;

?>
