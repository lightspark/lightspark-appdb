<?php
/**********************************/
/* code to display an application */
/**********************************/

/**
 * APPLICATION ENVIRONMENT
 */
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");
require(BASE."include/"."comments.php");
require(BASE."include/"."appdb.php");

require(BASE."include/"."vote.php");
require(BASE."include/"."rating.php");
require(BASE."include/"."category.php");

require(BASE."include/"."maintainer.php");



// NOTE: app Owners will see this menu too, make sure we don't show admin-only options
function admin_menu()
{
    global $apidb_root;

    $m = new htmlmenu("Admin");
    if($_REQUEST[versionId]) {
        $m->add("Add Note", $apidb_root."admin/addAppNote.php?appId=$appId&versionId=".$_REQUEST['versionId']);
        $m->addmisc("&nbsp;");

        $m->add("Edit Version", $apidb_root."admin/editAppVersion.php?appId=$appId&versionId=".$_REQUEST['versionId']);

        $url = $apidb_root."admin/deleteAny.php?what=appVersion&versionId=".$_REQUEST['versionId']."&confirmed=yes";
        $m->add("Delete Version", "javascript:deleteURL(\"Are you sure?\", \"".$url."\")");
    } else
    {
        $m->add("Add Version", $apidb_root."admin/addAppVersion.php?appId=".$_REQUEST['appId']);
        $m->addmisc("&nbsp;");
  
        $m->add("Edit App", $apidb_root."admin/editAppFamily.php?appId=".$_REQUEST['appId']);
  
        // global admin options
        if(havepriv("admin"))
        {
            $url = $apidb_root."admin/deleteAny.php?what=appFamily&appId=".$_REQUEST['appId']."&confirmed=yes";
            $m->add("Delete App", "javascript:deleteURL(\"Are you sure?\", \"".$url."\")");
            $m->addmisc("&nbsp;");
            $m->add("Edit Owners", $apidb_root."admin/editAppOwners.php?appId=".$_REQUEST['appId']);
            $m->add("Edit Bundle", $apidb_root."admin/editBundle.php?bundleId=".$_REQUEST['appId']);
        }
    }
    
    $m->done();
}


/**
 * Get a random image for a particular version of an app.
 * If the version is not set, get a random app image 
 */
function get_screenshot_img($appId, $versionId="") 
{
    global $apidb_root;

    if($versionId) 
    {
        $result = mysql_query("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND versionId = $versionId AND type = 'image' ORDER BY rand");
    }
    else {
       $result = mysql_query("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND type = 'image' ORDER BY rand");
    }
    
    if(!$result || !mysql_num_rows($result))
    {
        $imgFile = "<img src='".$apidb_root."images/no_screenshot.gif' border=0 alt='No Screenshot' />";
    }
    else
    {
        $ob = mysql_fetch_object($result);
        $imgFile = "<img src='appimage.php?imageId=$ob->id&width=128&height=128' ".
                   "border=0 alt='$ob->description' />";
    }
    
    $img = html_frame_start("",'128','',2);
    $img .= "<a href='screenshots.php?appId=$appId&versionId=$versionId'>$imgFile</a>";
    $img .= html_frame_end()."<br />";
    
    return $img;
}


/**
 * TODO: what does it do ? 
 */
function display_catpath($catId)
{
    $cat = new Category($catId);

    $catFullPath = make_cat_path($cat->getCategoryPath());
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $catFullPath ."</b><br>\n";
    echo html_frame_end();
}


/**
 * display the SUB apps that belong to this app 
 */
function display_bundle($appId)
{
    $result = mysql_query("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
                        "WHERE bundleId = $appId AND appBundle.appId = appFamily.appId");
    if(!$result || mysql_num_rows($result) == 0)
    {
         return; // do nothing
    }

    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

    echo "<tr class=color4>\n";
    echo "    <td><font color=white>Application Name</font></td>\n";
    echo "    <td><font color=white>Description</font></td>\n";
    echo "</tr>\n\n";

    $c = 0;
    while($ob = mysql_fetch_object($result)) {
        //set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //format desc
        $desc = substr(stripslashes($ob->description),0,50);
        if(strlen($desc) == 50) $desc .= " ...";

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
 * display the notes for the app 
 */
function display_notes($appId, $versionId = 0)
{
    $result = mysql_query("SELECT noteId,noteTitle FROM appNotes ".
        "WHERE appId = $appId AND versionId = $versionId");
        
    if(!$result || mysql_num_rows($result) == 0)
    {
        // do nothing
        return;
    }
    
    echo "<tr class=color1><td valign=top align=right> <b>Notes</b></td><td>\n";
    
    $c = 1;
    while($ob = mysql_fetch_object($result))
    {
        // skip if NONAME
        if ($ob->noteTitle == "NONAME" || $ob->noteTitle == "WARNING" || $ob->noteTitle == "HOWTO") { continue; }
      
        // set link for version
        if ($versionId != 0)
        {
            $versionLink = "&versionId=$versionId";
        }
      
        // display row
        if (havepriv("admin") || isMaintainer($appId,$versionId) )
            echo "    <a href='admin/editAppNote.php?noteId=".$ob->noteId."&appId=$appId".$versionLink."'> $c. ".substr(stripslashes($ob->noteTitle),0,30)."</a><br>\n";
        else
            echo "    <a href='noteview.php?noteId=".$ob->noteId."&appId=$appId".$versionLink."'> $c. ".substr(stripslashes($ob->noteTitle),0,30)."</a><br>\n";
        $c++;
    }

    echo "</td></tr>\n";
}

/**
 * display the versions 
 */
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
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

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


$appId = $_REQUEST['appId'];
$versionId = $_REQUEST['versionId'];

/**
 * We want to see an application family (=no version) 
 */
if(!is_numeric($appId))
{
    errorpage("Something went wrong with the IDs");
    exit;
}

if($appId && !$versionId)
{
    $app = new Application($appId);
    $data = $app->data;
    if(!$data)
    {
        // oops! application not found or other error. do something
        errorpage('Internal Database Access Error');
        exit;
    }

    // show Vote Menu
    if(loggedin())
        apidb_sidebar_add("vote_menu");

    // show Admin Menu
    if(loggedin() && (havepriv("admin") || $_SESSION['current']->ownsApp($appId)))
        apidb_sidebar_add("admin_menu");

    // header
    apidb_header("Viewing App - ".$data->appName);

    // cat display
    display_catpath($app->data->catId);

    // set Vendor
    $vendor = $app->getVendor();

    // set URL
    $appLinkURL = ($data->webPage) ? "<a href='$data->webPage'>".substr(stripslashes($data->webPage),0,30)."</a>": "&nbsp;";
  
    // start display application
    echo html_frame_start("","98%","",0);
  
    echo "<tr><td class=color4 valign=top>\n";
    echo "  <table>\n";
    echo "    <tr><td>\n";

    echo '      <table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "        <tr class=color0 valign=top><td width='100' align=right> <b>Name</b></td><td width='100%'> ".stripslashes($data->appName)." </td>\n";
    echo "        <tr class=color1 valign=top><td align=right> <b>Vendor</b></td><td> ".
         "        <a href='vendorview.php?vendorId=$vendor->vendorId'> ".stripslashes($vendor->vendorName)." </a> &nbsp;\n";
    echo "        <tr class=color0 valign=top><td align=right> <b>BUGS</b></td><td> ".
         "        <a href='bugs.php?appId=$data->appId.'> Check for bugs in bugzilla </a> &nbsp;\n";        
    echo "        </td></tr>\n";
  
    // display notes
    display_notes($appId);
  
    // main URL
    echo "        <tr class=color1 valign=top><td align=right> <b>URL</b></td><td>".$appLinkURL."</td></tr>\n";

    // optional links
    $result = mysql_query("SELECT * FROM appData WHERE appId = $appId AND type = 'url'");
    if($result && mysql_num_rows($result) > 0)
    {
        echo "        <tr class=color1><td valign=top align=right> <b>Links</b></td><td>\n";
        while($ob = mysql_fetch_object($result))
        {
            echo "        <a href='$ob->url'>".substr(stripslashes($ob->description),0,30)."</a> <br>\n";
        }
            echo "        </td></tr>\n";
        }
  
    // image
    $img = get_screenshot_img($appId);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";
    
    // display app owner
    $result = mysql_query("SELECT * FROM appOwners WHERE appId = $appId");
    if($result && mysql_num_rows($result) > 0)
    {
        echo "        <tr class=color0><td valign=top align=right> <b>Owner</b></td>\n";
        echo "        <td>\n";
        while($ob = mysql_fetch_object($result))
        {
            $inResult = mysql_query("SELECT username,email FROM user_list WHERE userid = $ob->ownerId");
            if ($inResult && mysql_num_rows($inResult) > 0)
            {
                $foo = mysql_fetch_object($inResult);
                echo "        <a href='mailto:$foo->email'>".substr(stripslashes($foo->username),0,30)."</a> <br>\n";
            }
        }
        echo "        </td></tr>\n";
    }
    echo "      </table>\n"; /* close of name/vendor/bugs/url table */

    echo "    </td></tr>\n";
    echo "    <tr><td>\n";

    // Display all supermaintainers maintainers of this application
    echo "      <table class=color4 width=250 border=1>\n";
    echo "        <tr><td align=left><b>Super maintainers:</b></td></tr>\n";
    $other_maintainers = getSuperMaintainersUserIdsFromAppId($appId);
    if($other_maintainers)
    {
        while(list($index, list($userIdValue)) = each($other_maintainers))
        {
            echo "        <tr><td align=left>\n";
            echo "        <li>".lookupUsername($userIdValue)."</td></tr>\n";
        }
    } else
    {
        echo "        <tr><td align=right>No maintainers.Volunteer today!</td></tr>\n";
    }

    // Display the app maintainer button
    echo "        <tr><td><center>\n";
    if(loggedin())
    {
        /* are we already a maintainer? */
        if(isSuperMaintainer($appId, $versionId)) /* yep */
        {
            echo '        <form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a super maintainer" class=button>';
        } else /* nope */
        {
            echo '        <form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a super maintainer of this app" class=button>';
        }

        echo "        <input type=hidden name='appId' value=$appId>";
        echo "        <input type=hidden name='versionId' value=$versionId>";
        echo "        <input type=hidden name='superMaintainer' value=1>"; /* set superMaintainer to 1 because we are at the appFamily level */
        echo "        </form>";
    } else
    {
        echo '        <input type=submit value="Log in to become a super maintainer" class=button>';
    }
    echo "        </center></td></tr>\n";
    echo "      </table>\n"; /* close of super maintainers table */

    echo "    </td></tr>\n";

    echo "    <tr><td>\n";
    echo "      <center><a href='appsubmit.php?appId=$data->appId&apptype=2'> Submit New Version </a> &nbsp;<center>\n";
    echo "    </td></tr>\n";

    echo "    </td></tr>\n";

    echo "  </table>\n"; /* close the table that contains the whole left hand side of the upper table */

    // description
    echo "  <td class=color2 valign=top width='100%'>\n";
    echo "    <table width='100%' border=0><tr><td width='100%' valign=top><b>Description</b><br>\n";
    echo add_br(stripslashes($data->description));

    echo "    </td></tr></table>\n";

    echo html_frame_end("For more details and user comments, view the versions of this application.");

    // display versions
    display_versions($appId,$app->getAppVersionList());

    // display bundle
    display_bundle($appId);

    // disabled for now
    //log_application_visit($appId);
}

#######################################
# We want to see a particular version #
#######################################
else if($appId && $versionId)
{
    $app = new Application($appId);
    $data = $app->data;
    if(!$data) 
    {
        // Oops! application not found or other error. do something
        errorpage('Internal Database Access Error. No App found.');
        exit;
    }

    $ver = $app->getAppVersion($versionId);
    if(!$ver) 
    {
        // Oops! Version not found or other error. do something
        errorpage('Internal Database Access Error. No Version Found.');
        exit;
    }

    // rating menu
    if(loggedin()) 
    {
        apidb_sidebar_add("rating_menu");
    }

    // admin menu
    if(loggedin() && havepriv("admin")) 
    {
        apidb_sidebar_add("admin_menu");
    }

    // header
     apidb_header("Viewing App Version - ".$data->appName);

    // cat
    display_catpath($app->data->catId);
  
    // set URL
    $appLinkURL = ($ver->webPage) ? "<a href='$ver->webPage'>".substr(stripslashes($ver->webPage),0,30)."</a>": "&nbsp;";

    // start version display
    echo html_frame_start("","98%","",0);
  
    echo '<tr><td class=color4 valign=top>',"\n";
    echo '<table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "<tr class=color0 valign=top><td width=100> <b>Name</b></td><td width='100%'>".stripslashes($data->appName)."</td>\n";
    echo "<tr class=color1 valign=top><td> <b>Version</b></td><td>".stripslashes($ver->versionName)."</td></tr>\n";
    echo "<tr class=color0 valign=top><td> <b>URL</b></td><td>".stripslashes($appLinkURL)."</td></tr>\n";

    // rating Area
    $r_win = rating_stars_for_version($versionId, "windows");
    $r_fake = rating_stars_for_version($versionId, "fake");

    echo "<tr class=color1 valign=top><td> <b>Rating</b></td><td> $r_win \n";
    echo "<br> $r_fake </td></tr>\n";

    // notes
    display_notes($appId, $versionId);

    // image
    $img = get_screenshot_img($appId, $versionId);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";

    // display all maintainers of this application
    echo "<tr class=color0><td align=left colspan=2><b>Maintainers of this application:</b>\n";
    echo "<table width=250 border=0>";
    $other_maintainers = getMaintainersUserIdsFromAppIdVersionId($appId, $versionId);
    if($other_maintainers)
    {
        while(list($index, list($userIdValue)) = each($other_maintainers))
        {
            echo "<tr class=color0><td align=left colspan=2>";
            echo "<li>".lookupUsername($userIdValue)."</td></tr>\n";
        }
    } else
    {
        echo "<tr class=color0><td align=right colspan=2>";
        echo "No maintainers. Volunteer today!</td></tr>\n";
    }
    echo "</table></td></tr>";

    // display the app maintainer button
    echo "<tr><td colspan = 2><center>";
    if(loggedin())
    {
        /* is this user a maintainer of this version by virtue of being a super maintainer */
        /* of this app family? */
        if(isSuperMaintainer($appId) && !isMaintainer($appId, $versionId))
        {
            echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a supermaintainer" class=button>';
            echo "<input type=hidden name='superMaintainer' value=1>";
        } else
        {
            /* are we already a maintainer? */
            if(isMaintainer($appId, $versionId)) /* yep */
            {
                echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a maintainer" class=button>';
                echo "<input type=hidden name='superMaintainer' value=0>";
            } else /* nope */
            {
                echo '<form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a maintainer for this app" class=button>';
            }
        }

        echo "<input type=hidden name='appId' value=$appId>";
        echo "<input type=hidden name='versionId' value=$versionId>";
        echo "</form>";
    } else
    {
        echo '<form method=post name=message action="account.php?cmd=login">';
        echo '<input type=submit value="Log in to become an app maintainer" class=button>';
        echo '</form>';
    }
    
    echo "</center></td></tr>";

    if (loggedin() && (havepriv("admin") || isMaintainer($appId, $versionId)))
    {
        echo "<tr><td colspan = 2><center>";
        echo '<form method=post name=message action=admin/editAppVersion.php?appId='.$appId.'&versionId='.$versionId.'>';
        echo '<input type=submit value="Edit Version Info" class=button>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?appId='.$appId.'&versionId='.$versionId.'>';
        echo '<input type=submit value="Add Note" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?appId='.$appId.'&versionId='.$versionId.'>';
        echo '<input type=hidden name="noteTitle" value="HOWTO">';
        echo '<input type=submit value="Add How To" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?appId='.$appId.'&versionId='.$versionId.'>';
        echo '<input type=hidden name="noteTitle" value="WARNING">';
        echo '<input type=submit value="Add Warning" class=button>';
        echo '</form>';
        echo "</center></td></tr>";
    }

    echo "</table><td class=color2 valign=top width='100%'>\n";

    //Desc Image
    echo "<table width='100%' border=0><tr><td width='100%' valign=top> <b>Description</b><br>\n";
    echo add_br(stripslashes($ver->description));
    echo "</td></tr>";

    /* close the table */
    echo "</table>\n";

    echo html_frame_end();

    //Show Warnings
    $result = mysql_query("SELECT * FROM appNotes WHERE appId = $appId and versionId = $versionId and noteTitle = 'WARNING'");
    if($result && mysql_num_rows($result))
    {
        while($ob = mysql_fetch_object($result))
        {
            echo html_frame_start("","98%",'',0);

            echo "<table width='100%' border=0 cellspacing=0>","\n";
            echo "<tr width='100%' bgcolor=red align=center valign=top><td><b>Warning</b><br></td></tr>\n";
            echo "<tr><td>\n";
            echo add_br(stripslashes($ob->noteDesc));
            echo "</td></tr>\n";

            if (loggedin() && (havepriv("admin") || isMaintainer($appId, $versionId)))
            {
                echo "<tr width='100%' class=color1 align=center valign=top><td>";
                echo '<form method=post name=message action=admin/editAppNote.php?noteId='.$ob->noteId.'&appId='.$appId.'&versionId='.$versionId.'>';
                echo '<input type=submit value="Edit Warning Info" class=button>';
                echo '</form></td></tr>';
            }
            echo "</table>\n";
            echo html_frame_end();
        }
    }

    // show How tos
    $result = mysql_query("SELECT * FROM appNotes WHERE appId = $appId and versionId = $versionId and noteTitle = 'HOWTO'");
    if($result && mysql_num_rows($result))
    {
        while($ob = mysql_fetch_object($result))
        {
            echo html_frame_start("","98%",'',0);

            echo "<table width='100%' border=0 cellspacing=0>","\n";
            echo "<tr width='100%' bgcolor=green align=center valign=top><td><b>How To</b><br></td></tr>\n";
            echo "<tr><td>\n";
            echo add_br(stripslashes($ob->noteDesc));
            echo "</td></tr>\n";

            if (loggedin() && (havepriv("admin") || isMaintainer($appId, $versionId)))
            {
                echo "<tr width='100%' class=color1 align=center valign=top><td>";
                echo '<form method=post name=message action=admin/editAppNote.php?noteId='.$ob->noteId.'&appId='.$appId.'&versionId='.$versionId.'>';
                echo '<input type=submit value="Edit How to Info" class=button>';
                echo '</form></td></tr>';
            }
            echo "</table>\n";
            echo html_frame_end();
        }
    }
    //TODO: code to view/add user experience record
    //    if(!$versionId) 
    //    {
    //        $versionId = 0;
    //    }

    // Comments Section
    view_app_comments($appId, $versionId);
  
} else 
{
    // Oops! Called with no params, bad llamah!
    errorpage('Page Called with No Params!');
    exit;
}
?>

<p>&nbsp;</p>

<?
apidb_footer();
?>
