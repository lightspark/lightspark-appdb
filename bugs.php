<?php
/*********************************/
/* bugs linked to an application */
/*********************************/

/*
 * application environment
 */
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/appdb.php");
require(BASE."include/category.php");


function display_catpath($catId, $appId, $versionId = '')
{
    $cat = new Category($catId);

    $catFullPath = make_cat_path($cat->getCategoryPath(),$appId, $versionId);
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: $catFullPath</b><br />\n";
    echo html_frame_end();
}

/* display the SUB apps that belong to this app */
function display_bundle($appId)
{
    $result = query_appdb("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
			  "WHERE bundleId = $appId AND appBundle.appId = appFamily.appId");
    if(!$result || mysql_num_rows($result) == 0)
	{
	    // do nothing
	    return;
	}

    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

    echo "<tr class=color4>\n";
    echo "    <td>Application Name</td>\n";
    echo "    <td>Description</td>\n";
    echo "</tr>\n\n";

    $c = 0;
    while($ob = mysql_fetch_object($result))
	{
	    //set row color
	    $bgcolor = (($c % 2) ? "color0" : "color1");

	    //format desc
	    $desc = substr(stripslashes($ob->description),0,50);
	    if(strlen($desc) == 50)
		$desc .= " ...";

	    //display row
	    echo "<tr class=$bgcolor>\n";
	    echo "    <td><a href='appview.php?appId=$ob->appId'>".stripslashes($ob->appName)."</a></td>\n";
	    echo "    <td>$desc &nbsp;</td>\n";
	    echo "</tr>\n\n";

	    $c++;
	}

    echo "</table>\n\n";
    echo html_frame_end();
}



/**
 * display the versions 
 */
function display_versions($iAppId, $aVersionsIds)
{
    if ($aVersionsIds)
    {
        echo html_frame_start("","98%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

        echo "<tr class=color4>\n";
        echo "    <td width=80>Version</td>\n";
        echo "    <td>Description</td>\n";
        echo "    <td width=80>Rating</td>\n";
        echo "    <td width=80>Wine version</td>\n";
        echo "    <td width=40>Comments</td>\n";
        echo "</tr>\n\n";
      
        $c = 0;
        foreach($aVersionsIds as $iVersionId)
        {
            $oVersion = new Version($iVersionId);

            // set row color
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

            //display row
            echo "<tr class=$bgcolor>\n";
            echo "    <td><a href=\"appview.php?versionId=".$iVersionId."\">".$oVersion->sName."</a></td>\n";
            echo "    <td>".trim_description($oVersion->sDescription)."</td>\n";
            echo "    <td align=center>".$oVersion->sTestedRating."</td>\n";
            echo "    <td align=center>".$oVersion->sTestedVersion."</td>\n";
            echo "    <td align=center>".sizeof($oVersion->aCommentsIds)."</td>\n";
            echo "</tr>\n\n";

            $c++;   

        }
        echo "</table>\n";
        echo html_frame_end("Click the Version Name to view the details of that Version");
    }
}


/* code to View an application's Bugs */
$appId = $_REQUEST['appId'];

if(!is_numeric($appId))
{
	errorpage("Something went wrong with the IDs");
	exit;
}

if($appId)
{
	$oApp = new Application($appId);
	if(!$oApp->iAppId) {
		// Oops! application not found or other error. do something
		errorpage('Internal Database Access Error');
		exit;
	}

	// header
	apidb_header("Search for bugs in Bugzila for - ".$oApp->sName);

	//cat display
	display_catpath($oApp->iCatId, $oApp->iAppId);

	//set Vendor
	$oVendor = new Vendor($oApp->iVendorId);

	//set URL
	$appLinkURL = ($oApp->sWebPage) ? "<a href='$oApp->sWebPage'>".substr(stripslashes($oApp->sWebPage),0,30)."</a>": "&nbsp;";
	
	//set Image
	$img = get_screenshot_img($oApp->iAppId, $versionId);
	
	//start display application
	echo html_frame_start("","98%","",0);
	
	echo '<tr><td class=color4 valign=top>',"\n";	
	echo '<table width="300" border=0 cellpadding=3 cellspacing=1">',"\n";
	echo "<tr class=color0 valign=top><td width='250' align=right> <b>Name</b></td><td width='75%'> ".$oApp->sName." </td>\n";
	echo "<tr class=color1 valign=top><td width='250' align=right> <b>App Id</b></td><td width='75%'> ".$oApp->iAppId." </td>\n";
	echo "<tr class=color0 valign=top><td width='250' align=right> <b>Vendor</b></td><td width='75%'> ".
	     "   <a href='vendorview.php?vendorId=".$oVendor->iVendorId."'> ".$oVendor->sName." </a> &nbsp;\n";
        echo "<tr class=color1 valign=top><td width='250' align=right> <b>All Bugs</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."buglist.cgi?product=Wine&bug_file_loc_type=allwords&bug_file_loc=appdb ".$oApp->iAppId."'>
             Look for All bugs in bugzilla </a> &nbsp;\n";
        echo "<tr class=color0 valign=top><td width=250 align=right> <b>Open Bugs</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."buglist.cgi?product=Wine".
             "&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&bug_file_loc_type=allwords&bug_file_loc=appdb ".$oApp->iAppId."'>
             Look for Open bugs in bugzilla </a> &nbsp;\n";        
        echo "<tr class=color1 valign=top><td width='250' align=right> <b>Submit a New Bug</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."enter_bug.cgi?product=Wine&bug_file_loc=".APPDB_OWNER_URL."appview.php?appid=".$oApp->iAppId."'>
             Submit a new bug in bugzilla </a> &nbsp;\n";
    	echo "</td></tr>\n";
	   
	echo "</table></td><td class=color2 valign=top width='100%'>\n";

	//Notes
	echo "<table width='100%' border=0><tr><td width='100%' valign=top><big><b>Welcome</b></big><br />
        <p>This is the link between the Wine Application Database and Wine's Buzilla. From here you 
        get search for bugs entered against this application. You can also enter new bugs if you log
        into Wine's Bugzilla.</p>
        <p>The link between the Application Database and Bugzilla is based on the bug having the following URL
        <a href='".APPDB_OWNER_URL."appview.php?appId=".$oApp->iAppId."'>
        ".APPDB_OWNER_URL."appview.php?appId=".$oApp->iAppId."</a> &nbsp;    
        in the bug's <i>URL</i> Field. If it is not entered, this search page can not find it.
	</td></tr></table>";
		
	echo html_frame_end("For more details and user comments, view the versions of this application.");

        //display versions
	display_versions($oApp->iAppId,$oApp->aVersionsIds);

	//display bundle
	display_bundle($oApp->iAppId);

}
else
{
	// Oops! Called with no params, bad llamah!
	errorpage('Page Called with No Params!');
	exit;
}
apidb_footer();
?>
