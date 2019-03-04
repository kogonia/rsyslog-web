<?php

//ini_set('display_errors', 1);

/*
 * Function section
 */
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
    if (strpos($file, '172.21.199') !== FALSE) {
        preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s*[\d|\.]*\s*\d*\:*\s*(\w+\S+\[[\d|\.]+\])\s*%(\w+\s+\d+\s\d\d:\d\d:\d\d)\s\d+\s+(.+)/',$line,$matches);
    } elseif (strpos($file, '172.21.200') !== FALSE) {
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

function get_snmp_info($key,$ip) {
    $result = shell_exec("/bin/bash switch_info.sh $key $ip");
    return $result;
}

function mstp_ip_count($log) {
    $count = substr_count($log,'MSTP');
    return $count;
}

function mstp($files,$arr) {
    $arr_for_sort = array();
    $mstp_table = "
        <table border='0'>";
	foreach (explode("<br/>",$files) as $line) {
        $content = file_get_contents($line);
        if (strripos($content, 'MSTP') !== FALSE) {
            $string  = preg_match('/.*PTSM.*/',strrev($content),$matches);
            $parced_data = parce_log_string($line,strrev($matches[0]));
            $switch_date = $parced_data[3];
            $ip = str_replace('.log','',$line);
            $data = "
            <tr>
                <td>mstp: ["  . $ip          .    "]</td>
                <td><b>"      . $arr[$ip]    . "</b></td>
                <td>at <b>"   . $switch_date . "</b></td>
            </tr>";
            $arr_for_sort[strtotime($switch_date)] = $data;
	    }
    }
    krsort($arr_for_sort);
    foreach($arr_for_sort as $key => $value) {
        $mstp_table .= $value;
    }
    $mstp_table .="
        </table>";
    return $mstp_table;
}

function get_name_from_file($file_name,$list_ip) {
    $data = file_get_contents($file_name);
    $List_ip = array();
    foreach (explode("<br/>",$list_ip) as $ip) {
        if ($ip !== "") {
            if (stristr($data, $ip) !== FALSE) {
                foreach(explode("\n",$data) as $line) {
                    $f_ip = explode("<tab>", $line);
                    if ($ip === $f_ip[0]) {
                        $List_ip[$ip] = trim($f_ip[1]);
                        break;
                    }
                }
            } else  {
                $List_ip[$ip] = '';
            }
        }
    }
    return $List_ip;
}

function info($ip, $com="cisco", $oid) {
    return snmp2_walk($ip, $com, $oid, 10000000, 0);
}

function DelWord($val) {
    return substr(strstr($val," "), 1);
}

function snmp_int($ip, $com="cisco") {

    $host           = array_map ('DelWord', info ($ip, $com, "sysName"));
    $sysDescr       = array_map ('DelWord', info ($ip, $com, "sysDescr"));
    $ifIndex        = array_map ('DelWord', info ($ip, $com, "ifIndex"));
    $ifDescr        = array_map ('DelWord', info ($ip, $com, "ifDescr"));
    $ifAlias        = array_map ('DelWord', info ($ip, $com, "ifAlias"));
    $ifOperStatus   = array_map ('DelWord', info ($ip, $com, "ifOperStatus"));
    $ifAdminStatus  = array_map ('DelWord', info ($ip, $com, "ifAdminStatus"));
    $ifLastChange   = array_map ('DelWord', info ($ip, $com, "ifLastChange"));
    $ifLastChange   = array_map ('DelWord', $ifLastChange);

    $res = "<h3>$host[0]:</h3>
        <table border='1' cellpadding='5' cellspacing='2'>
            <tr align='center'>
                <td>snmp №</td>
                <td>Port</td>
                <td>Description</td>
                <td>Status</td>
                <td>AdminStatus</td>
                <td>LastChange</td>
                <td style='text-align:right;vertical-align:top' rowspan=".count($ifIndex).">".nl2br(implode("<br />",$sysDescr))."</td>
            </tr>";

    for ($i=0; $i<count($ifIndex); $i++) {
        if ((strpos(strtolower($ifDescr[$i]), 'vlan') === false) && (strpos(strtolower($ifDescr[$i]), 'loopback') === false)) {
            $res .= "
            <tr align='center'>
                <td>" . $ifIndex[$i]        . "</td>
                <td>" . $ifDescr[$i]        . "</td>
                <td>" . $ifAlias[$i]        . "</td>
                <td>" . $ifOperStatus[$i]   . "</td>
                <td>" . $ifAdminStatus[$i]  . "</td>
                <td>" . $ifLastChange[$i]   . "</td>
            </tr>";
        }
    }

    $res .= "
        </table>";
    return $res;
}

/*
 * Code section
 */

if ($handle = opendir('.')) {
    $files   = "";
    while (FALSE !== ($files_list = readdir($handle))) {
        if (strpos($files_list, '.log') !== FALSE && strpos($files_list, '.gz') == FALSE) {
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

$arr_ip_name = array();
$arr_ip_name = get_name_from_file("ip_and_names.txt",$list_ip);
$list_ip_name = "
                <table border='0'>";
foreach($arr_ip_name as $key => $value) {
    $list_ip_name .= "
                    <tr>
                        <td>" . $key   . "</td>
                        <td>" . $value . "</td>
                    </tr>";
}
$list_ip_name .= "
                </table>";

$ip_input = trim($_POST['switch_ip_input']);
$file = $ip_input . ".log";
$text = preg_replace("'  '", ' ', file_get_contents("$file"));

if (isset($_POST['mstp'])) {
    $mstp_info = mstp($files, $arr_ip_name);
    print "$mstp_info
        <hr/>";

//    $page      .= spoiler($mstp_count, 'MSTP Status');
}

$page .= "
        <form name='form_1' action='' method='POST'>
            <input style='width:150px; height:20px;' name='switch_ip_input' type='text' placeholder='Enter ip' autofocus>
            <button type='submit' name='Log' value='Submit'>Log</button>
            <button type='submit' name='info' value='Submit'>info</button>
            <button type='submit' name='info+log' value='Submit'>info+Log</button>
            <button type='submit' name='switch_name' value='Submit'>Update Names</button>
            <button type='submit' name='bgp' value='Submit'>BGP ?</button>
            <button type='submit' name='mstp' value='Submit'>MSTP ?</button>
        </form>";

$page .= spoiler($list_ip_name,'List ip');

if (isset($_POST['Log']) || isset($_POST['info+log'])) {
    $log_table = "
        <table width='100%' border='1' cellpadding='5' cellspacing='2'>
            <tr align='center'>
                <td><b>Date</b></td>
                <td><b>Host</b></td>
                <td><b>Switch Date</b></td>
                <td><b>Switch Log</b></td>
            </tr>";
    if ($mstp_on_ip !=0) {
        echo "Count of MSTP for <b>$ip_input: [ $mstp_on_ip ]</b>";
    }
    $foo='';
    $bar='';
    foreach (explode("\n",$text) as $line) {
        $foo = parce_to_row($line);
        $foo.= $bar;
        $bar = $foo;
    }
    $log_table .= $bar;
    $log_table .= "
        </table>";
    $mstp_on_ip = mstp_ip_count($log_table);
    $page.= spoiler($log_table,'Log');
}

if (isset($_POST['info']) || isset($_POST['info+log'])) {
    if (strpos($ip_input,'172.') !== FALSE) {
        $switch_info = snmp_int($ip_input);
    }
    $page.= spoiler($switch_info,'Switch info');
}

if (isset($_POST['switch_name'])) {
    $ip_and_name = "";
    foreach (explode("<br/>",$list_ip) as $line) {
        if ($line !== "") {
            $name           = array_map('DelWord', info ($line, "cisco", "sysName"));
            $ip_and_name   .= $line . "<tab>" . $name[0] . "\r\n";
        }
    }
    file_put_contents("ip_and_names.txt",$ip_and_name);
}
/*
 * if (isset($_POST['mstp'])) {
 *     $mstp_info = mstp($files, $arr_ip_name);
 *     $page     .= spoiler($mstp_info, 'MSTP Status');
 * }
 */
if (isset($_POST['bgp'])) {
    $BGP  = get_snmp_info('bgp_sw-x',"172.21.200.9");
    $BGP .= get_snmp_info('bgp_ipx',"172.21.254.1");
    $page.= spoiler($BGP, 'BGP Status');
}

$page .= "
    </body>
</html>";

echo $page;

?>
