<?php

// Check CONFIG.PHP for configuration details!
if ( !@include "./config.php" ) {
 echo "config.php not found! Please copy (and edit) ./contrib/config.php to ./ !";
 die();
}

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) or die("DBConnect:".mysql_error());
mysql_select_db($MYSQL_DB) or die("DBSelect:".mysql_error());

$result = mysql_query("SELECT * FROM actions ORDER BY id DESC LIMIT 200");

$i       = 15;
$output  = "";
$maxtime = 0;

$lastmode = $lastfreq = "";

while ( $row=mysql_fetch_assoc($result) ) {
 if ( $maxtime < $row['time'] ) $maxtime = $row['time'];
 if ( $lastmode == $row['mode'] && $lastfreq == $row['freq'] ) continue;
 $output .= strftime("%H:%M",$row['time'])."z ".$row['user'].": ".$row['freq']." ".@$modes[$row['mode']]."<br>";
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

