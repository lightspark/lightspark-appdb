<?php
/*****************/
/* search engine */
/*****************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");

$sQuery = "SELECT *
           FROM appFamily
           WHERE appName != 'NONAME'
           AND queued = 'false'
           AND (appName LIKE '%".addslashes($_REQUEST['q'])."%'
           OR keywords LIKE '%".addslashes($_REQUEST['q'])."%')
           ORDER BY appName";
$hResult = query_appdb($sQuery);

apidb_header("Search Results");

if(mysql_num_rows($hResult) == 0)
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

	echo "<tr class=color4>\n";
	echo "    <td><font color=white>Application Name</font></td>\n";
	echo "    <td><font color=white>Description</font></td>\n";
	echo "    <td><font color=white>No. Versions</font></td>\n";
	echo "</tr>\n\n";

	$c = 0;
	while($ob = mysql_fetch_object($hResult))
	{
	        //skip if a NONAME
	        if ($ob->appName == "NONAME") { continue; }
		
		//set row color
		$bgcolor = ($c % 2) ? 'color0' : 'color1';
		
		//count versions
		$query = query_appdb("SELECT count(*) as versions FROM appVersion WHERE appId = $ob->appId AND versionName != 'NONAME'");
	        $y = mysql_fetch_object($query);
		
		//display row
		echo "<tr class=$bgcolor>\n";
		echo "    <td>".html_ahref($ob->appName,"appview.php?appId=$ob->appId")."</td>\n";
		echo "    <td>".trim_description($ob->description)."</td>\n";
		echo "    <td>$y->versions &nbsp;</td>\n";
		echo "</tr>\n\n";
		
		$c++;    
	}

    echo "<tr><td colspan=3 class=color4><font color=white>$c match(es) found</font></td></tr>\n";
    echo "</table>\n\n";
}

apidb_footer();
?>
