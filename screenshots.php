<?php
/*******************************************************************/
/* this script expects appId and optionally versionId as arguments */
/* OR                                                              */
/* cmd and imageId                                                 */
/*******************************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/screenshot.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");


if($_REQUEST['cmd'])
{
    //process screenshot upload
    if($_REQUEST['cmd'] == "screenshot_upload")
    {   
        if($_SESSION['current']->hasPriv("admin") || 
            ($_SESSION['current']->isLoggedIn() && $_SESSION['current']->isMaintainer($_REQUEST['appId'], 
                                                $_REQUEST['versionId'])))
        {    
            $oScreenshot = new Screenshot(null,false,$_SESSION['current']->iUserId,$_REQUEST['appId'],$_REQUEST['versionId'],$_REQUEST['screenshot_desc'],$_FILES['imagefile']);
            if($oScreenshot)
            {
                //success
                $sEmail = get_notify_email_address_list($_REQUEST['appId'], $_REQUEST['versionId']);
                if($sEmail)
                {
                    $sFullAppName = "Screenshot added to ".lookupAppName($_REQUEST['appId'])." ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                    $sMsg  = APPDB_ROOT."screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                    $sMsg .= "\n";
                    $sMsg .= $_SESSION['current']->sRealname." added screenshot ".$_REQUEST['screenshot_desc']." to ".$sFullAppName."\n";
          
                    mail_appdb($sEmail, $sFullAppName ,$sMsg);
                }
                addmsg("The image was successfully added into the database", "green");
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            }
        } else // we are a normal user or an anonymous and submitted a screenshot
        {   
            $oScreenshot = new Screenshot(null,true,$_SESSION['current']->userid,$_REQUEST['appId'],$_REQUEST['versionId'],$_REQUEST['screenshot_desc'],$_FILES['imagefile']);
            if($oScreenshot)
            {
                //success
                $sEmail = get_notify_email_address_list($_REQUEST['appId'], $_REQUEST['versionId']);
                if($sEmail)
                {
                    $sFullAppName = "Screenshot queued for ".lookupAppName($_REQUEST['appId'])." ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                    $sMsg  = APPDB_ROOT."admin/adminAppDataQueue.php?queueId=".mysql_insert_id()."\n";
                    $sMsg .= "\n";
                    $sMsg .= ($_SESSION['current']->sRealname ? $_SESSION['current']->sRealname : "an anonymous user")." submitted a screenshot ".$_REQUEST['screenshot_desc']." for ".$sFullAppName."\n";

                    mail_appdb($sEmail, $sFullAppName ,$sMsg);
                } 
                addmsg("The image you submitted will be added to the database database after being reviewed", "green");
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            }
        }
        $oScreenshot->free();
    } elseif($_REQUEST['cmd'] == "delete" && is_numeric($_REQUEST['imageId']))
    {
        if($_SESSION['current']->hasPriv("admin") ||
              $_SESSION['current']->isMaintainer($_REQUEST['appId'], 
                                                  $_REQUEST['versionId']))
        {     
            $oScreenshot = new Screenshot($_REQUEST['imageId']);         
            if($oScreenshot && $oScreenshot->delete())
            {
                $sEmail = get_notify_email_address_list($_REQUEST['appId'], $_REQUEST['versionId']);
                if($sEmail)
                {
                    $sFullAppName = "Screenshot deleted from ".lookupAppName($_REQUEST['appId'])." ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                    $sMsg  = APPDB_ROOT."screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                    $sMsg .= "\n";
                    $sMsg .= ($_SESSION['current']->sRealname ? $_SESSION['current']->sRealname : "Anonymous")." deleted screenshot from ".$sFullAppName."\r\n";
   
                    mail_appdb($sEmail, $sFullAppName ,$sMsg);
                }
                addmsg("Image deleted", "green");
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            } else
            {
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            }
        }
    } 
    $oScreenshot->free();
    exit;
}

// we didn't issue any command
if($_REQUEST['versionId'])
    $result = query_appdb("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." AND versionId = ".$_REQUEST['versionId']);
else
    $result = query_appdb("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." ORDER BY versionId");
     
$app=new Application($_REQUEST['appId']);
apidb_header("Screenshots");
if($result && mysql_num_rows($result))
{
    echo html_frame_start("Screenshot Gallery for ".$app->data->appName,500);

    // display thumbnails
    $c = 1;
    echo "<div align=center><table><tr>\n";
    while($ob = mysql_fetch_object($result))
    {
        if(!$_REQUEST['versionId'] && $ob->versionId!=$currentVersionId)
        {
            if($currentVersionId)
            {
                echo "</tr></table></div>\n";
                echo html_frame_end();
                $c=1;
            }
            $currentVersionId=$ob->versionId;
            echo html_frame_start("Version ".lookupVersionName($_REQUEST['appId'], $currentVersionId));
            echo "<div align=center><table><tr>\n";
        }
        $oScreenshot = new Screenshot($ob->id);
        // generate random tag for popup window
        $randName = generate_passwd(5);
        // set img tag        
        $imgSRC = '<img src="appimage.php?thumbnail=true&id='.$ob->id.'" alt="'.$oScreenshot->description.'" width="'.$oScreenshot->oThumnailImage->width.'" height="'.$oScreenshot->oThumnailImage->height.'">';

        // set image link based on user pref
        $img = '<a href="javascript:openWin(\'appimage.php?id='.$ob->id.'\',\''.$randName.'\','.$oScreenshot->oScreenshotImage->width.','.($oScreenshot->oScreenshotImage->height+4).');">'.$imgSRC.'</a>';
        if ($_SESSION['current']->isLoggedIn())
        {
            if ($_SESSION['current']->getpref("window:screenshot") == "no")
            {
                $img = '<a href="appimage.php?imageId='.$ob->id.'">'.$imgSRC.'</a>';
            }
        }

        // display image
        echo "<td>\n";
        echo $img;
        echo "<div align=center>". substr(stripslashes($ob->description),0,20). "\n";
        
        //show admin delete link
        if($_SESSION['current']->isLoggedIn() && ($_SESSION['current']->hasPriv("admin") || 
               $_SESSION['current']->isMaintainer($_REQUEST['appId'],
                                                   $_REQUEST['versionId'])))
        {
            echo "<br />[<a href='screenshots.php?cmd=delete&imageId=$ob->id&appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."'>Delete Image</a>]";
        }

        echo "</div></td>\n";

        // end row if counter of 3
        if ($c % 3 == 0) echo "</tr><tr>\n";

        $c++;
    }
    echo "</tr></table></div><br />\n";

    echo html_frame_end("Click thumbnail to view image in new window.");
} else {
 echo "<p align=\"center\">There are currently no screenshot for the selected version of this application.";
 echo "<br />Please consider submitting a screenshot for the selected version yourself.</p>";
}

if($_REQUEST['versionId'])
{
    //image upload box
    echo '<form enctype="multipart/form-data" action="screenshots.php" name=imageForm method="post">',"\n";
    echo html_frame_start("Upload Screenshot","400","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
      
    echo '<tr><td class=color1>Image</td><td class=color0><input name="imagefile" type="file" size="24"></td></tr>',"\n";
    echo '<tr><td class="color1">Description</td><td class="color0"><input type="text" name="screenshot_desc" maxlength="20" size="24"></td></tr>',"\n";
       
    echo '<tr><td colspan=2 align=center class=color3><input type="submit" value="Send File"></td></tr>',"\n";
       
    echo '</table>',"\n";
    echo html_frame_end();
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />',"\n";
    echo '<input type="hidden" name="cmd" value="screenshot_upload" />',"\n";
    echo '<input type="hidden" name="appId" value="'.$_REQUEST['appId'].'" />',"\n";
    echo '<input type="hidden" name="versionId" value="'.$_REQUEST['versionId'].'"></form />',"\n";
}
echo html_back_link(1);
apidb_footer();
?>
