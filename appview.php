<?php
/**********************************/
/* code to display an application */
/**********************************/

/*
 * application environment
 */
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/appdb.php");
require(BASE."include/vote.php");
require(BASE."include/category.php");
require(BASE."include/screenshot.php");
require(BASE."include/maintainer.php");


/**
 * display the full path of the Category we are looking at
 */
function display_catpath($catId, $appId, $versionId = '')
{
    $cat = new Category($catId);

    $catFullPath = make_cat_path($cat->getCategoryPath(), $appId, $versionId);
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $catFullPath ."</b><br />\n";
    echo html_frame_end();
}


/**
 * display the SUB apps that belong to this app 
 * FIXME:
 */
function display_bundle($appId)
{
    $oApp = new Application($appId);
    $result = query_appdb("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
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

        //display row
        echo "<tr class=$bgcolor>\n";
        echo "    <td><a href='appview.php?appId=$ob->appId'>".stripslashes($ob->appName)."</a></td>\n";
        echo "    <td>".trim_description($oApp->sDescription)."</td>\n";
        echo "</tr>\n\n";

        $c++;
    }

    echo "</table>\n\n";
    echo html_frame_end();
}

/* Show note */
function show_note($sType,$oData){
    
    switch($sType)
    {
        case 'WARNING':
        $color = 'red';
        $title = 'Warning';
        break;

        case 'HOWTO';
        $color = 'green';
        $title = 'HOWTO';
        break;

        default:
        
        if(!empty($oData->noteTitle))
            $title = $oData->noteTitle;
        else 
            $title = 'Note';
            
        $color = 'blue';
    }
    
    $s = html_frame_start("","98%",'',0);

    $s .= "<table width='100%' border=0 cellspacing=0>\n";
    $s .= "<tr width='100%' bgcolor='$color' align=center valign=top><td><b>$title</b></td></tr>\n";
    $s .= "<tr><td class='note'>\n";
    $s .= $oData->noteDesc;
    $s .= "</td></tr>\n";

    if ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($_REQUEST['versionId']))
    {
        $s .= "<tr width='100%' class=color1 align=center valign=top><td>";
        $s .= "<form method=post name=message action='admin/editAppNote.php?noteId={$oData->noteId}'>";
        $s .= '<input type=submit value="Edit Note" class=button>';
        $s .= '</form></td></tr>';
    }

    $s .= "</table>\n";
    $s .= html_frame_end();

    return $s;
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
        echo "    <td width=80><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Description</font></td>\n";
        echo "    <td width=80><font color=white class=small>Rating</font></td>\n";
        echo "    <td width=80><font color=white class=small>Wine version</font></td>\n";
        echo "    <td width=40><font color=white class=small>Comments</font></td>\n";
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


if(!is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId']))
{
    errorpage("Something went wrong with the application or version id");
    exit;
}



/**
 * We want to see an application family (=no version).
 */
if($_REQUEST['appId'])
{
    $oApp = new Application($_REQUEST['appId']);

    // show Vote Menu
    if($_SESSION['current']->isLoggedIn())
        apidb_sidebar_add("vote_menu");

    // header
    apidb_header("Viewing App - ".$oApp->sName);

    // cat display
    display_catpath($oApp->iCatId, $oApp->iAppId);

    // set Vendor
    $oVendor = new Vendor($oApp->iVendorId);

    // set URL
    $appLinkURL = ($oApp->sWebpage) ? "<a href=\"$data->webPage\">".substr(stripslashes($oApp->sWebpage),0,30)."</a>": "&nbsp;";
  
    // start display application
    echo html_frame_start("","98%","",0);
    echo "<link rel=\"stylesheet\" href=\"./application.css\" type=\"text/css\">";
    echo "<tr><td class=color4 valign=top>\n";
    echo "  <table>\n";
    echo "    <tr><td>\n";

    echo '      <table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "        <tr class=color0 valign=top><td width=\"100\"><b>Name</b></td><td width='100%'> ".$oApp->sName." </td>\n";
    echo "        <tr class=\"color1\"><td><b>Vendor</b></td><td> ".
         "        <a href='vendorview.php?vendorId=$oVendor->iVendorId'> ".$oVendor->sName." </a> &nbsp;\n";
    echo "        <tr class=\"color0\"><td><b>BUGS</b></td><td> ".
         "        <a href=\"bugs.php?appId=".$oApp->iAppId."\">Check for bugs in bugzilla </a> &nbsp;\n";
    echo "        </td></tr>\n";
    echo "        <tr class=\"color0\"><td><b>Votes</b></td><td> ";
    echo vote_count_app_total($oApp->iAppId);
    echo "        </td></tr>\n";
    
    // main URL
    echo "        <tr class=\"color1\"><td><b>URL</b></td><td>".$appLinkURL."</td></tr>\n";

    // optional links
    $result = query_appdb("SELECT * FROM appData WHERE appId = ".$_REQUEST['appId']." AND versionID = 0 AND type = 'url'");
    if($result && mysql_num_rows($result) > 0)
    {
        echo "        <tr class=\"color1\"><td> <b>Links</b></td><td>\n";
        while($ob = mysql_fetch_object($result))
        {
            echo "        <a href='$ob->url'>".substr(stripslashes($ob->description),0,30)."</a> <br />\n";
        }
            echo "        </td></tr>\n";
        }
  
    // image
    $img = get_screenshot_img($oApp->iAppId);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";
    
    echo "      </table>\n"; /* close of name/vendor/bugs/url table */

    echo "    </td></tr>\n";
    echo "    <tr><td>\n";

    // Display all supermaintainers maintainers of this application
    echo "      <table class=color4 width=250 border=1>\n";
    echo "        <tr><td align=left><b>Super maintainers:</b></td></tr>\n";
    $other_maintainers = getSuperMaintainersUserIdsFromAppId($oApp->iAppId);
    if($other_maintainers)
    {
        while(list($index, list($userIdValue)) = each($other_maintainers))
        {
            $oUser = new User($userIdValue);
            echo "        <tr><td align=left>\n";
            echo "        <li>".$oUser->sRealname."</td></tr>\n";
        }
    } else
    {
        echo "        <tr><td align=right>No maintainers.Volunteer today!</td></tr>\n";
    }

    // Display the app maintainer button
    echo "        <tr><td><center>\n";
    if($_SESSION['current']->isLoggedIn())
    {
        /* are we already a maintainer? */
        if($_SESSION['current']->isSuperMaintainer($oApp->iAppId)) /* yep */
        {
            echo '        <form method="post" name="message" action="maintainerdelete.php"><input type=submit value="Remove yourself as a super maintainer" class="button">';
        } else /* nope */
        {
            echo '        <form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a super maintainer of this app" class="button">';
        }

        echo "        <input type=\"hidden\" name=\"appId\" value=\"".$oApp->iAppId."\">";
        echo "        <input type=\"hidden\" name=\"superMaintainer\" value=\"1\">"; /* set superMaintainer to 1 because we are at the appFamily level */
        echo "        </form>";

        if($_SESSION['current']->isSuperMaintainer($oApp->iAppId) || $_SESSION['current']->hasPriv("admin"))
        {
            echo '        <form method="post" name="edit" action="admin/editAppFamily.php"><input type="hidden" name="appId" value="'.$_REQUEST['appId'].'"><input type="submit" value="Edit App" class="button"></form>';
        }
        if($_SESSION['current']->isLoggedIn())
        {
            echo '<form method="post" name="message" action="appsubmit.php?appId='.$oApp->iAppId.'&apptype=2">';
            echo '<input type=submit value="Add Version" class="button">';
            echo '</form>';
        }
        if($_SESSION['current']->hasPriv("admin"))
        {
            $url = BASE."admin/deleteAny.php?what=appFamily&appId=".$oApp->iAppId."&confirmed=yes";
            echo "        <form method=\"post\" name=\"edit\" action=\"javascript:deleteURL('Are you sure?', '".$url."')\"><input type=\"submit\" value=\"Delete App\" class=\"button\"></form>";
            echo '        <form method="post" name="edit" action="admin/editBundle.php"><input type="hidden" name="bundleId" value="'.$oApp->iAppId.'"><input type="submit" value="Edit Bundle" class="button"></form>';
        }
    } else
    {
        echo '<form method="post" action="account.php?cmd=login"><input type="submit" value="Log in to become a super maintainer" class="button"></form>';
    }
    echo "        </center></td></tr>\n";
    echo "      </table>\n"; /* close of super maintainers table */

    echo "    </td></tr>\n";

   echo "    </td></tr>\n";

    echo "  </table>\n"; /* close the table that contains the whole left hand side of the upper table */

    // description
    echo "  <td class=color2 valign=top width='100%'>\n";
    echo "    <table width='100%' border=0><tr><td width='100%' valign=top><span class=\"title\">Description</span>\n";
    echo $oApp->sDescription;
    echo "    </td></tr></table>\n";
    echo html_frame_end("For more details and user comments, view the versions of this application.");

    // display versions
    display_versions($oApp->iAppId,$oApp->aVersionsIds);

    // display bundle
    display_bundle($oApp->iAppId);

    // disabled for now
    //log_application_visit($oApp->iAppId);
}


/*
 * We want to see a particular version.
 */
else if($_REQUEST['versionId'])
{
    $oVersion = new Version($_REQUEST['versionId']);
    $oApp = new Application($oVersion->iAppId);
    if(!$oApp->iAppId) 
    {
        // Oops! application not found or other error. do something
        errorpage('Internal Database Access Error. No App found.');
        exit;
    }

    if(!$oVersion->iVersionId) 
    {
        // Oops! Version not found or other error. do something
        errorpage('Internal Database Access Error. No Version Found.');
        exit;
    }

    // header
    apidb_header("Viewing App Version - ".$oVersion->sName);

    // cat
    display_catpath($oApp->iCatId, $oApp->iAppId, $oVersion->iVersionId);
  
    // set URL
    $appLinkURL = ($oApp->sWebpage) ? "<a href='$oApp->sWebpage'>".substr(stripslashes($oApp->sWebpage),0,30)."</a>": "&nbsp;";

    // start version display
    echo html_frame_start("","98%","",0);
    echo "<link rel=\"stylesheet\" href=\"./application.css\" type=\"text/css\">";  
    echo '<tr><td class=color4 valign=top>',"\n";
    echo '<table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "<tr class=color0 valign=top><td width=100> <b>Name</b></td><td width='100%'>".$oApp->sName."</td>\n";
    echo "<tr class=color1 valign=top><td> <b>Version</b></td><td>".$oVersion->sName."</td></tr>\n";

    // links
    $result = query_appdb("SELECT * FROM appData WHERE versionID = ".$oVersion->iVersionId." AND type = 'url'");
    if($result && mysql_num_rows($result) > 0)
    {
        echo "        <tr class=\"color1\"><td><b>Links</b></td><td>\n";
        while($ob = mysql_fetch_object($result))
        {
            echo "        <a href='$ob->url'>".substr(stripslashes($ob->description),0,30)."</a> <br />\n";
        }
            echo "        </td></tr>\n";
    }    

    // rating Area
    echo "<tr class=\"color1\" valign=\"top\"><td> <b>Maintainer Rating</b></td><td>".$oVersion->sTestedRating."</td></tr>\n";
    echo "<tr class=\"color0\" valign=\"top\"><td> <b>Maintainers Version</b></td><td>".$oVersion->sTestedRelease."</td></tr>\n";

    // image
    $img = get_screenshot_img($oApp->iAppId, $oVersion->iVersionId);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";

    // display all maintainers of this application
    echo "<tr class=color0><td align=left colspan=2><b>Maintainers of this application:</b>\n";
    echo "<table width=250 border=0>";
    $other_maintainers = getMaintainersUserIdsFromAppIdVersionId($oApp->iAppId, $oVersion->iVersionId);
    if($other_maintainers)
    {
        while(list($index, list($userIdValue)) = each($other_maintainers))
        {
            $oUser = new User($userIdValue);
            echo "<tr class=color0><td align=left colspan=2>";
            echo "<li>".$oUser->sRealname."</td></tr>\n";
        }
    } else
    {
        echo "<tr class=color0><td align=right colspan=2>";
        echo "No maintainers. Volunteer today!</td></tr>\n";
    }
    echo "</table></td></tr>";

    // display the app maintainer button
    echo "<tr><td colspan = 2><center>";
    if($_SESSION['current']->isLoggedIn())
    {
        /* is this user a maintainer of this version by virtue of being a super maintainer */
        /* of this app family? */
        if($_SESSION['current']->isSuperMaintainer($oApp->iAppId))
        {
            echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a supermaintainer" class=button>';
            echo "<input type=hidden name='superMaintainer' value=1>";
        } else
        {
            /* are we already a maintainer? */
            if($_SESSION['current']->isMaintainer($oVersion->iVersionId)) /* yep */
            {
                echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a maintainer" class=button>';
                echo "<input type=hidden name='superMaintainer' value=0>";
            } else /* nope */
            {
                echo '<form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a maintainer for this app" class=button>';
            }
        }

        echo "<input type=hidden name=\"appId\" value=\"".$oApp->iAppId."\">";
        echo "<input type=hidden name=\"versionId\" value=\"".$oVersion->iVersionId."\">";
        echo "</form>";
    } else
    {
        echo '<form method=post name=message action="account.php?cmd=login">';
        echo '<input type=submit value="Log in to become an app maintainer" class=button>';
        echo '</form>';
    }
    
    echo "</center></td></tr>";

    if ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($oVersion->iVersionId))
    {
        echo "<tr><td colspan = 2><center>";
        echo '<form method=post name=message action=admin/editAppVersion.php?appId='.$oApp->iAppId.'&versionId='.$oVersion->iVersionId.'>';
        echo '<input type=submit value="Edit Version Info" class=button>';
        echo '</form>';
        $url = BASE."admin/deleteAny.php?what=appVersion&appId=".$oApp->iAppId."&versionId=".$oVersion->iVersionId."&confirmed=yes";
        echo "<form method=\"post\" name=\"delete\" action=\"javascript:deleteURL('Are you sure?', '".$url."')\">";
        echo '<input type=submit value="Delete Version" class="button">';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$oVersion->iVersionId.'>';
        echo '<input type=submit value="Add Note" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$oVersion->iVersionId.'>';
        echo '<input type=hidden name="noteTitle" value="HOWTO">';
        echo '<input type=submit value="Add How To" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$oVersion->iVersionId.'>';
        echo '<input type=hidden name="noteTitle" value="WARNING">';
        echo '<input type=submit value="Add Warning" class=button>';
        echo '</form>';
        echo "</center></td></tr>";
    }

    echo "</table><td class=color2 valign=top width='100%'>\n";

    // description
    echo "<table width='100%' border=0><tr><td width='100%' valign=top> <b>Description</b><br />\n";
    echo $oVersion->sDescription;
    echo "</td></tr>";

    /* close the table */
    echo "</table>\n";

    echo html_frame_end();

    $rNotes = query_appdb("SELECT * FROM appNotes WHERE versionId = ".$oVersion->iVersionId);
    
    while( $oNote = mysql_fetch_object($rNotes) )
    {
        echo show_note($oNote->noteTitle,$oNote);
    }
    
    // Comments Section
    view_app_comments($oVersion->iVersionId);
  
} else 
{
    // Oops! Called with no params, bad llamah!
    errorpage('Page Called with No Params!');
    exit;
}

apidb_footer();
?>
