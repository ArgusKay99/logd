<?php
//addnews ready
// mail ready
// translator ready
/**
* Version:	0.6
* Date:		July 31, 2003
* Author:	John J. Collins
* Email:	collinsj@yahoo.com
*
* Purpose:	Provide a fun module to Legend of the Green Dragon
* Program Flow:	The player can choose to use the Private or Public Toilet.
* It costs Gold to use the Private Toilet. The Public Toilet is free. After
* using one of the toilet's, the play can was their hands or return. If
* they choose to wash their hands, there is a chance that they can get
* their gold back. If they don't choose to wash their hands, there is a
* chance that they will lose some gold. If they loose gold there is an
* entry added to the daily news.
*/
/**
* Modifications:
* Date: Mar 3, 2004
* Author: Eric Stevens aka MightyE
* Email: logd -at- mightye -dot- org
*
* Mods: Reflowed to sit in moduling system.
*/

require_once("lib/http.php");

function outhouse_getmoduleinfo(){
	$info = array(
		"name"=>"Gnomish Outhouse",
		"author"=>"John Collins",
		"version"=>"2.0",
		"category"=>"Forest",
		"download"=>"core_module",
		"prefs"=>array(
			"Gnomish Outhouse User Preferences,title",
			"usedouthouse"=>"Used Outhouse Today,bool|0"
		),
		"settings"=>array(
			"Gnomish Outhouse Settings,title",
			"cost"=>"Cost to use the private outhouse,range,1,20,1|5",
			"goldinhand"=>"How much gold must user have in hand before they can lose money,range,0,10,1|1",
			"giveback"=>"How much gold to give back if the player is rewarded for washing their hands,range,0,20,1|3",
			"takeback"=>"How much gold to take if the user is punished for not washing their hands,range,0,20,1|1",
			"goodmusthit"=>"Percent of time you get your money back if you wash,range,0,100,1|60",
			"badmusthit"=>"Percent of time you lose money if you don't wash,range,0,100,1|50",
			"givegempercent"=>"Percent chance of getting a gem if you wash,range,0,100,1|25",
			"giveturnchance"=>"Percent chance of a free forest fight if you wash,range,0,100,1|0"
		)
	);
	return $info;
}

function outhouse_install(){
	global $session;
	debug("Adding Hooks");
	module_addhook("forest");
	module_addhook("newday");

	$sql = "DESCRIBE " . db_prefix("accounts");
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)){
		if ($row['Field']=="usedouthouse"){
			$sql = "SELECT usedouthouse,acctid FROM " . db_prefix("accounts") . " WHERE usedouthouse>0";
			$result1 = db_query($sql);
			debug("Migrating outhouse usage.`n");
			while ($row1 = db_fetch_assoc($result1)){
				$sql = "INSERT INTO " . db_prefix("module_userprefs") . " (modulename,setting,userid,value) VALUES ('outhouse','usedouthouse',{$row1['acctid']},{$row1['usedouthouse']})";
				db_query($sql);
			}//end while
			debug("Dropping usedouthouse column from the user table.`n");
			$sql = "ALTER TABLE " . db_prefix("accounts") . " DROP usedouthouse";
			db_query($sql);
			//drop it from the user's session too.
			unset($session['user']['usedouthouse']);
		}//end if
	}//end while
	return true;
}

function outhouse_uninstall(){
	output::doOutput("Uninstalling this module.`n");
	return true;
}

function outhouse_dohook($hookname, $args){
	if ($hookname=="forest"){
		output::addnav("O?The Outhouse","runmodule.php?module=outhouse");
	}elseif ($hookname=="newday"){
		set_module_pref("usedouthouse",0);
	}
	return $args;
}

function outhouse_run(){
	global $session;
	$cost = get_module_setting("cost");
	$goldinhand = get_module_setting("goldinhand");
	$giveback = get_module_setting("giveback");
	$takeback = get_module_setting("takeback");
	$goodmusthit = get_module_setting("goodmusthit");
	$badmusthit = get_module_setting("badmusthit");
	$givegempercent = get_module_setting("givegempercent");
	$giveturnchance= get_module_setting("giveturnchance");
	$returnto = get_module_setting("returnto");
	// Does the player have enough gold to use the Private Toilet?
	if ($session['user']['gold'] >= $cost)
		$canpay = true;

	$op = http::httpget('op');
	if ($op == "pay"){
		if (!$canpay) {
			pageparts::page_header("Private Toilet");
			output::doOutput("`7You reach into your pocket and find that your gold has vanished!");
			output::doOutput("Dejected, you return to the forest.");
			require_once("lib/forest.php");
			forest(true);
			page_footer();
		}

		pageparts::page_header("Private Toilet");
		//$session['user']['usedouthouse'] = 1;
		set_module_pref("usedouthouse",1);
		output::doOutput("`7You pay your %s gold to the Toilet Gnome for the privilege of using the paid outhouse.`n", $cost);
		output::doOutput("This is the cleanest outhouse in the land!`n");
		output::doOutput("The Toilet Paper Gnome tells you if you need anything, just ask.`n");
		if ($session['user']['sex']) {
			output::doOutput("She politely turns her back to you and finishes cleaning the wash stand.`n");
		} else {
			output::doOutput("He politely turns his back to you and finishes cleaning the wash stand.`n");
		}
		$session['user']['gold'] -= $cost;
		debuglog("spent $cost gold to use the outhouse");
		output::addnav("Wash your hands", "runmodule.php?module=outhouse&op=washpay");
		output::addnav("Leave", "runmodule.php?module=outhouse&op=nowash");
	}elseif ($op == "free"){
		pageparts::page_header("Public Toilet!");
		set_module_pref("usedouthouse",1);
		output::doOutput("`2The smell is so strong your eyes tear up and your nose hair curls!`n");
		output::doOutput("After blowing his nose with it, the Toilet Paper Gnome gives you 1 sheet of single-ply TP to use.`n");
		output::doOutput("After looking at the stuff covering his hands, you think you might not want to use it.`n`n");
		output::doOutput("While %s over the big hole in the middle of the room with the TP Gnome observing you closely, you almost slip in.`n", translator::translate_inline($session['user']['sex']?"squatting":"standing"));
		output::doOutput("You go ahead and take care of business as fast as you can; you can only hold your breath so long.`n");
		output::addnav("Wash your hands", "runmodule.php?module=outhouse&op=washfree");
		output::addnav("Leave", "runmodule.php?module=outhouse&op=nowash");
	}elseif ($op == "washpay"|| $op == "washfree"){
		pageparts::page_header("Wash Stand");
		output::doOutput("`2Washing your hands is always a good thing.  You tidy up, straighten your %s in your reflection in the water, and head on your way.`0`n", $session['user']['armor']);
		$goodhabits = e_rand(1, 100);
		if ($goodhabits <= $goodmusthit && $op=="washpay"){
			output::doOutput("`^The Wash Room Fairy blesses you!`n");
			output::doOutput("`7You receive `^%s`7 gold for being sanitary and clean!`0`n", $giveback);
			$session['user']['gold'] += $giveback;
			debuglog("got $giveback gold in the outhouse for washing");
			$givegemtemp = e_rand(1, 100);
			if ($givegemtemp <= $givegempercent){
				$session['user']['gems']++;
				debuglog("gained 1 gem in the outhouse");
				output::doOutput("`&Aren't you the lucky one to find a `%gem`& there by the doorway!`0`n");
			}
			$giveturntemp = e_rand(1, 100);
			if ($giveturntemp <= $giveturnchance) {
				$session['user']['turns']++;
				output::doOutput("`&You gained a turn!`0`n");
			}
		}elseif ($goodhabits <= $goodmusthit && $op == "washfree"){
			if (e_rand(1, 3)==1) {
				output::doOutput("`&You notice a small bag containing `^%s`7 gold that someone left by the washstand.`0", $giveback);
				$session['user']['gold'] += $giveback;
				debuglog("got $giveback gold in the outhouse for washing");
			}
		}
		$args = array(
			'soberval'=>0.9,
			'sobermsg'=>"`&Leaving the outhouse, you feel a little more sober.`n",
			'schema'=>"module-outhouse",
		);
		modules::modulehook("soberup", $args);
		require_once("lib/forest.php");
		forest(true);
	}elseif (($op == "nowash")){
		pageparts::page_header("Stinky Hands");
		output::doOutput("`2Your hands are soiled and real stinky!`n");
		output::doOutput("Didn't your mother teach you any better?`n");
		$takeaway = e_rand(1, 100);
		if ($takeaway >= $badmusthit){
			if ($session['user']['gold'] >= $goldinhand){
				$session['user']['gold'] -= $takeback;
				debuglog("lost $takeback gold in the outhouse for not washing");
				output::doOutput("`nThe Toilet Paper Gnome has thrown you to the slimy, filthy floor and extracted `\$%s gold`2 %s from you due to your slovenliness!`n", $takeback, translator::translate_inline($takeback ==1?"piece":"pieces"));
			}
			output::doOutput("Aren't you glad an embarrassing moment like this isn't in the news?`n");
			if ($session['user']['sex']) {
				$msg = "`2Always cool, %s`2 was seen walking around with a long string of toilet paper stuck to her foot.`n";
			} else {
				$msg = "`2Always cool, %s`2 was seen walking around with a long string of toilet paper stuck to his foot.`n";
			}
			addnews($msg, $session['user']['name']);
		}
		require_once("lib/forest.php");
		forest(true);
	}else{
		pageparts::page_header("The Outhouses");
		output::doOutput("`2The nearby village has two outhouses, which it keeps way out here in the forest because of the warding effect of their smell on creatures.`n`n");
		if (get_module_pref("usedouthouse")==0){
			output::doOutput("In typical caste style, there is a privileged outhouse, and an underprivileged outhouse.");
			output::doOutput("The choice is yours!`0`n`n");
			output::addnav("Toilets");
			if ($canpay){
				output::addnav(array("Private Toilet: (%s gold)", $cost),
						"runmodule.php?module=outhouse&op=pay");
			}else{
				output::doOutput("`2The Private Toilet costs `^%s`2 gold.", $cost);
				output::doOutput("Looks like you are going to have to hold it or use the Public Toilet!");
			}
			output::addnav("Public Toilet: (free)", "runmodule.php?module=outhouse&op=free");
			output::addnav("Hold it", "forest.php");
		}else{
			switch(e_rand(1,3)){
			case 1:
				output::doOutput("The Outhouses are closed for repairs.`n");
				output::doOutput("You will have to hold it till tomorrow!");
				break;
			case 2:
				output::doOutput("As you draw close to the Outhouses, you realize that you simply don't think you can bear the smell of another visit to the Outhouses today.");
				break;
			case 3:
				output::doOutput("You really don't have anything left to relieve today!");
				break;
			}
			output::doOutput("`n`n`7You return to the forest.`0");
			require_once("lib/forest.php");
			forest(true);
		}
	}
	page_footer();
}
