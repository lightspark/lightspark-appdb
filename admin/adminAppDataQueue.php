<?php
/********************************************************/
/* code to view and approve new application data        */
/********************************************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/mail.php");
require(BASE."include/screenshot.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");

// deny access if not admin
if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}


// shows the list of appdata in queue
if (!$_REQUEST['queueId'])
{

    apidb_header("Admin Application Data Queue");

    // get available appData
    $sQuery = "SELECT appDataQueue.*, appVersion.appId AS appId 
               FROM appDataQueue, appVersion 
               WHERE appVersion.versionId = appDataQueue.versionID;";
    $hResult = query_appdb($sQuery);

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
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Queue Id</font></td>\n";
        echo "    <td><font color=white>Name (e-mail)</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Type</font></td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($hResult))
        {   
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
            echo "    <td><a href='adminAppDataQueue.php?queueId=$ob->queueId'>".$ob->queueId."</a></td>\n";
            if($ob->userId)
            {
                $oUser = new User($ob->userId);
                echo "    <td>".$oUser->sRealname." (".$oUser->sEmail.")</td>\n";
            }
            else
            echo "    <td>Anonymous</td>\n";
            echo "<td>".lookup_app_name($ob->appId)."</td>\n";
            echo "<td>".lookup_version_name($ob->versionId)."</td>\n";
            echo "<td>".$ob->type."</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }      
} else // shows a particular appdata
{
    $sQuery = "SELECT appDataQueue.*, appVersion.appId AS appId 
               FROM appDataQueue,appVersion 
               WHERE appVersion.versionId = appDataQueue.versionId 
               AND queueId='".$_REQUEST['queueId']."'";
    $hResult = query_appdb($sQuery);
    $obj_row = mysql_fetch_object($hResult);
    
    if(!$_REQUEST['sub']=="inside_form")
    {       
        apidb_header("Admin Application Data Queue");

        echo '<form name="qform" action="adminAppDataQueue.php" method="post">',"\n";
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
        echo "<td>".lookup_app_name($obj_row->appId)."</td></tr>\n";

        // version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".lookup_version_name($obj_row->versionId)."</td></tr>\n";
         
        //dataDescription
        echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
        echo '<td><textarea name="description" rows=10 cols=35>'.stripslashes($obj_row->description).'</textarea></td></tr>',"\n";
        
        //data
        if($obj_row->type == "image") 
        {
           $oScreenshot = new Screenshot($obj_row->queueId,true);
           echo '<tr valign=top><td class=color0><b>Submited image</b></td>',"\n";
           echo '<td>';
           $imgSRC = '<img width="'.$oScreenshot->oThumbnailImage->width.'" height="'.$oScreenshot->oThumbnailImage->height.'" src="../appimage.php?queued=true&id='.$obj_row->queueId.'" />';
           // generate random tag for popup window
           $randName = generate_passwd(5);
           // set image link based on user pref
           $img = '<a href="javascript:openWin(\'../appimage.php?queued=true&id='.$obj_row->queueId.'\',\''.$randName.'\','.$oScreenshot->oScreenshotImage->width.','.($oScreenshot->oScreenshotImage->height+4).');">'.$imgSRC.'</a>';
           if ($_SESSION['current']->isLoggedIn())
           {
               if ($_SESSION['current']->getpref("window:screenshot") == "no")
               { 
                   $img = '<a href="../appimage.php?queued=true&id='.$obj_row->queueId.'">'.$imgSRC.'</a>';
               }
           }
           echo $img;
           echo '</td></tr>',"\n";
        } elseif($obj_row->type == "url")
        {
            echo '<tr valign=top><td class=color0><b>Submitted link</b></td>',"\n";
            echo '<td><textarea name="content" rows=10 cols=35>'.stripslashes($obj_row->url).'</textarea></td></tr>',"\n";
        }

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='replyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the submitted application data here</textarea></td></tr>\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=add value=" Add data to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=reject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '</table>',"\n";
        echo '<input type=hidden name="sub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="queueId" value="'.$_REQUEST['queueId'].'" />',"\n";  
        echo '</form>';
    } elseif ($_REQUEST['add']) // we accepted the request
    { 
        $statusMessage = "";
         $goodtogo = 0;
        
        if($obj_row->type == "image")
        { 
            $oScreenshot = new Screenshot($obj_row->queueId,true);
            $oScreenshot->unQueue();
        }
        elseif ($obj_row->type == "url")
        { // FIXME: use Link class
            $query = "INSERT INTO appData VALUES (null, ".$obj_row->versionId.", 'url', ".
                     "'".addslashes($_REQUEST['description'])."', '".$obj_row->url."')";
            if (query_appdb($sQuery))
            {
                $statusMessage = "<p>The application data was successfully added into the database</p>\n";

                //delete the item from the queue
                query_appdb("DELETE from appDataQueue where queueId = ".$obj_row->queueId.";");
        
                //Send Status Email
                $oUser = new User($obj_row->userId);
                if ($oUser->sEmail)
                {
                    $sSubject =  "Application Data Request Report";
                    $sMsg  = "Your submission of an application data for ".lookup_app_name($obj_row->appId).lookup_version_name($obj_row->versionId)." has been accepted. ";
                    $sMsg .= $_REQUEST['replyText'];
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\r\n";
                
                    mail_appdb($oUser->sEmail, $sSubject ,$sMsg);
                }
            }
        }
        redirect(apidb_fullurl("admin/adminAppDataQueue.php"));
    } elseif ($_REQUEST['reject'])
    {
        if($obj_row->type == "image")
        { 
            $oScreenshot = new Screenshot($obj_row->queueId,true);
            $oScreenshot->delete();
        }
        elseif ($obj_row->type == "url")
        { // FIXME: use Link class
            $oUser = new User($obj_row->userId);
            if ($oUser->sEmail)
            {
                $sSubject =  "Application Data Request Report";
                $sMsg  = "Your submission of an application data for ".lookup_app_name($obj_row->appId).lookup_version_name($obj_row->versionId)." was rejected. ";
                $sMsg .= $_REQUEST['replyText'];
                mail_appdb($oUser->sEmail, $sSubject ,$sMsg); 
            }

            //delete main item
            $sQuery = "DELETE from appDataQueue where queueId = ".$obj_row->queueId.";";
            $hResult = query_appdb($sQuery);
            if($hResult)
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
