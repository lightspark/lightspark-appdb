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
require(BASE."include/comment.php");
require(BASE."include/appdb.php");
require(BASE."include/screenshot.php");
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
    echo "    <td><font color=white>Application Name</font></td>\n";
    echo "    <td><font color=white>Description</font></td>\n";
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



/* display the versions */
function display_versions($appId, $versions)
{
	if ($versions)
	{
	
		 echo html_frame_start("","98%","",0);
		 echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

		 echo "<tr class=color4>\n";
		 echo "    <td width=80><font color=white>Version</font></td>\n";
		 echo "    <td><font color=white>Description</font></td>\n";
		 echo "    <td width=80><font color=white class=small>Rating With Windows</font></td>\n";
		 echo "    <td width=80><font color=white class=small>Rating Without Windows</font></td>\n";
		 echo "    <td width=40><font color=white class=small>Comments</font></td>\n";
		 echo "</tr>\n\n";
    	
	        $c = 0;
		while(list($idx, $ver) = each($versions))
		{
	             //set row color
	            $bgcolor = (($c % 2) ? "color0" : "color1");

	            //format desc
	            $desc = substr(stripslashes($ver->description),0,75);
	            if(strlen($desc) == 75)
		    $desc .= " ...";		
		
		   //get ratings
	           $r_win = rating_stars_for_version($ver->versionId, "windows");
	           $r_fake = rating_stars_for_version($ver->versionId, "fake");
		   
		   //count comments
		   $r_count = count_comments($appId,$ver->versionId);
		
	           //display row
	           echo "<tr class=$bgcolor>\n";
	           echo "    <td><a href='appview.php?appId=$appId&versionId=$ver->versionId'>".$ver->versionName."</a></td>\n";
	           echo "    <td>$desc &nbsp;</td>\n";
		   echo "    <td align=center>$r_win</td>\n";
		   echo "    <td align=center>$r_fake</td>\n";
		   echo "    <td align=center>$r_count</td>\n";
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
	$app = new Application($appId);
	$data = $app->data;
	if(!$data) {
		// Oops! application not found or other error. do something
		errorpage('Internal Database Access Error');
		exit;
	}

	// header
	apidb_header("Search for bugs in Bugzila for - ".$data->appName);

	//cat display
	display_catpath($app->data->catId, $appId);

	//set Vendor
	$vendor = $app->getVendor();

	//set URL
	$appLinkURL = ($data->webPage) ? "<a href='$data->webPage'>".substr(stripslashes($data->webPage),0,30)."</a>": "&nbsp;";
	
	//set Image
	$img = get_screenshot_img($appId, $versionId);
	
	//start display application
	echo html_frame_start("","98%","",0);
	
	echo '<tr><td class=color4 valign=top>',"\n";	
	echo '<table width="300" border=0 cellpadding=3 cellspacing=1">',"\n";
	echo "<tr class=color0 valign=top><td width='250' align=right> <b>Name</b></td><td width='75%'> ".stripslashes($data->appName)." </td>\n";
	echo "<tr class=color1 valign=top><td width='250' align=right> <b>App Id</b></td><td width='75%'> ".$data->appId." </td>\n";
	echo "<tr class=color0 valign=top><td width='250' align=right> <b>Vendor</b></td><td width='75%'> ".
	     "   <a href='vendorview.php?vendorId=$vendor->vendorId'> ".stripslashes($vendor->vendorName)." </a> &nbsp;\n";
        echo "<tr class=color1 valign=top><td width='250' align=right> <b>All Bugs</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."buglist.cgi?product=Wine&bug_file_loc_type=allwords&bug_file_loc=appdb ".$data->appId."'>
             Look for All bugs in bugzilla </a> &nbsp;\n";
        echo "<tr class=color0 valign=top><td width=250 align=right> <b>Open Bugs</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."buglist.cgi?product=Wine".
             "&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&bug_file_loc_type=allwords&bug_file_loc=appdb ".$data->appId."'>
             Look for Open bugs in bugzilla </a> &nbsp;\n";        
        echo "<tr class=color1 valign=top><td width='250' align=right> <b>Submit a New Bug</b></td><td width='75%'> ".
             "   <a href='".BUGZILLA_ROOT."enter_bug.cgi?product=Wine&bug_file_loc=".APPDB_OWNER_URL."appview.php?appid=".$data->appId."'>
             Submit a new bug in bugzilla </a> &nbsp;\n";
    	echo "</td></tr>\n";
	   
	echo "</table></td><td class=color2 valign=top width='100%'>\n";

	//Notes
	echo "<table width='100%' border=0><tr><td width='100%' valign=top><big><b>Welcome</b></big><br />;
        <p>This is the link between the Wine Application Database and Wine's Buzilla. From here you 
        get search for bugs entered against this application. You can also enter new bugs if you log
        into Wine's Bugzilla.</p>
        <p>The link between the Application Database and Bugzilla is based on the bug having the following URL
        <a href='".APPDB_OWNER_URL."appview.php?appId=".$data->appId."'>
        ".APPDB_OWNER_URL."appview.php?appId=".$data->appId."</a> &nbsp;    
        in the bug's <i>URL</i> Field. If it is not entered, this search page can not find it.
	</td></tr></table>";
		
	echo html_frame_end("For more details and user comments, view the versions of this application.");

        //display versions
	display_versions($appId,$app->getAppVersionList());

	//display bundle
	display_bundle($appId);

}
else
{
	// Oops! Called with no params, bad llamah!
	errorpage('Page Called with No Params!');
	exit;
}
apidb_footer();
?>
