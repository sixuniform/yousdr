<?php

$modes = array("fm" => "FM (narrow)", "wbfm" => "FM (wide)", "am" => "AM", "usb" => "USB", "lsb" => "LSB", "cw" => "CW");
$filters = array("" => "None", "edge" => "Edge Tuning", "dc" => "DC Blocking Filter", "deemp" => "De-Emphasis Filter", "direct" => "Direct Sampling", "offset" => "Offset tuning");
date_default_timezone_set("UTC");

mysql_connect("localhost", "sdr", "sDRjo57WQ") or die(mysql_query());
mysql_select_db("sdr");

$result = mysql_query("SELECT * FROM actions ORDER BY id DESC LIMIT 200");

$i       = 15;
$output  = "";
$maxtime = 0;

$lastmode = $lastfreq = "";

while ( $row=mysql_fetch_assoc($result) ) {
 if ( $maxtime < $row['time'] ) $maxtime = $row['time'];
 if ( $lastmode == $row['mode'] && $lastfreq == $row['freq'] ) continue;
 $output .= strftime("%H:%M",$row['time'])."z ".$row['user'].": ".$row['freq']." ".$modes[$row['mode']]."<br>";
 $lastfreq = $row['freq'];
 $lastmode = $row['mode'];
 $i--;
 if ( !$i ) break;
}

echo '<html>
<head>
<meta http-equiv="refresh" content="60;url=\'./sdrlog.php?lastaction='.$maxtime.'\'">';

if ( $maxtime > $_GET['lastaction'] ) 
echo '<script type="text/javascript">
window.parent.location.href = "./";
// window.parent.location.href;
</script>
';

echo '</head>
<body><pre>'.$output.'</pre></body></html>';

?>

