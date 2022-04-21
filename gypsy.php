<?php
// addnews ready
// translator ready
// mail ready
require_once("common.php");
require_once("lib/commentary.php");
require_once("lib/http.php");
require_once("lib/villagenav.php");

translator::tlschema("gypsy");

addcommentary();

$cost = $session['user']['level']*20;
$op = http::httpget('op');

if ($op=="pay"){
	if ($session['user']['gold']>=$cost){ // Gunnar Kreitz
		$session['user']['gold']-=$cost;
		debuglog("spent $cost gold to speak to the dead");
		redirect("gypsy.php?op=talk");
	}else{
		pageparts::page_header("Gypsy Seer's tent");
		villagenav();
		output::doOutput("`5You offer the old gypsy woman your `^%s`5 gold for your gen-u-wine say-ance, however she informs you that the dead may be dead, but they ain't cheap.", $session['user']['gold']);
	}
}elseif ($op=="talk"){
	pageparts::page_header("In a deep trance, you talk with the shades");
	commentdisplay("`5While in a deep trance, you are able to talk with the dead:`n", "shade","Project",25,"projects");
	output::addnav("Snap out of your trance","gypsy.php");
}else{
	checkday();
	pageparts::page_header("Gypsy Seer's tent");
	output::doOutput("`5You duck into a gypsy tent like many you have seen throughout the realm.");
	output::doOutput("All of them promise to let you talk with the deceased, and most of them surprisingly seem to work.");
	output::doOutput("There are also rumors that the gypsy have the power to speak over distances other than just those of the afterlife.");
	output::doOutput("In typical gypsy style, the old woman sitting behind a somewhat smudgy crystal ball informs you that the dead only speak with the paying.");
	output::doOutput("\"`!For you, %s, the price is a trifling `^%s`! gold.`5\", she rasps.", translator::translate_inline($session['user']['sex']?"my pretty":"my handsome"), $cost);
	output::addnav("Seance");
	output::addnav(array("Pay to talk to the dead (%s gold)", $cost),"gypsy.php?op=pay");
	if ($session['user']['superuser'] & SU_EDIT_COMMENTS)
		output::addnav("Superuser Entry","gypsy.php?op=talk");
	output::addnav("Other");
	output::addnav("Forget it","village.php");
	modules::modulehook("gypsy");
}
page_footer();
