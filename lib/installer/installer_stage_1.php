<?php
require_once("lib/pullurl.php");
$license = join("",pullurl("http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode"));
$license = str_replace("\n","",$license);
$license = str_replace("\r","",$license);
$shortlicense=array();
preg_match_all("'<body[^>]*>(.*)</body>'",$license,$shortlicense);
$license = $shortlicense[1][0];
output::doOutput("`@`c`bLicense Agreement`b`c`0");
output::doOutput("`2Before continuing, you must read and understand the following license agreement.`0`n`n");
if (md5($license)=="484d213db9a69e79321feafb85915ff1"){
	$cdomain = "http://creativecommons.org";
	$license = preg_replace("/(href|src)\=\"([^(http)])(\/)?/", "$1=\"".$cdomain."$2", $license);
	rawoutput("<div style='width: 100%; height; 350px; max-height: 350px; overflow: auto; color: #FFFFFF; background-color: #000000; padding: 10px;'>".$license."</div>");
}else{
	output::doOutput("`^Warning, the Creative Commons license has changed, or could not be retrieved from the Creative Commons server.");
	output::doOutput("You should check with the game authors to ensure that the below license agrees with the license under which it was released.");
	output::doOutput("The license may be referenced at <a target='_blank' href='http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode'>the Creative Commons site</a>.",true);
}
$license = join("",file("LICENSE.txt"));
$license = preg_replace("/[^\na-zA-Z0-9!?.,;:'\"\\/\\()@ -\\]\\[]/","",$license);
$licensemd5s = array(
'e281e13a86d4418a166d2ddfcd1e8032'=>true
);
if (isset($licensemd5s[md5($license)])){
	// Reload it so we get the right line breaks, etc.
	//$license = file("LICENSE.txt");
	$license = htmlentities($license, ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"));
	$license = nl2br($license);
	//$license = preg_replace("/<br[^>]*>\s+<br[^>]*>/i","<p>",$license);
	//$license = preg_replace("/<br[^>]*>/i","",$license);
	output::doOutput("`n`n`b`@Plain Text:`b`n`7");
	rawoutput($license);
}else{
	output::doOutput("`^The license file (LICENSE.txt) has been modified.  Please obtain a new copy of the game's code, this file has been tampered with.");
	output::doOutput("Expected MD5 in (".join(array_keys($licensemd5s),",")."), but got ".md5($license));
	$stage=-1;
	$session['stagecompleted']=-1;
}
