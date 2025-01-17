<?php
// addnews ready
// translator ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

translator::tlschema("list");

pageparts::page_header("List Warriors");
if ($session['user']['loggedin']) {
	checkday();
	if ($session['user']['alive']) {
		villagenav();
	} else {
		output::addnav("Return to the Graveyard", "graveyard.php");
	}
	output::addnav("Currently Online","list.php");
	if ($session['user']['clanid']>0){
		output::addnav("Online Clan Members","list.php?op=clan");
		if ($session['user']['alive']) {
			output::addnav("Clan Hall","clan.php");
		}
	}
}else{
	output::addnav("Login Screen","index.php");
	output::addnav("Currently Online","list.php");
}

$playersperpage=50;

$sql = "SELECT count(acctid) AS c FROM " . db_prefix("accounts") . " WHERE locked=0";
$result = db_query($sql);
$row = db_fetch_assoc($result);
$totalplayers = $row['c'];

$op = http::httpget('op');
$page = http::httpget('page');
$search = "";
$limit = "";

if ($op=="search"){
	$search="%";
	$n = httppost('name');
	for ($x=0;$x<strlen($n);$x++){
		$search .= substr($n,$x,1)."%";
	}
	$search=" AND name LIKE '".addslashes($search)."' ";
}else{
	$pageoffset = (int)$page;
	if ($pageoffset>0) $pageoffset--;
	$pageoffset*=$playersperpage;
	$from = $pageoffset+1;
	$to = min($pageoffset+$playersperpage,$totalplayers);

	$limit=" LIMIT $pageoffset,$playersperpage ";
}
output::addnav("Pages");
for ($i=0;$i<$totalplayers;$i+=$playersperpage){
	$pnum = $i/$playersperpage+1;
	if ($page == $pnum) {
		output::addnav(array(" ?`b`#Page %s`0 (%s-%s)`b", $pnum, $i+1, min($i+$playersperpage,$totalplayers)), "list.php?page=$pnum");
	} else {
		output::addnav(array(" ?Page %s (%s-%s)", $pnum, $i+1, min($i+$playersperpage,$totalplayers)), "list.php?page=$pnum");
	}
}

// Order the list by level, dragonkills, name so that the ordering is total!
// Without this, some users would show up on multiple pages and some users
// wouldn't show up
if ($page=="" && $op==""){
	$title = translator::translate_inline("Warriors Currently Online");
	$sql = "SELECT acctid,name,login,alive,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".settings::getsetting("LOGINTIMEOUT",900)." seconds"))."' ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query_cached($sql,"list.php-warsonline");
}elseif($op=='clan'){
	$title = translator::translate_inline("Clan Members Online");
	$sql = "SELECT acctid,name,login,alive,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 AND loggedin=1 AND laston>'".date("Y-m-d H:i:s",strtotime("-".settings::getsetting("LOGINTIMEOUT",900)." seconds"))."' AND clanid='{$session['user']['clanid']}' ORDER BY level DESC, dragonkills DESC, login ASC";
	$result = db_query($sql);
}else{
	if ($totalplayers > $playersperpage && $op != "search") {
		$title = translator::sprintf_translate("Warriors of the realm (Page %s: %s-%s of %s)", ($pageoffset/$playersperpage+1), $from, $to, $totalplayers);
	} else {
		$title = translator::sprintf_translate("Warriors of the realm");
	}
	rawoutput(translator::tlbutton_clear());
	$sql = "SELECT acctid,name,login,alive,hitpoints,location,race,sex,level,laston,loggedin,lastip,uniqueid FROM " . db_prefix("accounts") . " WHERE locked=0 $search ORDER BY level DESC, dragonkills DESC, login ASC $limit";
	$result = db_query($sql);
}
if ($session['user']['loggedin']){
	$search = translator::translate_inline("Search by name: ");
	$search2 = translator::translate_inline("Search");

	rawoutput("<form action='list.php?op=search' method='POST'>$search<input name='name'><input type='submit' class='button' value='$search2'></form>");
	output::addnav("","list.php?op=search");
}

$max = db_num_rows($result);
if ($max>settings::getsetting("maxlistsize", 100)) {
	output::doOutput("`\$Too many names match that search.  Showing only the first %s.`0`n", settings::getsetting("maxlistsize", 100));
	$max = settings::getsetting("maxlistsize", 100);
}

if ($page=="" && $op==""){
	$title .= translator::sprintf_translate(" (%s warriors)", $max);
}
output_notl("`c`b".$title."`b");

$alive = translator::translate_inline("Alive");
$level = translator::translate_inline("Level");
$name = translator::translate_inline("Name");
$loc = translator::translate_inline("Location");
$race = translator::translate_inline("Race");
$sex = translator::translate_inline("Sex");
$last = translator::translate_inline("Last On");

rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>",true);
rawoutput("<tr class='trhead'><td>$alive</td><td>$level</td><td>$name</td><td>$loc</td><td>$race</td><td>$sex</td><td>$last</tr>");
$writemail = translator::translate_inline("Write Mail");
$alive = translator::translate_inline("`1Yes`0");
$dead = translator::translate_inline("`4No`0");
$unconscious = translator::translate_inline("`6Unconscious`0");
for($i=0;$i<$max;$i++){
	$row = db_fetch_assoc($result);
	rawoutput("<tr class='".($i%2?"trdark":"trlight")."'><td>",true);
	if ($row['alive'] == true) {
		$a = $alive;
	} else if ($row['hitpoints'] > 0) {
		$a = $unconscious;
	} else {
		$a = $dead;
	}
	//$a = translator::translate_inline($row['alive']?"`1Yes`0":"`4No`0");
	output_notl("%s", $a);
	rawoutput("</td><td>");
	output_notl("`^%s`0", $row['level']);
	rawoutput("</td><td>");
	if ($session['user']['loggedin']) {
		rawoutput("<a href=\"mail.php?op=write&to=".rawurlencode($row['login'])."\" target=\"_blank\" onClick=\"".popup("mail.php?op=write&to=".rawurlencode($row['login'])."").";return false;\">");
		rawoutput("<img src='images/newscroll.GIF' width='16' height='16' alt='$writemail' border='0'></a>");
		rawoutput("<a href='bio.php?char=".$row['acctid']."'>");
		output::addnav("","bio.php?char=".$row['acctid']."");
	}
	output_notl("`&%s`0", $row['name']);
	if ($session['user']['loggedin'])
		rawoutput("</a>");
	rawoutput("</td><td>");
	$loggedin=(date("U") - strtotime($row['laston']) < settings::getsetting("LOGINTIMEOUT",900) && $row['loggedin']);
	output_notl("`&%s`0", $row['location']);
	if ($loggedin) {
		$online = translator::translate_inline("`#(Online)");
		output_notl("%s", $online);
	}
	rawoutput("</td><td>");
	if (!$row['race']) $row['race'] = RACE_UNKNOWN;
	translator::tlschema("race");
	output::doOutput($row['race']);
	translator::tlschema();
	rawoutput("</td><td>");
	$sex = translator::translate_inline($row['sex']?"`%Female`0":"`!Male`0");
	output_notl("%s", $sex);
	rawoutput("</td><td>");
	$laston = relativedate($row['laston']);
	output_notl("%s", $laston);
	rawoutput("</td></tr>");
}
rawoutput("</table>");
output_notl("`c");
page_footer();
