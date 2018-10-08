<?php

function parce_to_row($match) {
    $date         =  $match[1];
    $host         =  $match[2];
    $switch_date  =  $match[3];
    $switch_log   =  $match[4];
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

if ($handle = opendir('.')) {
    $list="";
    while (false !== ($files_list = readdir($handle))) {
        if (strpos($files_list, '.log') !== false && strpos($files_list, '.gz') == false) {
            $list .= str_replace('.log','',$files_list) . "<br/>";
        }
    }
    closedir($handle);
}

function mstp_count($log) {
    $count = substr_count($log,'MSTP');
    return $count;

}

$page = "<!DOCTYPE html>
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

$form_data =  trim($_POST['switch_ip']);
$file = $form_data . ".log";
$text = preg_replace("'  '", ' ', file_get_contents("$file"));

$table ="
        <table width='100%' border='1' cellpadding='5' cellspacing='2'>
            <tr align='center'>
                <td><b>Date</b></td>
                <td><b>Host</b></td>
                <td><b>Switch Date</b></td>
                <td><b>Switch Log</b></td>
            </tr>";

$log_table = 'Enter ip and press button [<b>Log</b>]';
if ((isset($_POST['Log']) || isset($_POST['info+log'])) && (strpos($file, '172.21.199') !== false)) {
    $log_table = $table;
    foreach (explode("\n", $text) as $line){
        preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s*[\d|\.]*\s*\d*\:*\s*(\w+\S+\[[\d|\.]+\])\s*%(\w+\s+\d+\s\d\d:\d\d:\d\d\s\d+)\s+(.+)/',$line,$matches);
        $log_table .= parce_to_row ($matches);
    }
    $log_table .= "
        </table>";
} elseif ((isset($_POST['Log']) || isset($_POST['info+log'])) && (strpos($file, '172.21.200') !== false)) {
    $log_table = $table;
    foreach (explode("\n", $text) as $line){
        preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s([\d|\.]+)\s*\d*\:*\s(\w+\s+\d+\s\d\d:\d\d:\d\d)[\s|\.\d]*\s*\S*\s+\%(.*)/',$line,$matches);
        $log_table .= parce_to_row ($matches);
    }
    $log_table .= "
        </table>";
}

$count = mstp_count($log_table);
if ($count !=0)
    echo "Количество запией MSTP для <b>$form_data: [ $count ]</b><br/><br/>";

$page .= "
        <form name='form' action='' method='POST'>
            <input style='width:150px; height:20px;' name='switch_ip' type='text' placeholder='Enter ip' autofocus>
            <button type='submit' name='Log' value='Submit'>Log</button>
            <button type='submit' name='info' value='Submit'>info</button>
            <button type='submit' name='info+log' value='Submit'>info+Log</button>
            <button type='submit' name='switch_name' value='Submit'>Get Names</button>
        </form>";

if (isset($_POST['switch_name'])) {
    $list_names = "
        <table border='0'>";
    foreach (explode("<br/>",$list) as $line) {
        if ($line != '') {
            $list_names .= get_info('name',$line);
        }
    }
    $list_names .= "
        </table>";
    $page .= spoiler($list_names,'ip+Names');
} else {
    $page .= spoiler($list,'List ip');
}


$switch_info = 'Enter ip and press button [<b>info</b>]';
if (isset($_POST['info']) || isset($_POST['info+log'])) {
    if (strpos($form_data,'172.') !== false) {
        $switch_info = get_info('info',$form_data);
    }
}
$page.= spoiler($log_table,'Log');
$page.= spoiler($switch_info,'Switch info');
$page .= "
    </body>
</html>";

echo $page;

?>
