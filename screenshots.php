<?php
/*******************************************************************/
/* this script expects appId and optionnaly versionId as arguments */
/* OR                                                              */
/* cmd and imageId                                                 */
/*******************************************************************/

/**
 * APPLICATION ENVIRONMENT
 */
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

if($_REQUEST['cmd'])
{
    if(havepriv("admin") || isMaintainer($_REQUEST['appId'], $_REQUEST['versionId']))
    {
    //process screenshot upload
    if($_REQUEST['cmd'] == "screenshot_upload")
    {    
        if(!copy($_FILES['imagefile']['tmp_name'], "data/screenshots/".$_REQUEST['appId']."-".$_REQUEST['versionId']."-".basename($_FILES['imagefile']['name'])))
        {
            // whoops, copy failed. do something
            errorpage("debug: copy failed; (".$_FILES['imagefile']['tmp_name'].";".$_FILES['imagefile']['name']);
            exit;
        }

        $query = "INSERT INTO appData VALUES (null, ".$_REQUEST['appId'].", ".$_REQUEST['versionId'].", 'image', ".
        "'".addslashes($_REQUEST['screenshot_desc'])."', '".$_REQUEST['appId']."-".$_REQUEST['versionId']."-".basename($_FILES['imagefile']['name'])."')";

        if(debugging()) addmsg("<p align=center><b>query:</b> $query </p>",green);
    
        if (mysql_query($query))
        {
            //success
            $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
            if($email)
            {
                 $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                 $ms .= APPDB_ROOT."screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                 $ms .= "\n";
                 $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." added screenshot ".$_REQUEST['screenshot_desc']." to ".$fullAppName."\n";
                 $ms .= "\n";
                 $ms .= STANDARD_NOTIFY_FOOTER;

                 mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
            } else
            {
                $email = "no one";
            }
            addmsg("mesage sent to: ".$email, green);

            addmsg("The image was successfully added into the database", "green");
            redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
        }
        else
        {
            //error
            $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
            addmsg($statusMessage, "red");
        }
    } else
    {
        if($_REQUEST['cmd'] == "delete")
        {
            $result = mysql_query("DELETE FROM appData WHERE id = ".$_REQUEST['imageId']);
            if($result)
                {
                    $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
                    if($email)
                    {
                        $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                        $ms .= APPDB_ROOT."screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                        $ms .= "\n";
                        $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." deleted screenshot from ".$fullAppName."\n";
                        $ms .= "\n";
                        $ms .= STANDARD_NOTIFY_FOOTER;
   
                        mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
 
                    } else
                    {
                        $email = "no one";
                    }
                    addmsg("mesage sent to: ".$email, green);

                    addmsg("Image deleted", "green");
                    redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
                } else
                {
                    addmsg("Failed to delete image: ".mysql_error(), "red");
                    redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
                }
            }
        } 
    }
    exit;
}

if($_REQUEST['versionId'])
    $result = mysql_query("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." AND versionId = ".$_REQUEST['versionId']);
else
    $result = mysql_query("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." ORDER BY versionId");
     
if((!$result || !mysql_num_rows($result)) && (!havepriv("admin") && !isMaintainer($_REQUEST['appId'], $_REQUEST['versionId']))) 
{
    errorpage("No Screenshots Found","There are no screenshots currently linked to this application.");
    exit;
} else
{
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
                }
                $currentVersionId=$ob->versionId;
                echo html_frame_start("Version ".lookupVersionName($_REQUEST['appId'], $currentVersionId));
                echo "<div align=center><table><tr>\n";
            }
            // set img tag
            $imgSRC = '<img src="appimage.php?imageId='.$ob->id.'&width=128&height=128" alt="'.$ob->description.'">';

            // get image size
            $size = getimagesize("data/screenshots/".$ob->url);

            // generate random tag for popup window
            $randName = generate_passwd(5);

            // set image link based on user pref
            $img = '<a href="javascript:openWin(\'appimage.php?imageId='.$ob->id.'\',\''.$randName.'\','.$size[0].','.$size[1].');">'.$imgSRC.'</a>';
            if (loggedin())
            {
                if ($_SESSION['current']->getpref("window:screenshot") == "no")
                {
                    $img = '<a href="appimage.php?imageId='.$ob->id.'">'.$imgSRC.'</a>';
                }
            }

            // display image
            echo "<td>\n";
            echo html_frame_start(substr(stripslashes($ob->description),0,20),128,"",0);
            echo $img;

            //show admin delete link
            if(loggedin() && (havepriv("admin") || isMaintainer($_REQUEST['appId'], $_REQUEST['versionId'])))
            {
                echo "<div align=center>[<a href='screenshots.php?cmd=delete&imageId=$ob->id&appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."'>Delete Image</a>]</div>";
            }

            echo html_frame_end("&nbsp;");
            echo "</td>\n";

            // end row if counter of 3
            if ($c % 3 == 0) echo "</tr><tr>\n";

            $c++;
        }
        echo "</tr></table></div><br>\n";

        echo html_frame_end("Click thumbnail to view image in new window.");
    }
    if((havepriv("admin") || isMaintainer($_REQUEST['appId'], $_REQUEST['versionId']))
       && $_REQUEST['versionId'])
    {
        //image upload box
        echo '<form enctype="multipart/form-data" action="screenshots.php" name=imageForm method="post">',"\n";
        echo html_frame_start("Upload Screenshot","400","",0);
        echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
      
        echo '<tr><td class=color1>Image</td><td class=color0><input name="imagefile" type="file"></td></tr>',"\n";
        echo '<tr><td class=color1>Description</td><td class=color0><input type="text" name="screenshot_desc"></td></tr>',"\n";
       
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

}

?>
