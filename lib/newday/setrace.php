<?php
$setrace = http::httpget("setrace");
if ($setrace!=""){
	$vname = settings::getsetting("villagename", LOCATION_FIELDS);
	//in case the module wants to reference it this way.
	$session['user']['race']=$setrace;
	// Set the person to the main village/capital by default
	$session['user']['location'] = $vname;
	modules::modulehook("setrace");
	output::addnav("Continue","newday.php?continue=1$resline");
}else{
	output::doOutput("Where do you recall growing up?`n`n");
	modules::modulehook("chooserace");
}
if (navcount()==0){
	clearoutput();
	pageparts::page_header("No Races Installed");
	output::doOutput("No races were installed in this game.");
	output::doOutput("So we'll call you a 'human' and get on with it.");
	if ($session['user']['superuser'] & (SU_MEGAUSER|SU_MANAGE_MODULES)) {
		output::doOutput("You should go into the module manager off of the super user grotto, install and activate some races.");
	} else {
		output::doOutput("You might want to ask your admin to install some races, they're really quite fun.");
	}
	$session['user']['race']="Human";
	output::addnav("Continue","newday.php?continue=1$resline");
	page_footer();
}else{
	pageparts::page_header("A little history about yourself");
	page_footer();
}
