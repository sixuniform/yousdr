<?php

// PPM Correction. Hardcoded for now.
// $ppm     = intval(29);
$ppm     = intval(26);


// Current User set via cookies.

if ( isset($_POST['sdruser']) && strlen(trim($_POST['sdruser'])) ) {
 setcookie("SDR_USER", trim($_POST['sdruser']), time()+60*60*24*30);
 $_COOKIE['SDR_USER'] = trim($_POST['sdruser']);
} else
 $sdruser = "";


// Check CONFIG.PHP for configuration details!
if ( !@include "./config.php" ) {
 echo "config.php not found! Please copy (and edit) ./contrib/config.php to ./ !";
 die();
}

echo '<html>
<head>
 <script type="text/javascript">
 function callrequired() {
  var empt = document.forms["sdrcontrol"]["sdruser"].value;
  if (empt == "") {
   alert("Please enter your callsign/name");
   document.forms["sdrcontrol"]["sdruser"].focus();
   return false;
  }
 return true;
 }
 </script>
</head><body>

<table style="border: 1px solid black;"><tr><td vAlign="top" width="400">
<table>
';

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS) or die("DBConnect:".mysql_error());
mysql_select_db($MYSQL_DB) or die("DBSelect:".mysql_error());


// Get current rxmode from database
$result_rxm = mysql_query("SELECT rxmode FROM actions ORDER BY id DESC LIMIT 1") or die(mysql_error());
if ( mysql_num_rows($result_rxm) ) {
 $cur_rxm    = mysql_fetch_row($result_rxm);
 $rxmode     = $cur_rxm[0];
} else
 $rxmode     = "listen";


// See if user changed mode and switch if it is correct..
if ( isset($_GET['rxmode']) && isset($rxmodes[$_POST['rxmode']]) )
 $rxmode = $_POST['rxmode'];


switch($rxmode) {

case "listen":

 // Get latest actions and frequency data
 $result  = mysql_query("SELECT * FROM actions WHERE bw != 0 ORDER BY id DESC LIMIT 1");

 $cur     = mysql_fetch_assoc($result);

 $freq    = $cur['freq'];
 $bw      = $cur['bw'];
 //$ppm    = $cur['ppm'];
 $sql     = $cur['squl'];
 $mode    = $cur['mode'];
 $autobw  = $cur['autobw'];
 $filter  = $cur['filter'];

 // Check for requested changes. 
 // If Receiver Mode is changed, rely on database settings.

 if ( $_POST && @!$_POST['rxmode'] )  {
  if ( isset($_POST['freq']) )   $freq   = str_replace(",",".",$_POST['freq']);   // Needs to be EXEC/SYSTEM safe. Fix !!!
  if ( isset($_POST['sql']) )    $sql    = intval($_POST['sql']);
  if ( isset($_POST['bw']) )     $bw     = intval($_POST['bw']);
  if ( isset($_POST['autobw']) ) $autobw = 1; else $autobw = 0;

  if ( isset($_POST['filter']) && isset($filters[$_POST['filter']]) ) $filter = $_POST['filter'];

  if ( isset($_POST['mode'])   && isset($modes[$_POST['mode']]) ) {
   if ( $autobw && $_POST['mode'] != $mode ) 
    $bw = $bw_hz[$_POST['mode']]; // Set auto-banwidth on mode change, if requested.
   $mode = $_POST['mode'];
  }
 }


 include "xml2array.php";
 $il    = xml2array("http://".$ICECAST_USER.":".$ICECAST_PASS."@".$ICECAST_HOST."/admin/listclients?mount=/".$ICECAST_MOUNT);

 echo '<form name="sdrcontrol" action="./" method="POST" onSubmit="JavaScript:return callrequired();">
 <tr><td>Frequency</td><td><input name="freq" value="'.$freq.'" size="15"> MHz</td></tr>
 <tr><td>Mode</td><td>
 <select name="mode">';

 foreach($modes as $r_mode => $p_mode) 
  echo '<option '.($r_mode == $mode ? 'selected' : '').' value="'.$r_mode.'">'.$p_mode.'</option>';

 echo '
 </select>
 <input type="checkbox" name="autobw" '.($autobw ? 'checked' : '').'> Auto BW
 </td></tr>
 <tr><td>Bandwidth</td><td><input name="bw" value="'.$bw.'" size="10"> Hz</td></tr>

 <tr><td>Squelch</td><td><input type="range" min=0 max=175 name="sql" value="'.$sql.'"></td></tr>

 <!-- <tr><td>Squelch</td><td><input name="sql" value="'.$sql.'"></td></tr> -->
 <tr><td>Option</td><td>
 <select name="filter">';

 foreach($filters as $r_filter => $p_filter) 
  echo '<option '.($r_filter == $filter ? 'selected' : '').' value="'.$r_filter.'">'.$p_filter.'</option>';

 echo '
 </select>
 </td></tr>
 <tr><td>User Callsign</td><td><input type="text" name="sdruser" value="'.@$_COOKIE['SDR_USER'].'"></td></tr>
 <tr><td><input type="submit" value="Set data"></td><td><a href="./?kill=yes">Kill Stream (quit)</a></td></tr>
 </form>

 <tr><td colspan=2><br>Stream URL: <a target="_blank" href="'.$ICECAST_URL.'/'.$ICECAST_MOUNT.'">'.$ICECAST_URL.'/'.$ICECAST_MOUNT.'</a> ['.$il['icestats']['source']['Listeners'].']</td></td>
';

break;

case "ads-b":
 echo '<tr><td colspan=2><br>ADS-B Map URL: <a target="_blank" href="'.$URL_DUMP1090.'">'.$URL_DUMP1090.'</a></td></td>';
break;

case "rtl_tcp":
 echo "Connect local SDR# Sharp to SDR-IP";
break;

} // End switch rxmode

echo '</table>

<br><form action="./?rxmode=set" name="rxmode" method=POST onSubmit="JavaScript:return callrequired();">
Receiver Mode <select name="rxmode">';

foreach($rxmodes as $rxm_s => $rxm_l) 
 echo '<option '.($rxm_s == $rxmode ? 'selected' : '').' value="'.$rxm_s.'">'.$rxm_l.'</option>';

echo '
</select>
<input type="submit" value="Set">
</form>

</td><td style="border-left: 1px dotted black;"><iframe border=0 frameborder=0 height="275" width="500" src="./sdrlog.php?lastaction='.time().'"></iframe></td></tr></table>';


// Fix bandwidths. APLAY doesn't seem to handle less than 4000 Hz :-(

if ( @$bw > 96000 ) $audiobw = "96000"; else
if ( @$bw < 4000  ) $bw      = "4000"; else
 $audiobw = $bw;

// No changes to be made? Quit here.
if ( !$_POST ) die();	

// Kill all existing programs that may hog the RTL-Chip.
system("killall -9 ".$BIN_RTLFM." ".$BIN_DUMP1090." ".$BIN_RTLTCP);


switch($rxmode) {

case "off":
 $crap  = file_get_contents("http://".$ICECAST_USER.":".$ICECAST_PASS."@".$ICECAST_HOST."/admin/metadata?mount=/".$ICECAST_MOUNT."&mode=updinfo&song=OFF");
 mysql_query("INSERT INTO actions SET user = '".@mysql_real_escape_string($_COOKIE['SDR_USER'])."', time = '".time()."', freq = 'Stream stopped.', rxmode = '".$rxmode."'") or die(mysql_error());
break;


case "listen":
 mysql_query("INSERT INTO actions SET user = '".@mysql_real_escape_string($_COOKIE['SDR_USER'])."', time = '".time()."', freq = '".mysql_real_escape_string($freq)."', squl = '".mysql_real_escape_string($sql)."', autobw = '".intval($autobw)."', mode = '".mysql_real_escape_string($mode)."', filter = '".mysql_real_escape_string($filter)."', bw = '".intval($bw)."', rxmode = '".$rxmode."'") or die(mysql_error());
  if ( $sql ) $sql = "-l ".$sql; else $sql = "";
  if ( strlen($filter) ) $filter = escapeshellarg("-E ".$filter); else $filter = "";
 system("(".$BIN_RTLFM." '-F 9' ".escapeshellarg("-f ".$freq."M")." -M ".$mode." -s ".$bw." ".($audiobw != $bw ? "-r ".$audiobw : "")."  -p ".$ppm." ".$sql." ".$filter." -  | ".$BIN_APLAY." -r ".$audiobw." -f S16_LE -c 1 -t raw --buffer-size=0 -) > /dev/null 2>&1 &");
 $title = $freq." ".strtoupper($mode);
 $crap  = file_get_contents("http://".$ICECAST_USER.":".$ICECAST_PASS."@".$ICECAST_HOST."/admin/metadata?mount=/".$ICECAST_MOUNT."&mode=updinfo&song=".urlencode($title));
break;

case "ads-b":
 mysql_query("INSERT INTO actions SET user = '".@mysql_real_escape_string($_COOKIE['SDR_USER'])."', time = '".time()."', freq = 'ADS-B Flight RX', rxmode = '".$rxmode."'") or die(mysql_error());
 system("(cd ".$HTML_DUMP1090." ; ".$BIN_DUMP1090." --ppm ".$ppm." --net --fix --phase-enhance --aggressive --metric --modeac --gain -10 --quiet) > /dev/null 2>&1 &");
 $title = "ADS-B Flight RX 1090 MHz (No Audio)";
 $crap  = file_get_contents("http://".$ICECAST_USER.":".$ICECAST_PASS."@".$ICECAST_HOST."/admin/metadata?mount=/".$ICECAST_MOUNT."&mode=updinfo&song=".urlencode($title));
break;

case "rtl_tcp":
 mysql_query("INSERT INTO actions SET user = '".@mysql_real_escape_string($_COOKIE['SDR_USER'])."', time = '".time()."', freq = 'RTL-TCP SDR', rxmode = '".$rxmode."'") or die(mysql_error());
 system("(".$BIN_RTLTCP." -a 0.0.0.0 -P ".$ppm." )> /dev/null 2>&1 &");
 $title = "RTL-TCP Wideband (No Audio here)";
 $crap  = file_get_contents("http://".$ICECAST_USER.":".$ICECAST_PASS."@".$ICECAST_HOST."/admin/metadata?mount=/".$ICECAST_MOUNT."&mode=updinfo&song=".urlencode($title));
break;

}



?>
