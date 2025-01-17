<?php
// addnews ready
// mail ready
// translator ready

// hilarious copy of mounts.php
require_once("common.php");
require_once("lib/http.php");
require_once("lib/showform.php");

check_su_access(SU_EDIT_MOUNTS);

translator::tlschema("companions");

pageparts::page_header("Companion Editor");

require_once("lib/superusernav.php");
superusernav();

output::addnav("Companion Editor");
output::addnav("Add a companion","companions.php?op=add");

$op = http::httpget('op');
$id = http::httpget('id');
if ($op=="deactivate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=0 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	httpset("op", "");
	invalidatedatacache("companionsdata-$id");
} elseif ($op=="activate"){
	$sql = "UPDATE " . db_prefix("companions") . " SET companionactive=1 WHERE companionid='$id'";
	db_query($sql);
	$op="";
	httpset("op", "");
	invalidatedatacache("companiondata-$id");
} elseif ($op=="del") {
	//drop the companion.
	$sql = "DELETE FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	db_query($sql);
	module_delete_objprefs('companions', $id);
	$op = "";
	httpset("op", "");
	invalidatedatacache("companiondata-$id");
} elseif ($op=="take"){
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query($sql);
	if ($row = db_fetch_assoc($result)) {
		$row['attack'] = $row['attack'] + $row['attackperlevel'] * $session['user']['level'];
		$row['defense'] = $row['defense'] + $row['defenseperlevel'] * $session['user']['level'];
		$row['maxhitpoints'] = $row['maxhitpoints'] + $row['maxhitpointsperlevel'] * $session['user']['level'];
		$row['hitpoints'] = $row['maxhitpoints'];
		$row = modules::modulehook("alter-companion", $row);
		$row['abilities'] = @unserialize($row['abilities']);
		require_once("lib/buffs.php");
		apply_companion($row['name'], $row);
		output::doOutput("`\$Succesfully taken `^%s`\$ as companion.", $row['name']);
	}
	$op = "";
	httpset("op", "");
} elseif ($op=="save"){
	$subop = http::httpget("subop");
	if ($subop == "") {
		$companion = httppost('companion');
		if ($companion) {
			if (!isset($companion['allowinshades'])) {
				$companion['allowinshades'] = 0;
			}
			if (!isset($companion['allowinpvp'])) {
				$companion['allowinpvp'] = 0;
			}
			if (!isset($companion['allowintrain'])) {
				$companion['allowintrain'] = 0;
			}
			if (!isset($companion['abilities']['fight'])) {
				$companion['abilities']['fight'] = false;
			}
			if (!isset($companion['abilities']['defend'])) {
				$companion['abilities']['defend'] = false;
			}
			if (!isset($companion['cannotdie'])) {
				$companion['cannotdie'] = false;
			}
			if (!isset($companion['cannotbehealed'])) {
				$companion['cannotbehealed'] = false;
			}
			$sql = "";
			$keys = "";
			$vals = "";
			$i = 0;
			while(list($key, $val) = each($companion)) {
				if (is_array($val)) $val = addslashes(serialize($val));
				$sql .= (($i > 0) ? ", " : "") . "$key='$val'";
				$keys .= (($i > 0) ? ", " : "") . "$key";
				$vals .= (($i > 0) ? ", " : "") . "'$val'";
				$i++;
			}
			if ($id>""){
				$sql="UPDATE " . db_prefix("companions") .
					" SET $sql WHERE companionid='$id'";
			}else{
				$sql="INSERT INTO " . db_prefix("companions") .
					" ($keys) VALUES ($vals)";
			}
			db_query($sql);
			invalidatedatacache("companiondata-$id");
			if (db_affected_rows()>0){
				output::doOutput("`^Companion saved!`0`n`n");
			}else{
//				if (strlen($sql) > 400) $sql = substr($sql,0,200)." ... ".substr($sql,strlen($sql)-200);
				output::doOutput("`^Companion `\$not`^ saved: `\$%s`0`n`n", $sql);
			}
		}
	} elseif ($subop=="module") {
		// Save modules settings
		$module = http::httpget("module");
		$post = httpallpost();
		reset($post);
		while(list($key, $val) = each($post)) {
			set_module_objpref("companions", $id, $key, $val, $module);
		}
		output::doOutput("`^Saved!`0`n");
	}
	if ($id) {
		$op="edit";
	} else {
		$op = "";
	}
	httpset("op", $op);
}

if ($op==""){
	$sql = "SELECT * FROM " . db_prefix("companions") . " ORDER BY category, name";
	$result = db_query($sql);

	$ops = translator::translate_inline("Ops");
	$name = translator::translate_inline("Name");
	$cost = translator::translate_inline("Cost");

	$edit = translator::translate_inline("Edit");
	$del = translator::translate_inline("Del");
	$take = translator::translate_inline("Take");
	$deac = translator::translate_inline("Deactivate");
	$act = translator::translate_inline("Activate");

	rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999'>");
	rawoutput("<tr class='trhead'><td nowrap>$ops</td><td>$name</td><td>$cost</td></tr>");
	$cat = "";
	$count=0;

	while ($row=db_fetch_assoc($result)) {
		if ($cat!=$row['category']){
			rawoutput("<tr class='trlight'><td colspan='5'>");
			output::doOutput("Category: %s", $row['category']);
			rawoutput("</td></tr>");
			$cat = $row['category'];
			$count=0;
		}
		if (isset($companions[$row['companionid']])) {
			$companions[$row['companionid']] = (int)$companions[$row['companionid']];
		} else {
			$companions[$row['companionid']] = 0;
		}
		rawoutput("<tr class='".($count%2?"trlight":"trdark")."'>");
		rawoutput("<td nowrap>[ <a href='companions.php?op=edit&id={$row['companionid']}'>$edit</a> |");
		output::addnav("","companions.php?op=edit&id={$row['companionid']}");
		if ($row['companionactive']){
			rawoutput("$del |");
		}else{
			$mconf = sprintf($conf, $companions[$row['companionid']]);
			rawoutput("<a href='companions.php?op=del&id={$row['companionid']}'>$del</a> |");
			output::addnav("","companions.php?op=del&id={$row['companionid']}");
		}
		if ($row['companionactive']) {
			rawoutput("<a href='companions.php?op=deactivate&id={$row['companionid']}'>$deac</a> | ");
			output::addnav("","companions.php?op=deactivate&id={$row['companionid']}");
		}else{
			rawoutput("<a href='companions.php?op=activate&id={$row['companionid']}'>$act</a> | ");
			output::addnav("","companions.php?op=activate&id={$row['companionid']}");
		}
		rawoutput("<a href='companions.php?op=take&id={$row['companionid']}'>$take</a> ]</td>");
		output::addnav("", "companions.php?op=take&id={$row['companionid']}");
		rawoutput("<td>");
		output_notl("`&%s`0", $row['name']);
		rawoutput("</td><td>");
		output::doOutput("`%%s gems`0, `^%s gold`0",$row['companioncostgems'], $row['companioncostgold']);
		rawoutput("</td></tr>");
		$count++;
	}
	rawoutput("</table>");
	output::doOutput("`nIf you wish to delete a companion, you have to deactivate it first.");
}elseif ($op=="add"){
	output::doOutput("Add a companion:`n");
	output::addnav("Companion Editor Home","companions.php");
	companionform(array());
}elseif ($op=="edit"){
	output::addnav("Companion Editor Home","companions.php");
	$sql = "SELECT * FROM " . db_prefix("companions") . " WHERE companionid='$id'";
	$result = db_query_cached($sql, "companiondata-$id", 3600);
	if (db_num_rows($result)<=0){
		output::doOutput("`iThis companion was not found.`i");
	}else{
		output::addnav("Companion properties", "companions.php?op=edit&id=$id");
		module_editor_navs("prefs-companions", "companions.php?op=edit&subop=module&id=$id&module=");
		$subop=http::httpget("subop");
		if ($subop=="module") {
			$module = http::httpget("module");
			rawoutput("<form action='companions.php?op=save&subop=module&id=$id&module=$module' method='POST'>");
			module_objpref_edit("companions", $module, $id);
			rawoutput("</form>");
			output::addnav("", "companions.php?op=save&subop=module&id=$id&module=$module");
		} else {
			output::doOutput("Companion Editor:`n");
			$row = db_fetch_assoc($result);
			$row['abilities'] = @unserialize($row['abilities']);
			companionform($row);
		}
	}
}

function companionform($companion){
	// Let's sanitize the data
	if (!isset($companion['companionactive'])) $companion['companionactive'] = "";
	if (!isset($companion['name'])) $companion['name'] = "";
	if (!isset($companion['companionid'])) $companion['companionid'] = "";
	if (!isset($companion['description'])) $companion['description'] = "";
	if (!isset($companion['dyingtext'])) $companion['dyingtext'] = "";
	if (!isset($companion['jointext'])) $companion['jointext'] = "";
	if (!isset($companion['category'])) $companion['category'] = "";
	if (!isset($companion['companionlocation'])) $companion['companionlocation']  = 'all';
	if (!isset($companion['companioncostdks'])) $companion['companioncostdks']  = 0;

	if (!isset($companion['companioncostgems'])) $companion['companioncostgems']  = 0;
	if (!isset($companion['companioncostgold'])) $companion['companioncostgold']  = 0;

	if (!isset($companion['attack'])) $companion['attack'] = "";
	if (!isset($companion['attackperlevel'])) $companion['attackperlevel'] = "";
	if (!isset($companion['defense'])) $companion['defense'] = "";
	if (!isset($companion['defenseperlevel'])) $companion['defenseperlevel'] = "";
	if (!isset($companion['hitpoints'])) $companion['hitpoints'] = "";
	if (!isset($companion['maxhitpoints'])) $companion['maxhitpoints'] = "";
	if (!isset($companion['maxhitpointsperlevel'])) $companion['maxhitpointsperlevel'] = "";

	if (!isset($companion['abilities']['fight'])) $companion['abilities']['fight'] = 0;
	if (!isset($companion['abilities']['defend'])) $companion['abilities']['defend'] =  0;
	if (!isset($companion['abilities']['heal'])) $companion['abilities']['heal'] =  0;
	if (!isset($companion['abilities']['magic'])) $companion['abilities']['magic'] =  0;

	if (!isset($companion['cannotdie'])) $companion['cannotdie'] = 0;
	if (!isset($companion['cannotbehealed'])) $companion['cannotbehealed'] = 1;
	if (!isset($companion['allowinshades'])) $companion['allowinshades'] = 0;
	if (!isset($companion['allowinpvp'])) $companion['allowinpvp'] = 0;
	if (!isset($companion['allowintrain'])) $companion['allowintrain'] = 0;

	rawoutput("<form action='companions.php?op=save&id={$companion['companionid']}' method='POST'>");
	rawoutput("<input type='hidden' name='companion[companionactive]' value=\"".$companion['companionactive']."\">");
	output::addnav("","companions.php?op=save&id={$companion['companionid']}");
	rawoutput("<table width='100%'>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Name:");
	rawoutput("</td><td><input name='companion[name]' value=\"".htmlentities($companion['name'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Dyingtext:");
	rawoutput("</td><td><input name='companion[dyingtext]' value=\"".htmlentities($companion['dyingtext'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Description:");
	rawoutput("</td><td><textarea cols='25' rows='5' name='companion[description]'>".htmlentities($companion['description'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion join text:");
	rawoutput("</td><td><textarea cols='25' rows='5' name='companion[jointext]'>".htmlentities($companion['jointext'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."</textarea></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Category:");
	rawoutput("</td><td><input name='companion[category]' value=\"".htmlentities($companion['category'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\" maxlength='50'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Availability:");
	rawoutput("</td><td nowrap>");
	// Run a modules::modulehook to find out where camps are located.  By default
	// they are located in 'Degolburg' (ie, getgamesetting('villagename'));
	// Some later module can remove them however.
	$vname = settings::getsetting('villagename', LOCATION_FIELDS);
	$locs = array($vname => translator::sprintf_translate("The Village of %s", $vname));
	$locs = modules::modulehook("camplocs", $locs);
	$locs['all'] = translator::translate_inline("Everywhere");
	ksort($locs);
	reset($locs);
	rawoutput("<select name='companion[companionlocation]'>");
	foreach($locs as $loc=>$name) {
		rawoutput("<option value='$loc'".($companion['companionlocation']==$loc?" selected":"").">$name</option>");
	}

	rawoutput("<tr><td nowrap>");
	output::doOutput("Maxhitpoints / Bonus per level:");
	rawoutput("</td><td><input name='companion[maxhitpoints]' value=\"".htmlentities($companion['maxhitpoints'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[maxhitpointsperlevel]' value=\"".htmlentities($companion['maxhitpointsperlevel'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Attack / Bonus per level:");
	rawoutput("</td><td><input name='companion[attack]' value=\"".htmlentities($companion['attack'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[attackperlevel]' value=\"".htmlentities($companion['attackperlevel'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Defense / Bonus per level:");
	rawoutput("</td><td><input name='companion[defense]' value=\"".htmlentities($companion['defense'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"> / <input name='companion[defenseperlevel]' value=\"".htmlentities($companion['defenseperlevel'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");

	rawoutput("<tr><td nowrap>");
	output::doOutput("Fighter?:");
	rawoutput("</td><td><input id='fighter' type='checkbox' name='companion[abilities][fight]' value='1'".($companion['abilities']['fight']==true?" checked":"")." onClick='document.getElementById(\"defender\").disabled=document.getElementById(\"fighter\").checked; if(document.getElementById(\"defender\").disabled==true) document.getElementById(\"defender\").checked=false;'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Defender?:");
	rawoutput("</td><td><input id='defender' type='checkbox' name='companion[abilities][defend]' value='1'".($companion['abilities']['defend']==true?" checked":"")." onClick='document.getElementById(\"fighter\").disabled=document.getElementById(\"defender\").checked; if(document.getElementById(\"fighter\").disabled==true) document.getElementById(\"fighter\").checked=false;'></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Healer level:");
	rawoutput("</td><td valign='top'><select name='companion[abilities][heal]'>");
	for($i=0;$i<=30;$i++) {
		rawoutput("<option value='$i'".($companion['abilities']['heal']==$i?" selected":"").">$i</option>");
	}
	rawoutput("</select></td></tr>");
	rawoutput("<tr><td colspan='2'>");
	output::doOutput("`iThis value determines the maximum amount of HP healed per round`i");
	rawoutput("</td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Magician?:");
	rawoutput("</td><td valign='top'><select name='companion[abilities][magic]'>");
	for($i=0;$i<=30;$i++) {
		rawoutput("<option value='$i'".($companion['abilities']['magic']==$i?" selected":"").">$i</option>");
	}
	rawoutput("</select></td></tr>");
	rawoutput("<tr><td colspan='2'>");
	output::doOutput("`iThis value determines the maximum amount of damage caused per round`i");
	rawoutput("</td></tr>");

	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion cannot die:");
	rawoutput("</td><td><input type='checkbox' name='companion[cannotdie]' value='1'".($companion['cannotdie']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion cannot be healed:");
	rawoutput("</td><td><input type='checkbox' name='companion[cannotbehealed]' value='1'".($companion['cannotbehealed']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");

	output::doOutput("Companion Cost (DKs):");
	rawoutput("</td><td><input name='companion[companioncostdks]' value=\"".htmlentities((int)$companion['companioncostdks'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Cost (Gems):");
	rawoutput("</td><td><input name='companion[companioncostgems]' value=\"".htmlentities((int)$companion['companioncostgems'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Companion Cost (Gold):");
	rawoutput("</td><td><input name='companion[companioncostgold]' value=\"".htmlentities((int)$companion['companioncostgold'], ENT_COMPAT, settings::getsetting("charset", "ISO-8859-1"))."\"></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Allow in shades:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowinshades]' value='1'".($companion['allowinshades']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Allow in PvP:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowinpvp]' value='1'".($companion['allowinpvp']==true?" checked":"")."></td></tr>");
	rawoutput("<tr><td nowrap>");
	output::doOutput("Allow in train:");
	rawoutput("</td><td><input type='checkbox' name='companion[allowintrain]' value='1'".($companion['allowintrain']==true?" checked":"")."></td></tr>");
	rawoutput("</table>");
	$save = translator::translate_inline("Save");
	rawoutput("<input type='submit' class='button' value='$save'></form>");
}

page_footer();
