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

if ($handle = opendir('.')) {
    $list="";
    while (false !== ($files_list = readdir($handle))) {
        if (strpos($files_list, '.log') !== false && strpos($files_list, '.gz') == false) {
            $list .= str_replace('.log','',$files_list) . "<br/>";
        }
    }
    closedir($handle);
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
  <body>
    <form name='form' action='' method='get'>
      <input style='width:150px; height:20px;' name='switch_ip' type='text' id='ip_to_find' placeholder='Введите ip' autofocus>
      <input type='submit'>
    </form>
    <table width='100%' border='1' cellpadding='5' cellspacing='2'>
      <tr align='center'>
        <td><b>Date</b></td>
        <td><b>Host</b></td>
        <td><b>Switch Date</b></td>
        <td><b>Switch Log</b></td>
      </tr>";

$form_data =  trim($_GET['switch_ip']);
$file = $form_data . ".log";
$text = preg_replace("'  '", ' ', file_get_contents ("$file"));

if (strpos($file, '172.21.199') !== false) {
  foreach (explode("\n", $text) as $line){
    preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s*[\d|\.]*\s*\d*\:*\s*(\w+\S+\[[\d|\.]+\])\s*%(\w+\s+\d+\s\d\d:\d\d:\d\d\s\d+)\s+(.+)/',$line,$matches);
    $page .= parce_to_row ($matches);
  }
} elseif (strpos($file, '172.21.200') !== false) {
  foreach (explode("\n", $text) as $line){
    preg_match('/(\w+\s+\d+\s\d\d:\d\d:\d\d)\s([\d|\.]+)\s*\d*\:*\s(\w+\s+\d+\s\d\d:\d\d:\d\d)[\s|\.\d]*\s*\S*\s+\%(.*)/',$line,$matches);
    $page .= parce_to_row ($matches);
  }
}

$page .= "
    </table>
    <div class='spoiler'>
      <input type='button' onclick='showSpoiler(this);' value='Доступные ip' />
      <div class='inner' style='display:none;'>
        $list
      </div>
    </div>
  </body>
</html>";

echo $page;

?>