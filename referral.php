<?php
// translator ready
// addnews ready
// mail ready
define("ALLOW_ANONYMOUS",true);
require_once("common.php");
require_once("lib/http.php");

translator::tlschema("referral");

if ($session['user']['loggedin']){
	pageparts::page_header("Referral Page");
	if (file_exists("lodge.php")) {
		output::addnav("L?Return to the Lodge","lodge.php");
	} else {
		require_once("lib/villagenav.php");
		villagenav();
	}
	output::doOutput("You will automatically receive %s points for each person that you refer to this website who makes it to level %s.`n`n", settings::getsetting("refereraward", 25), settings::getsetting("referminlevel", 4));

	$url = settings::getsetting("serverurl",
			"http://".$_SERVER['SERVER_NAME'] .
			($_SERVER['SERVER_PORT']==80?"":":".$_SERVER['SERVER_PORT']) .
			dirname($_SERVER['REQUEST_URI']));
	if (!preg_match("/\\/$/", $url)) {
		$url = $url . "/";
		savesetting("serverurl", $url);
	}

	output::doOutput("How does the site know that I referred a person?`n");
	output::doOutput("Easy!  When you tell your friends about this site, give out the following link:`n`n");
	output_notl("%sreferral.php?r=%s`n`n",$url,rawurlencode($session['user']['login']));
	output::doOutput("If you do, the site will know that you were the one who sent them here.");
	output::doOutput("When they reach level %s for the first time, you'll get your points!", settings::getsetting("referminlevel", 4));

	$sql = "SELECT name,level,refererawarded FROM " . db_prefix("accounts") . " WHERE referer={$session['user']['acctid']} ORDER BY dragonkills,level";
	$result = db_query($sql);
	$name=translator::translate_inline("Name");
	$level=translator::translate_inline("Level");
	$awarded=translator::translate_inline("Awarded?");
	$yes=translator::translate_inline("`@Yes!`0");
	$no=translator::translate_inline("`\$No!`0");
	$none=translator::translate_inline("`iNone`i");
	output::doOutput("`n`nAccounts which you referred:`n");
	rawoutput("<table border='0' cellpadding='3' cellspacing='0'><tr><td>$name</td><td>$level</td><td>$awarded</td></tr>");
	$number=db_num_rows($result);
	for ($i=0;$i<$number;$i++){
		$row = db_fetch_assoc($result);
		rawoutput("<tr class='".($i%2?"trlight":"trdark")."'><td>");
		output_notl($row['name']);
		rawoutput("</td><td>");
		output_notl($row['level']);
		rawoutput("</td><td>");
		output_notl($row['refererawarded']?$yes:$no);
		rawoutput("</td></tr>");
	}
	if (db_num_rows($result)==0){
		rawoutput("<tr><td colspan='3' align='center'>");
		output_notl($none);
		rawoutput("</td></tr>");
	}
	rawoutput("</table>",true);
	page_footer();
}else{
	pageparts::page_header("Welcome to Legend of the Green Dragon");
	output::doOutput("`@Legend of the Green Dragon is a remake of the classic BBS Door Game Legend of the Red Dragon.");
	output::doOutput("Adventure into the classic realm that was one of the world's very first multiplayer roleplaying games!");
	output::addnav("Create a character","create.php?r=".HTMLEntities(http::httpget('r'), ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1")));
	output::addnav("Login Page","index.php");
	page_footer();
}
