<?php
/*****************/
/* search engine */
/*****************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

$search = str_replace("'", "\\'", $_REQUEST['q']);
$search = "%$search%";

$query = "SELECT * FROM appFamily WHERE appName != 'NONAME' AND appName LIKE '$search' ORDER BY appName";
$result = mysql_query($query);

apidb_header("Search Results");

if(mysql_num_rows($result) == 0)
{
	// do something
	echo html_frame_start("","98%");
	echo "No matches found for ". urlencode($_REQUEST['q']) .  "\n";
	echo html_frame_end();
}
else
{

	echo html_frame_start("","98%","",0);
	echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

	echo "<tr bgcolor=#999999>\n";
	echo "    <td><font color=white>Application Name</font></td>\n";
	echo "    <td><font color=white>Description</font></td>\n";
	echo "    <td><font color=white>No. Versions</font></td>\n";
	echo "</tr>\n\n";

	$c = 0;
	while($ob = mysql_fetch_object($result))
	{
	        //skip if a NONAME
	        if ($ob->appName == "NONAME") { continue; }
		
		//set row color
		if ($c % 2 == 1) { $bgcolor = '#E0E0E0'; } else { $bgcolor = '#EFEFEF'; }
		
		//count versions
		$query = mysql_query("SELECT count(*) as versions FROM appVersion WHERE appId = $ob->appId AND versionName != 'NONAME'");
	        $y = mysql_fetch_object($query);
		
		//format desc
		$desc = substr(stripslashes($ob->description),0,75);

		//display row
		echo "<tr bgcolor=$bgcolor>\n";
		echo "    <td>".html_ahref($ob->appName,"appview.php?appId=$ob->appId")."</td>\n";
		echo "    <td>$desc &nbsp;</td>\n";
		echo "    <td>$y->versions &nbsp;</td>\n";
		echo "</tr>\n\n";
		
		$c++;    
	}

    echo "<tr><td colspan=3 bgcolor=#999999><font color=white>$c match(es) found</font></td></tr>\n";
	echo "</table>\n\n";
}

apidb_footer();

?>
