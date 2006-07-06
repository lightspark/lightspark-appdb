<?php
/********************************************************/
/* code to view and approve new application data        */
/********************************************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/mail.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");

$aClean = array(); //array of user input

$aClean['iId'] = makeSafe($_REQUEST['iId']);
$aClean['sSub'] = makeSafe($_REQUEST['sSub']);
$aClean['sAdd'] = makeSafe($_REQUEST['sAdd']);
$aClean['sDescription'] = makeSafe($_REQUEST['sDescription']);
$aClean['sReplyText'] = makeSafe($_REQUEST['sReplyText']);
$aClean['sReject'] = makeSafe($_REQUEST['sReject']);

// deny access if not admin or at least some kind of maintainer
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer())
    util_show_error_page("Insufficient privileges.");

// shows the list of appdata in queue
if (!$aClean['iId'])
{
    apidb_header("Admin Application Data Queue");

    /* retrieve the queued apps */
    $hResult = $_SESSION['current']->getAppDataQuery("*", false, true);

    if(!$hResult || !mysql_num_rows($hResult))
    {
        // no appData in queue
        echo html_frame_start("","90%");
        echo '<p><b>The App Data Queue is empty.</b></p>',"\n";
        echo '<p>There is nothing for you to do. Check back later.</p>',"\n";        
        echo html_frame_end("&nbsp;");         
    } else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is a list of application data submitted by users.\n";
        echo "Please inspect carefully this data before accepting it.\n";
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
        
        echo "<tr class=color4>\n";
        echo "    <td>Submission Date</td>\n";
        echo "    <td>Submitter</td>\n";
        echo "    <td>Application</td>\n";
        echo "    <td>Version</td>\n";
        echo "    <td>Type</td>\n";
        echo "    <td align=\"center\">Action</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($oRow = mysql_fetch_object($hResult))
        {   
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=\"$bgcolor\">\n";
            echo "<td>".print_date(mysqltimestamp_to_unixtimestamp($oRow->submitTime))."</td>\n";
            $oUser = new User($oRow->submitterId);
            echo "<td>";
            echo $oUser->sEmail ? "<a href=\"mailto:".$oUser->sEmail."\">":"";
            echo $oUser->sRealname;
            echo $oUser->sEmail ? "</a>":"";
            echo "</td>\n";
            echo "<td>".Application::lookup_name($oRow->appId)."</td>\n";
            echo "<td>".Version::lookup_name($oRow->versionId)."</td>\n";
            echo "<td>".$oRow->type."</td>\n";
            echo "<td align=\"center\">[<a href='adminAppDataQueue.php?iId=$oRow->id'>process</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }      
} else // shows a particular appdata
{
    $hResult = $_SESSION['current']->getAppDataQuery($aClean['iId'], false, false);
    $obj_row = mysql_fetch_object($hResult);
    
    if(!$aClean['sSub']=="inside_form")
    {       
        apidb_header("Admin Application Data Queue");

        echo '<form name="sQform" action="adminAppDataQueue.php" method="post">',"\n";
        // help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a submitted scrrenshot is rejected.\n";
        echo "It is not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    

        // view application details
        echo html_frame_start("New Application Data Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
  
        // app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".Application::lookup_name($obj_row->appId)."</td></tr>\n";

        // version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".Version::lookup_name($obj_row->versionId)."</td></tr>\n";
         
        //dataDescription
        echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
        echo '<td><textarea name="sDescription" rows=10 cols=35>'.stripslashes($obj_row->description).'</textarea></td></tr>',"\n";
        
        //data
        if($obj_row->type == "image") 
        {
           $oScreenshot = new Screenshot($obj_row->id);
           echo '<tr valign=top><td class=color0><b>Submited image</b></td>',"\n";
           echo '<td>';
           $imgSRC = '<img width="'.$oScreenshot->oThumbnailImage->width.'" height="'.$oScreenshot->oThumbnailImage->height.'" src="../appimage.php?bQueued=true&iId='.$obj_row->id.'" />';
           // generate random tag for popup window
           $randName = User::generate_passwd(5);
           // set image link based on user pref
           $img = '<a href="javascript:openWin(\'../appimage.php?bQueued=true&iId='.$obj_row->id.'\',\''.$randName.'\','.$oScreenshot->oScreenshotImage->width.','.($oScreenshot->oScreenshotImage->height+4).');">'.$imgSRC.'</a>';
           if ($_SESSION['current']->isLoggedIn())
           {
               if ($_SESSION['current']->getpref("window:screenshot") == "no")
               { 
                   $img = '<a href="../appimage.php?bQueued=true&iId='.$obj_row->id.'">'.$imgSRC.'</a>';
               }
           }
           echo $img;
           echo '</td></tr>',"\n";
        } elseif($obj_row->type == "url")
        {
            echo '<tr valign=top><td class=color0><b>Submitted link</b></td>',"\n";
            echo '<td><textarea name="sContent" rows=10 cols=35>'.stripslashes($obj_row->url).'</textarea></td></tr>',"\n";
        }

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='sReplyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the submitted application data here</textarea></td></tr>\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sAdd value=" Add data to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sReject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '</table>',"\n";
        echo '<input type=hidden name="sSub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="iId" value="'.$aClean['iId'].'" />',"\n";  
        echo '</form>';
    } elseif ($aClean['sAdd']) // we accepted the request
    { 
        $statusMessage = "";
         $goodtogo = 0;
        
        if($obj_row->type == "image")
        { 
            $oScreenshot = new Screenshot($obj_row->id);
            $oScreenshot->unQueue();
        }
        elseif ($obj_row->type == "url")
        {
            $hResult = query_parameters("INSERT INTO appData (id, appId, versionId, type, ".
                                        "description, url) VALUES (?, '?', '?', '?', '?', '?')",
                                        "null", $obj_row->appId, $obj_row->versionId,
                                        "url", $aClean['sDescription'], $obj_row->url);
            if($hResult)
            {
                $statusMessage = "<p>The application data was successfully added into the database</p>\n";

                //delete the item from the queue
                query_parameters("DELETE from appData where id = '?'", $obj_row->id);
        
                //Send Status Email
                $oUser = new User($obj_row->userId);
                if ($oUser->sEmail)
                {
                    $sSubject =  "Application Data Request Report";
                    $sMsg  = "Your submission of an application data for ".Application::lookup_name($obj_row->appId).Version::lookup_name($obj_row->versionId)." has been accepted. ";
                    $sMsg .= $aClean['sReplyText'];
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\r\n";
                
                    mail_appdb($oUser->sEmail, $sSubject ,$sMsg);
                }
            }
        }
        redirect(apidb_fullurl("admin/adminAppDataQueue.php"));
    } elseif ($aClean['sReject'])
    {
        if($obj_row->type == "image")
        { 
            $oScreenshot = new Screenshot($obj_row->id);
            $oScreenshot->delete();
        }
        elseif ($obj_row->type == "url")
        { // FIXME: use Link class
            $oUser = new User($obj_row->userId);
            if ($oUser->sEmail)
            {
                $sSubject =  "Application Data Request Report";
                $sMsg  = "Your submission of an application data for ".Application::lookup_name($obj_row->appId).Version::lookup_name($obj_row->versionId)." was rejected. ";
                $sMsg .= $aClean['sReplyText'];
                mail_appdb($oUser->sEmail, $sSubject ,$sMsg); 
            }

            //delete main item
            if($_SESSION['current']->deleteAppData($obj_row->id))
            {
               //success
               echo "<p>Application data  was successfully deleted from the Queue.</p>\n";
            }
        }
        redirect(apidb_fullurl("admin/adminAppDataQueue.php"));
    }
}
echo html_frame_end("&nbsp;");        
echo html_back_link(1,'adminAppDataQueue.php');
apidb_footer();
?>
