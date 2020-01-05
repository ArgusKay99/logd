<?php
// translator ready
// addnews ready
// mail ready

//ver 1.1 - added a little newday-runonce routine to return the creatures to
//			    some default value after a specified number of days

function crazyaudrey_getmoduleinfo(){
	$info = array(
		"name"=>"Crazy Audrey's Petting Zoo",
		"version"=>"1.1",
		"author"=>"Eric Stevens",
		"category"=>"Forest Specials",
		"download"=>"core_module",
		"settings"=>array(
			"Crazy Audrey Settings,title",
			"cost"=>"Cost to pet,int|5",
			"animal"=>"Name of animal (should be singular)|Kitten",
			"animals"=>"Plural name of animal|Kittens",
			"lanimal"=>"Lowercase name of animal (should be singular)|kitten",
			"lanimals"=>"Lowercase plural name of animal|kittens",
			"The last ones only need to be different for languages which do not capitalize nouns,note",
			"sound"=>"Sound that animal makes|mew",
			"buffname"=>"Name of buff from animal|Warm Fuzzies",
			"gamedaysremaining"=>"How many game days should the animal remain? (set to -1 for indefinite),int|-1",
			"defaultanimal"=>"Name of default animal (should be singular)|Kitten",
			"defaultanimals"=>"Plural name of default animal|Kittens",
			"defaultsound"=>"Sound that default animal makes|mew",
			"defaultbuffname"=>"Name of buff from default animal|Warm Fuzzies",
			"profit"=>"How much profit has Audrey made?,int|5",
			"villagepercent"=>"How often will you see Crazy Audrey sitting in the village square?,range,0,100,1|20",

		),
		"prefs"=>array(
			"Crazy Audrey User Preferences,title",
			"played"=>"Played Baskets Today?,bool|0",
		)
	);
	return $info;
}

function crazyaudrey_install(){
	module_addhook("village");
	module_addhook("village-desc");
	module_addhook("newday");
	module_addhook("newday-runonce");
	module_addeventhook("forest", "return 100;");
	return true;
}

function crazyaudrey_uninstall(){
	return true;
}

function crazyaudrey_dohook($hookname,$args){
	global $session;
	$animals = get_module_setting("animals");
	$lcanimals = get_module_setting("lanimals");
	switch($hookname){
	case "village-desc":
		if (e_rand(1, 100) <= get_module_setting("villagepercent")) {
			output::doOutput("`n`%Crazy Audrey is here with her `#%s`%!`n",$lcanimals);
			$args['doaudrey'] = 1;
		}

	case "village":
		if (!array_key_exists("doaudrey",$args)) $args['doaudrey'] = false;
		if ($args['doaudrey']) {
			$cost = get_module_setting("cost");
			// And since the capital can change the texts
			translator::tlschema($args['schemas']['marketnav']);
			addnav($args["marketnav"]);
			translator::tlschema();
			addnav(array(" ?Pet Crazy Audrey's %s`0 (`^%s gold`0)",$animals,$cost),"runmodule.php?module=crazyaudrey&op=pet");
		}
		break;
	case "newday":
		set_module_pref("played",0);
		break;
	case "newday-runonce":
		$daysremaining=get_module_setting("gamedaysremaining");
		if ($daysremaining>0){
			$daysremaining -= 1;
			set_module_setting("gamedaysremaining",$daysremaining);
		}
		if($daysremaining==0){
		//This is intentionally not an elseif
			set_module_setting("animal",get_module_setting("defaultanimal"));
			set_module_setting("animals",get_module_setting("defaultanimals"));
			set_module_setting("sound",get_module_setting("defaultsound"));
			set_module_setting("buffname",get_module_setting("defaultbuffname"));
			set_module_setting("gamedaysremaining",-1);
		}
		break;

	}
	return $args;
}

function crazyaudrey_runevent($type)
{
	// We act the same for all event types
	crazyaudrey_baskets($type);
}

function crazyaudrey_baskets($type)
{
	global $session;

	$from = "runmodule.php?module=crazyaudrey&";
	if ($type == "forest")
		$from = "forest.php?";

	if ($type == "forest") {
		$session['user']['specialinc'] = "module:crazyaudrey";
	}

	$animal = get_module_setting("animal");
	$lcanimal = get_module_setting("lanimal");
	$lcplural = get_module_setting("lanimals");
	$sound  = get_module_setting("sound");

	$op = http::httpget('op');
	if ($op == "" || $op == "search" || $op == "baskets") {
		if ($op == "baskets") {
			output::doOutput("`5You reach for the lid of one of Crazy Audrey's baskets when you think she is distracted, when out of nowhere, Crazy Audrey appears, ranting feverishly about colored %s, and pulls the baskets to her.`n`n", $lcplural);
		} elseif ($type == "forest") {
			output::doOutput("`5You stumble across a clearing that is oddly quiet.");
			output::doOutput("To one side are three baskets, tightly lidded.");
			output::doOutput("Finding this curious, you cautiously approach them when you hear the faint %s`5 of a %s`5.", $sound, $lcanimal);
			output::doOutput("You reach for the lid of the first basket when out of nowhere, Crazy Audrey appears, ranting feverishly about colored %s`5, and pulls the baskets to her.`n`n", $lcplural);
		}
		output::doOutput("Taken somewhat aback, you decide you had best question her about these %s.`n`n", $lcplural);
		output::doOutput("\"`#Tell me, good woman,`5\" you begin...`n`n");
		output::doOutput("\"`%GOOD GOOD good good goodgoodgoodgoodgood...`5\" Audrey begins to repeat.");
		output::doOutput("Unflustered, you persist.`n`n");
		output::doOutput("\"`#What are these %s`# you speak of?`5\"`n`n", $lcplural);
		output::doOutput("Amazingly, Crazy Audrey suddenly grows quiet and begins to speak in a regal accent both melodious and soft.`n`n");
		output::doOutput("\"`%Of these baskets, have I three,`n");
		output::doOutput("Four %s`% inside each there do be.`n`n", $lcplural);
		output::doOutput("Minds of their own, do they have,`n");
		output::doOutput("Should two alike emerge, you'll get this salve.`n`n");
		output::doOutput("Energy it gives, to fight your foes,`n");
		output::doOutput("Merely rub it 'tween your toes.`n`n");
		output::doOutput("Should no two alike show their head,`n");
		output::doOutput("Earlier today, you'll see your bed.`n`n");
		output::doOutput("That then is my proposition,`n");
		output::doOutput("Shall thou take it, or from me run?`5\"`n`n");
		output::doOutput("Will you play her game?");
		addnav("Play",$from."op=play");
		addnav("Run away from Crazy Audrey",$from."op=run");
	}else if($op=="run"){
		output::doOutput("`5You run, very quickly, away from this mad woman.");
	}else if($op=="play"){
		if ($type == "module-internal") {
			set_module_pref("played",1);
		}
		$colors = array("`^C`&a`Ql`6i`7c`qo","`7T`&i`7g`&e`7r","`QGinger","`&White","`^`bHedgehog!`b");
		$colors = translator::translate_inline($colors);
		$c1 = e_rand(0,3);
		$c2 = e_rand(0,3);
		$c3 = e_rand(0,3);
		if (e_rand(1,20)==1) {
			$c1=4; $c2=4; $c3=4;
		}
		output::doOutput("`5You agree to Crazy Audrey's preposterous game and she thumps the first basket on the lid.");

		if ($c1 == 4) {
			output::doOutput("A %s`5 peeks its head out.`n`n", $colors[$c1]);
		} else {
			output::doOutput("A %s`5 %s`5 peeks its head out.`n`n", $colors[$c1], $lcanimal);
		}
		if ($c2 == 4) {
			output::doOutput("Crazy Audrey then thumps the second basket on the lid, and a %s`5 peeks its head out.`n`n", $colors[$c2]);
		} else {
			output::doOutput("Crazy Audrey then thumps the second basket on the lid, and a %s`5 %s`5 peeks its head out.`n`n", $colors[$c2], $lcanimal);
		}
		if ($c3 == 4) {
			output::doOutput("She thumps the third basket on the lid, and a %s`5 hops out and bounds up to Crazy Audrey's shoulder.`n`n", $colors[$c3]);
		} else {
			output::doOutput("She thumps the third basket on the lid, and a %s`5 %s`5 hops out and bounds up to Crazy Audrey's shoulder.`n`n", $colors[$c3], $lcanimal);
		}

		if ($c1==$c2 && $c2==$c3){
			if ($c1==4){
				$where = translator::translate_inline($type=="forest"?"forest":"crowd");
				output::doOutput("\"`%Hedgehogs?  HEDGEHOGS??  Hahahahaha, HEDGEHOGS!!!!`5\" shouts Crazy Audrey as she snatches them up in glee and runs cheering into the %s.", $where);
				output::doOutput("You notice that she has dropped a full BAG of those wonderful salves.`n`n");
				output::doOutput("`^You gain FIVE forest fights!");
				$session['user']['turns']+=5;
			}else{
				output::doOutput("\"`%Argh, you are ALL very bad %s`%!`5\" shouts Crazy Audrey before hugging her shoulder %s`5 and putting it back in the basket.", $lcplural, $lcanimal);
				output::doOutput("\"`%Because my %s`% all were alike, I grant you TWO salves.`5\"`n`n", $lcplural);
				output::doOutput("You rub the salves on your toes.`n`n");
				output::doOutput("`^You gain TWO forest fights!");
				$session['user']['turns']+=2;
			}
		}elseif ($c1==$c2 || $c2==$c3 || $c1==$c3){
			output::doOutput("\"`%Garr, you crazy %s`%, what do you know?  Why I ought to paint you all different colors!`5\"", $lcplural);
			output::doOutput("Despite her threatening words, Crazy Audrey pets the %s`5 on her shoulder and places it back in the basket, before giving you your salve, which you rub all over your toes.`n`n", $lcanimal);
			output::doOutput("`^You gain a forest fight!");
			$session['user']['turns']++;
		}else{
			output::doOutput("\"`%Well done my pretties!`5\" shouts Crazy Audrey.");
			output::doOutput("Just then her shoulder-mounted %s`5 leaps at you.", $lcanimal);
			if ($session['user']['turns'] > 0) {
				output::doOutput("In fending it off, you lose some energy.");
				$msg = "`^You lose a forest fight!";
				$session['user']['turns']--;
			} else {
				output::doOutput("In fending it off, you get a nasty scratch along one side of your face.");
				$msg = "`^You lose a charm point!";
				if ($session['user']['charm'] > 0)
					$session['user']['charm']--;
			}
			output::doOutput("Finally it hops back in its basket and all is quiet.");
			output::doOutput("Crazy Audrey cackles quietly and looks at you.`n`n");
			output::doOutput($msg);
		}
	}

	if ($op == "run" || $op=="play") {
		if ($type == "forest") {
			$session['user']['specialinc'] = "";
		}
	}
}

function crazyaudrey_run(){
	global $session;
	$op = http::httpget('op');
	if ($op=="pet"){
		page_header("Crazy Audrey's Zoo");
		$cost = get_module_setting("cost");
		$animal = get_module_setting("animal");
		$lcanimal = get_module_setting("lanimal");
		$plural = get_module_setting("animals");
		$lcplural = get_module_setting("lanimals");
		$profit = get_module_setting("profit");
		output::doOutput("`5You cautiously approach Crazy Audrey.");
		output::doOutput("Next to her is a sign that reads, \"`#%s gold to pet %s`#,`5\" and a basket filled with `^%s`5 gold!",$cost,$lcplural,$profit);
		if ($session['user']['gold']>=$cost){
			output::doOutput("You place your `^%s`5 gold in the basket, and spend a few minutes petting one of the %s`5.", $cost, $lcplural);
			output::doOutput("Soon though, Crazy Audrey chases you off, and you stand at a distance admiring the %s`5.",$lcplural);
			$session['user']['gold']-=$cost;
			debuglog("spent $cost gold to pet audrey's pets");
			$profit += $cost;
			set_module_setting("profit",$profit);
			$buffname = get_module_setting("buffname");
			apply_buff('crazyaudrey',array("name"=>$buffname,"rounds"=>5,"activate"=>"defense","defmod"=>1.05, "schema"=>"module-crazyaudrey"));
			output::doOutput("`5After a few minutes, you once again try to approach in order to look into her baskets.");
			if (get_module_pref("played")==0) {
				addnav("Look at Crazy Audrey's baskets","runmodule.php?module=crazyaudrey&op=baskets");
			} else {
				output::doOutput("`5As you approach closer, Crazy Audrey looks up and screams at you. \"`%Hey!!  I recognize you!  You've already played with my %s`% today!  Get away from here, you pervy %s`% fancier!`5\"", $lcplural, $lcanimal);
				output::doOutput("You quickly step back and admire the %s`5 from a safe distance.", $lcplural);
			}
		}else{
			output::doOutput("Not having `^%s`5 gold, you wander sadly away.",$cost);
		}
	}elseif ($op=="baskets" || $op == "play" || $op == "run"){
		page_header("Crazy Audrey");
		crazyaudrey_baskets("module-internal",
				"runmodule.php?module=crazyaudrey");
	}
	if ($op != "baskets") {
		require_once("lib/villagenav.php");
		villagenav();
	}
	page_footer();
}
