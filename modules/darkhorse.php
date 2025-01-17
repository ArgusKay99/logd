<?php
// translator ready
// addnews ready
// mail ready

function darkhorse_getmoduleinfo(){
	$info = array(
		"name"=>"Dark Horse Tavern",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Dark Horse Tavern Settings,title",
			"tavernname"=>"Name of the tavern|Dark Horse Tavern",
		),
		"prefs-mounts"=>array(
			"Dark Horse Tavern Mount Preferences,title",
			"findtavern"=>"Can this mount find the tavern,bool|0",
		),
	);
	return $info;
}

function darkhorse_tavernmount() {
	global $playermount;
	
        // We need the module parameter here because this function can be
	// called from the eventchance eval and this module might not be loaded
	// at that point.
	$tavern = get_module_objpref("mounts", $playermount->getID(), "findtavern", "darkhorse");
	return $tavern;
}

function darkhorse_install(){
	module_addeventhook("forest",
			"require_once(\"modules/darkhorse.php\");
			return (darkhorse_tavernmount() ? 0 : 100);");
	module_addeventhook("travel",
			"require_once(\"modules/darkhorse.php\");
			return (darkhorse_tavernmount() ? 0 : 100);");
	$sql = "DESCRIBE " . db_prefix("mounts");
	$result = db_query($sql);
	while($row = db_fetch_assoc($result)) {
		if ($row['Field'] == "tavern") {
			debug("Migrating tavern for all mounts");
			$sql = "INSERT INTO " . db_prefix("module_objprefs") . " (modulename,objtype,setting,objid,value) SELECT 'darkhorse','mounts','findtavern',mountid,tavern FROM " . db_prefix("mounts");
			db_query($sql);
			debug("Dropping tavern field from mounts table");
			$sql = "ALTER TABLE " . db_prefix("mounts") . " DROP tavern";
			db_query($sql);
		}
	}
	module_addhook("forest");
	module_addhook("mountfeatures");
	module_addhook("moderate");
	return true;
}

function darkhorse_uninstall(){
	return true;
}

function darkhorse_dohook($hookname,$args){
	switch($hookname) {
	case "moderate":
		$args['darkhorse'] = get_module_setting("tavernname");
		break;
	case "mountfeatures":
		$tavern = get_module_objpref("mounts", $args['id'], "findtavern");
		$args['features']['Darkhorse']=$tavern;
		break;
	case "forest":
		if(darkhorse_tavernmount()) {
			// add the nav
			output::addnav("Other");
			$iname = get_module_setting("tavernname");
			global $mount_dev, $playermount;
			list($name, $lcname) = $mount_dev->getName($playermount);
			output::addnav(array("D?Take %s`0 to %s", $lcname, $iname),
					"runmodule.php?module=darkhorse&op=enter");
		}
		break;
	}
	return $args;
}

function darkhorse_checkday(){
	// Reset special-in just in case checkday kicks in.
	$session['user']['specialinc']="";
	checkday();
	// And now set it back.
	$session['user']['specialinc']="module:darkhorse";
}

function darkhorse_bartender($from){
	global $session;
	$what = http::httpget('what');
	if ($what==""){
		output::doOutput("The grizzled old man behind the bar reminds you very much of a strip of beef jerky.`n`n");
		$dname = translator::translate_inline($session['user']['sex']?"lasshie":"shon");
		output::doOutput("\"`7Shay, what can I do for you %s?`0\" inquires the toothless fellow.", $dname);
		output::doOutput("\"`7Don't shee the likesh of your short too offen 'round theshe partsh.`0\"");
		output::addnav("Learn about my enemies",$from."op=bartender&what=enemies");
		output::addnav("Learn about colors",$from."op=bartender&what=colors");
	}elseif($what=="colors"){
		output::doOutput("The old man leans on the bar.");
		output::doOutput("\"`%Sho you want to know about colorsh, do you?`0\" he asks.`n`n");
		output::doOutput("You are about to answer when you realize the question was rhetorical.`n`n");
		output::doOutput("He continues, \"`%To do colorsh, here'sh what you need to do.  Firsht, you ushe a &#0096; mark (found right above the tab key) followed by 1, 2, 3, 4, 5, 6, 7, !, @, #, $, %, ^, &, ), q or Q.  Each of thoshe correshpondsh with a color to look like this: `n`1&#0096;1 `2&#0096;2 `3&#0096;3 `4&#0096;4 `5&#0096;5 `6&#0096;6 `7&#0096;7 `n`!&#0096;! `@&#0096;@ `#&#0096;# `\$&#0096;\$ `%&#0096;% `^&#0096;^ `&&#0096;& `n `)&#0096;) `q&#0096;q `Q&#0096;Q `n`% got it?`0\"`n  You can practice below:", true);
		rawoutput("<form action=\"".$from."op=bartender&what=colors\" method='POST'>");
		$testtext = httppost('testtext');
		$try = translator::translate_inline("Try");
		rawoutput("<input name='testtext' id='testtext'><input type='submit' class='button' value='$try'></form>");
		output::addnav("",$from."op=bartender&what=colors");
		rawoutput("<script language='JavaScript'>document.getElementById('testtext').focus();</script>");
		if ($testtext) {
			output::doOutput("`0You entered %s`n", prevent_colors(HTMLEntities($testtext, ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))),true);
			output::doOutput("It looks like %s`n", $testtext);
		}
		output::doOutput("`0`n`nThese colors can be used in your name, and in any conversations you have.");
	}else if($what=="enemies"){
		$who = http::httpget('who');
		if ($who==""){
			output::doOutput("\"`7Sho, you want to learn about your enemiesh, do you?  Who do you want to know about?  Well?  Shpeak up!  It only costs `^100`7 gold per person for information.`0\"");
			$subop = http::httpget('subop');
			if ($subop!="search"){
				$search = translator::translate_inline("Search");
				rawoutput("<form action='".$from."op=bartender&what=enemies&subop=search' method='POST'><input name='name' id='name'><input type='submit' class='button' value='$search'></form>");
				output::addnav("",$from."op=bartender&what=enemies&subop=search");
				rawoutput("<script language='JavaScript'>document.getElementById('name').focus();</script>");
			}else{
				output::addnav("Search Again",$from."op=bartender&what=enemies");
				$search = "%";
				$name = httppost('name');
				for ($i=0;$i<strlen($name);$i++){
					$search.=substr($name,$i,1)."%";
				}
				$sql = "SELECT name,alive,location,sex,level,laston,loggedin,login FROM " . db_prefix("accounts") . " WHERE (locked=0 AND name LIKE '$search') ORDER BY level DESC";
				$result = db_query($sql);
				$max = db_num_rows($result);
				if ($max > 100) {
					output::doOutput("`n`n\"`7Hey, whatsh you think yoush doin'.  That'sh too many namesh to shay.  I'll jusht tell you 'bout shome of them.`0`n");
					$max = 100;
				}
				$n = translator::translate_inline("Name");
				$lev = translator::translate_inline("Level");
				rawoutput("<table border=0 cellpadding=0><tr><td>$n</td><td>$lev</td></tr>");
				for ($i=0;$i<$max;$i++){
					$row = db_fetch_assoc($result);
					rawoutput("<tr><td><a href='".$from."op=bartender&what=enemies&who=".rawurlencode($row['login'])."'>");
					output_notl("%s", $row['name']);
					rawoutput("</a></td><td>{$row['level']}</td></tr>");
					output::addnav("",$from."op=bartender&what=enemies&who=".rawurlencode($row['login']));
				}
				rawoutput("</table>");
			}
		}else{
			if ($session['user']['gold']>=100){
				$sql = "SELECT name,acctid,alive,location,maxhitpoints,gold,sex,level,weapon,armor,attack,race,defense,charm FROM " . db_prefix("accounts") . " WHERE login='$who'";
				$result = db_query($sql);
				if (db_num_rows($result)>0){
					$row = db_fetch_assoc($result);
					$row = modules::modulehook("adjuststats", $row);
					$name = str_replace("s", "sh", $row['name']);
					$name = str_replace("S", "Sh", $name);
					output::doOutput("\"`7Well... letsh shee what I know about %s`7,`0\" he says...`n`n", $name);
					output::doOutput("`4`bName:`b`6 %s`n", $row['name']);
					output::doOutput("`4`bRace:`b`6 %s`n",
							translator::translate_inline($row['race'],"race"));
					output::doOutput("`4`bLevel:`b`6 %s`n", $row['level']);
					output::doOutput("`4`bHitpoints:`b`6 %s`n", $row['maxhitpoints']);
					output::doOutput("`4`bGold:`b`6 %s`n", $row['gold']);
					output::doOutput("`4`bWeapon:`b`6 %s`n", $row['weapon']);
					output::doOutput("`4`bArmor:`b`6 %s`n", $row['armor']);
					output::doOutput("`4`bAttack:`b`6 %s`n", $row['attack']);
					output::doOutput("`4`bDefense:`b`6 %s`n", $row['defense']);
					output::doOutput("`n`^%s7 ish alsho ", $row['name']);
					$amt=$session['user']['charm'];
					if ($amt == $row['charm']) {
						output::doOutput("ash ugly ash you are.`n");
					} else if ($amt-10 > $row['charm']) {
						output::doOutput("`bmuch`b uglier shan you!`n");
					} else if ($amt > $row['charm']) {
						output::doOutput("uglier shan you.`n");
					} else if ($amt+10 < $row['charm']) {
						output::doOutput("`bmuch`b more beautiful shan you!`n");
					} else {
						output::doOutput("more beautiful shan you.`n");
					}
					$session['user']['gold']-=100;
					debuglog("spent 100 gold to learn about an enemy");
				}else{
					output::doOutput("\"`7Eh..?  I don't know anyone named that.`0\"");
				}
			}else{
				output::doOutput("\"`7Well... letsh shee what I know about cheapshkates like you,`0\" he says...`n`n");
				output::doOutput("`4`bName:`b`6 Get some money`n");
				output::doOutput("`4`bLevel:`b`6 You're too broke`n");
				output::doOutput("`4`bHitpoints:`b`6 Probably more than you`n");
				output::doOutput("`4`bGold:`b`6 Definately richer than you`n");
				output::doOutput("`4`bWeapon:`b`6 Something good enough to lay the smackdown on you`n");
				output::doOutput("`4`bArmor:`b`6 Probably something more fashionable than you`n");
				output::doOutput("`4`bAttack:`b`6 Eleventy billion`n");
				output::doOutput("`4`bDefense:`b`6 Super Duper`n");
			}
		}
	}
	output::addnav("Return to the Main Room",$from."op=tavern");
}

function darkhorse_runevent($type, $link){
	global $session;
	$from = $link;
	$gameret = substr($link, 0, -1);
	$session['user']['specialinc']="module:darkhorse";

	require_once("lib/sanitize.php");
	$iname = get_module_setting("tavernname");

	rawoutput("<span style='color: #787878'>");
	output_notl("`c`b%s`b`c",$iname);
	$op = http::httpget('op');
	switch($op){
	case "":
	case "search":
		darkhorse_checkday();
		output::doOutput("A cluster of trees nearby looks familiar...");
		output::doOutput("You're sure you've seen this place before.");
		output::doOutput("As you approach the grove, a strange mist creeps in around you; your mind begins to buzz, and you're no longer sure exactly how you got here.");
		if(darkhorse_tavernmount()) {
			global $mount_dev, $playermount;
			list($name, $lcname) = $mount_dev->getName($playermount);
			output::doOutput("%s`0 seems to have known the way, however.", $name);
		}
		output::doOutput("`n`nThe mist clears, and before you is a log building with smoke trailing from its chimney.");
		output::doOutput("A sign over the door says `7\"%s.\"`0", $iname);
		output::addnav("Enter the tavern",$from."op=tavern");
		output::addnav("Leave this place",$from."op=leaveleave");
		break;
	case "tavern":
		darkhorse_checkday();
		output::doOutput("You stand near the entrance of the tavern and survey the scene before you.");
		output::doOutput("Whereas most taverns are noisy and raucous, this one is quiet and nearly empty.");
		output::doOutput("In the corner, an old man plays with some dice.");
		output::doOutput("You notice that the tables have been etched on by previous adventurers who have found this place before, and behind the bar, a stick of an old man hobbles around, polishing glasses, as though there were anyone here to use them.");
		output::addnav("Talk to the old man",$from."op=oldman");
		output::addnav("Talk to the bartender",$from."op=bartender");

		// Special case here.  go and see if the comment area is blocked and
		// if so, don't put the link in.
		$args = modules::modulehook("blockcommentarea", array("section"=>"darkhorse"));
		if (!isset($args['block']) || $args['block'] != 'yes') {
			output::addnav("Examine the tables",$from."op=tables");
		}
		output::addnav("Exit the tavern",$from."op=leave");
		break;
	case "tables":
		require_once("lib/commentary.php");
		addcommentary();
		commentdisplay("You examine the etchings in the table:`n`n",
				"darkhorse","Add your own etching:");
		output::addnav("Return to the Main Room",$from."op=tavern");
		break;
	case "bartender":
		darkhorse_bartender($from);
		break;
	case "oldman":
		darkhorse_checkday();
		output::addnav("Old Man");
		modules::modulehook("darkhorsegame", array("return"=>$gameret));
		output::doOutput("The old man looks up at you, his eyes sunken and hollow.");
		output::doOutput("His red eyes make it seem that he may have been crying recently so you ask him what is bothering him.");
		if ($session['user']['sex'] == SEX_MALE) {
			output::doOutput("\"`7Aah, I met an adventurer in the woods, and figured I'd play a little game with her, but she won and took almost all of my money.`0\"`n`n");
		} else {
			output::doOutput("\"`7Aah, I met an adventurer in the woods, and figured I'd play a little game with him, but he won and took almost all of my money.`0\"`n`n");
		}
		$c = navcount();
		if ($c != 0) {
			output::doOutput("`0\"`7Say... why not do an old man a favor and let me try to win some of it back from you?");
			if ($c > 1)output::doOutput(" I can play several games!`0\"");
			elseoutput::doOutput(" Shall we play a game?`0\"");
		}
		$session['user']['specialmisc']="";
		output::addnav("Return to the Main Room",$from."op=tavern");
		break;
	case "leave":
		output::doOutput("You duck out of the tavern, and wander into the thick foliage around you.");
		output::doOutput("That strange mist revisits you, making your mind buzz.");
		output::doOutput("The mist clears, and you find yourself again where you were before the mist first covered you.");
		if(!darkhorse_tavernmount()) {
			output::doOutput(" How exactly you got to the tavern is not exactly clear.");
		}
		$session['user']['specialinc']="";
		break;
	case "leaveleave":
		output::doOutput("You decide that the tavern holds no appeal for you today.");
		$session['user']['specialinc']="";
		break;
	}
	rawoutput("</span>");
}

function darkhorse_run(){
	$op = http::httpget('op');
	if ($op == "enter") {
		httpset("op", "tavern");
		pageparts::page_header(get_module_setting("tavernname"));
		darkhorse_runevent("forest", "forest.php?");
		// Clear the specialinc, just in case.
		$session['user']['specialinc']="";
		page_footer();
	}
}
