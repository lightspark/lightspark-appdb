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
require(BASE."include/comment.php");
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
 */
function display_bundle($appId)
{
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

        //format desc
        $aDesc = explode("\n",$oApp->data->description,2);
        //display row
        echo "<tr class=$bgcolor>\n";
        echo "    <td><a href='appview.php?appId=$ob->appId'>".stripslashes($ob->appName)."</a></td>\n";
        echo "    <td>".$aDesc[0]."</td>\n";
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
    $s .= add_br(stripslashes($oData->noteDesc));
    $s .= "</td></tr>\n";

    if ($_SESSION['current']->isLoggedIn() && ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($_REQUEST['appId'], $_REQUEST['versionId'])))
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
function display_versions($appId, $versions)
{
    if ($versions)
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
        while(list($idx, $ver) = each($versions))
        {
            //set row color
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

            // Description
            $desc = trim_description($ver->description);
            if(strlen($desc) == 75)
                $desc .= " ...";    
         
            //count comments
            $r_count = count_comments($appId,$ver->versionId);
    
            //display row
            echo "<tr class=$bgcolor>\n";
            echo "    <td><a href='appview.php?versionId=$ver->versionId'>".$ver->versionName."</a></td>\n";
            echo "    <td>$desc &nbsp;</td>\n";
            echo "    <td align=center>$ver->maintainer_rating</td>\n";
            echo "    <td align=center>$ver->maintainer_release</td>\n";
            echo "    <td align=center>$r_count</td>\n";
            echo "</tr>\n\n";

            $c++;   
    }
    
    echo "</table>\n";
    echo html_frame_end("Click the Version Name to view the details of that Version");
    }
}



/**
 * We want to see an application family (=no version) 
 */
if(!is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId']))
{
    errorpage("Something went wrong with the application or version id");
    exit;
}


if($_REQUEST['appId'])
{
    $app = new Application($_REQUEST['appId']);
    $data = $app->data;
    if(!$data)
    {
        // oops! application not found or other error. do something
        errorpage('Internal Database Access Error');
        exit;
    }

    // show Vote Menu
    if($_SESSION['current']->isLoggedIn())
        apidb_sidebar_add("vote_menu");

    // header
    apidb_header("Viewing App - ".$data->appName);

    // cat display
    display_catpath($app->data->catId, $_REQUEST['appId']);

    // set Vendor
    $vendor = $app->getVendor();

    // set URL
    $appLinkURL = ($data->webPage) ? "<a href='$data->webPage'>".substr(stripslashes($data->webPage),0,30)."</a>": "&nbsp;";
  
    // start display application
    echo html_frame_start("","98%","",0);
    echo "<link rel=\"stylesheet\" href=\"./application.css\" type=\"text/css\">";
    echo "<tr><td class=color4 valign=top>\n";
    echo "  <table>\n";
    echo "    <tr><td>\n";

    echo '      <table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "        <tr class=color0 valign=top><td width=\"100\"><b>Name</b></td><td width='100%'> ".stripslashes($data->appName)." </td>\n";
    echo "        <tr class=\"color1\"><td><b>Vendor</b></td><td> ".
         "        <a href='vendorview.php?vendorId=$vendor->vendorId'> ".stripslashes($vendor->vendorName)." </a> &nbsp;\n";
    echo "        <tr class=\"color0\"><td><b>BUGS</b></td><td> ".
         "        <a href='bugs.php?appId=$data->appId.'>Check for bugs in bugzilla </a> &nbsp;\n";
    echo "        </td></tr>\n";
    echo "        <tr class=\"color0\"><td><b>Votes</b></td><td> ";
    echo vote_count_app_total($data->appId);
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
    $img = get_screenshot_img($_REQUEST['appId']);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";
    
    echo "      </table>\n"; /* close of name/vendor/bugs/url table */

    echo "    </td></tr>\n";
    echo "    <tr><td>\n";

    // Display all supermaintainers maintainers of this application
    echo "      <table class=color4 width=250 border=1>\n";
    echo "        <tr><td align=left><b>Super maintainers:</b></td></tr>\n";
    $other_maintainers = getSuperMaintainersUserIdsFromAppId($_REQUEST['appId']);
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
        if($_SESSION['current']->isSuperMaintainer($_REQUEST['appId'])) /* yep */
        {
            echo '        <form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a super maintainer" class=button>';
        } else /* nope */
        {
            echo '        <form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a super maintainer of this app" class=button>';
        }

        echo "        <input type=\"hidden\" name=\"appId\" value=\"".$_REQUEST['appId']."\">";
        echo "        <input type=\"hidden\" name=\"superMaintainer\" value=\"1\">"; /* set superMaintainer to 1 because we are at the appFamily level */
        echo "        </form>";

        if($_SESSION['current']->isSuperMaintainer($_REQUEST['appId']) || $_SESSION['current']->hasPriv("admin"))
        {
            echo '        <form method="post" name="edit" action="admin/editAppFamily.php"><input type="hidden" name="appId" value="'.$_REQUEST['appId'].'"><input type="submit" value="Edit App" class="button"></form>';
            echo '<form method="post" name="message" action="appsubmit.php?appId='.$_REQUEST['appId'].'&apptype=2">';
            echo '<input type=submit value="Add Version" class="button">';
            echo '</form>';
        }
        if($_SESSION['current']->hasPriv("admin"))
        {
            $url = BASE."admin/deleteAny.php?what=appFamily&appId=".$_REQUEST['appId']."&confirmed=yes";
            echo "        <form method=\"post\" name=\"edit\" action=\"javascript:deleteURL(\"Are you sure?\", \"".$url."\")\"><input type=\"submit\" value=\"Delete App\" class=\"button\"></form>";
            echo '        <form method="post" name="edit" action="admin/editBundle.php"><input type="hidden" name="bundleId" value="'.$_REQUEST['appId'].'"><input type="submit" value="Edit Bundle" class="button"></form>';
        }
    } else
    {
        echo '        <input type=submit value="Log in to become a super maintainer" class=button>';
    }
    echo "        </center></td></tr>\n";
    echo "      </table>\n"; /* close of super maintainers table */

    echo "    </td></tr>\n";

   echo "    </td></tr>\n";

    echo "  </table>\n"; /* close the table that contains the whole left hand side of the upper table */

    // description
    echo "  <td class=color2 valign=top width='100%'>\n";
    echo "    <table width='100%' border=0><tr><td width='100%' valign=top><span class=\"title\">Description</span>\n";
    echo $data->description;
    echo "    </td></tr></table>\n";
    echo html_frame_end("For more details and user comments, view the versions of this application.");

    // display versions
    display_versions($_REQUEST['appId'],$app->getAppVersionList());

    // display bundle
    display_bundle($_REQUEST['appId']);

    // disabled for now
    //log_application_visit($_REQUEST['appId']);
}

#######################################
# We want to see a particular version #
#######################################
else if($_REQUEST['versionId'])
{
    //FIXME: get rid of appId references everywhere, as version is enough.
    $sQuery = "SELECT appId FROM appVersion WHERE versionId = '".$_REQUEST['versionId']."'";
    $hResult = query_appdb($sQuery);
    $oRow = mysql_fetch_object($hResult);
    $appId = $oRow->appId; 

    $app = new Application($oRow->appId);
    $data = $app->data;
    if(!$data) 
    {
        // Oops! application not found or other error. do something
        errorpage('Internal Database Access Error. No App found.');
        exit;
    }

    $ver = $app->getAppVersion($_REQUEST['versionId']);
    if(!$ver) 
    {
        // Oops! Version not found or other error. do something
        errorpage('Internal Database Access Error. No Version Found.');
        exit;
    }

    // header
    apidb_header("Viewing App Version - ".$data->appName);

    // cat
    display_catpath($app->data->catId, $appId, $_REQUEST['versionId']);
  
    // set URL
    $appLinkURL = ($ver->webPage) ? "<a href='$ver->webPage'>".substr(stripslashes($ver->webPage),0,30)."</a>": "&nbsp;";

    // start version display
    echo html_frame_start("","98%","",0);
    echo "<link rel=\"stylesheet\" href=\"./application.css\" type=\"text/css\">";  
    echo '<tr><td class=color4 valign=top>',"\n";
    echo '<table width="250" border=0 cellpadding=3 cellspacing=1">',"\n";
    echo "<tr class=color0 valign=top><td width=100> <b>Name</b></td><td width='100%'>".stripslashes($data->appName)."</td>\n";
    echo "<tr class=color1 valign=top><td> <b>Version</b></td><td>".stripslashes($ver->versionName)."</td></tr>\n";

    // links
    $result = query_appdb("SELECT * FROM appData WHERE appId = $appId AND versionID = ".$_REQUEST['versionId']." AND type = 'url'");
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
    echo "<tr class=\"color1\" valign=\"top\"><td> <b>Maintainer Rating</b></td><td>".stripslashes($ver->maintainer_rating)."</td></tr>\n";
    echo "<tr class=\"color0\" valign=\"top\"><td> <b>Maintainers Version</b></td><td>".stripslashes($ver->maintainer_release)."</td></tr>\n";

    // image
    $img = get_screenshot_img($appId, $_REQUEST['versionId']);
    echo "<tr><td align=center colspan=2>$img</td></tr>\n";

    // display all maintainers of this application
    echo "<tr class=color0><td align=left colspan=2><b>Maintainers of this application:</b>\n";
    echo "<table width=250 border=0>";
    $other_maintainers = getMaintainersUserIdsFromAppIdVersionId($appId, $_REQUEST['versionId']);
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
        if($_SESSION['current']->isSuperMaintainer($appId))
        {
            echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a supermaintainer" class=button>';
            echo "<input type=hidden name='superMaintainer' value=1>";
        } else
        {
            /* are we already a maintainer? */
            if($_SESSION['current']->isMaintainer($appId, $_REQUEST['versionId'])) /* yep */
            {
                echo '<form method=post name=message action="maintainerdelete.php"><input type=submit value="Remove yourself as a maintainer" class=button>';
                echo "<input type=hidden name='superMaintainer' value=0>";
            } else /* nope */
            {
                echo '<form method=post name=message action="maintainersubmit.php"><input type=submit value="Be a maintainer for this app" class=button>';
            }
        }

        echo "<input type=hidden name=\"appId\" value=\"".$appId."\">";
        echo "<input type=hidden name=\"versionId\" value=\"".$_REQUEST['versionId']."\">";
        echo "</form>";
    } else
    {
        echo '<form method=post name=message action="account.php?cmd=login">';
        echo '<input type=submit value="Log in to become an app maintainer" class=button>';
        echo '</form>';
    }
    
    echo "</center></td></tr>";

    if ($_SESSION['current']->isLoggedIn() && ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($appId, $_REQUEST['versionId'])))
    {
        echo "<tr><td colspan = 2><center>";
        echo '<form method=post name=message action=admin/editAppVersion.php?appId='.$appId.'&versionId='.$_REQUEST['versionId'].'>';
        echo '<input type=submit value="Edit Version Info" class=button>';
        echo '</form>';
        $url = BASE."admin/deleteAny.php?what=appVersion&appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."&confirmed=yes";
        echo "<form method=\"post\" name=\"delete\" action=\"javascript:deleteURL('Are you sure?', '".$url."')\">";
        echo '<input type=submit value="Delete Version" class="button">';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$_REQUEST['versionId'].'>';
        echo '<input type=submit value="Add Note" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$_REQUEST['versionId'].'>';
        echo '<input type=hidden name="noteTitle" value="HOWTO">';
        echo '<input type=submit value="Add How To" class=button>';
        echo '</form>';
        echo '</form>';
        echo '<form method=post name=message action=admin/addAppNote.php?versionId='.$versionId.'>';
        echo '<input type=hidden name="noteTitle" value="WARNING">';
        echo '<input type=submit value="Add Warning" class=button>';
        echo '</form>';
        echo "</center></td></tr>";
    }

    echo "</table><td class=color2 valign=top width='100%'>\n";

    // description
    echo "<table width='100%' border=0><tr><td width='100%' valign=top> <b>Description</b><br />\n";
    echo $ver->description;
    echo "</td></tr>";

    /* close the table */
    echo "</table>\n";

    echo html_frame_end();

    $rNotes = query_appdb("SELECT * FROM appNotes WHERE versionId = ".$_REQUEST['versionId']);
    
    while( $oNote = mysql_fetch_object($rNotes) )
    {
        echo show_note($oNote->noteTitle,$oNote);
    }
    
    // Comments Section
    view_app_comments($_REQUEST['versionId']);
  
} else 
{
    // Oops! Called with no params, bad llamah!
    errorpage('Page Called with No Params!');
    exit;
}

apidb_footer();
?>
