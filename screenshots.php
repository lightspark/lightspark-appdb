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
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

if($_REQUEST['cmd'])
{
    //process screenshot upload
    if($_REQUEST['cmd'] == "screenshot_upload")
    {   
        if(havepriv("admin") || 
            (loggedin() && $_SESSION['current']->is_maintainer($_REQUEST['appId'], 
                                                $_REQUEST['versionId'])))
        {    
            $str_query = "INSERT INTO appData VALUES (null, ".$_REQUEST['appId'].", ".$_REQUEST['versionId'].
                         ", 'image', '".addslashes($_REQUEST['screenshot_desc'])."', '')";

            if(debugging()) addmsg("<p align=center><b>query:</b> $str_query </p>","green");
    
            if (mysql_query($str_query))
            {
                $int_id = mysql_insert_id();
    
                if(!copy($_FILES['imagefile']['tmp_name'], "data/screenshots/".$int_id))
                {
                    // whoops, copy failed. do something
                    errorpage("debug: copy failed; (".$_FILES['imagefile']['tmp_name'].";".$_FILES['imagefile']['name']);
                    $str_query = "DELETE FROM appData WHERE id = '".$int_id."'";
                    mysql_query($str_query);
                    exit;
                } else 
                {   
                    // we have to update the entry now that we know it's name
                    $str_query = "UPDATE appData SET url = '".$int_id."' WHERE id = '".$int_id."'";
                    if (mysql_query($str_query))
                    {
                        //success
                        $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
                        if($email)
                        {
                            $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                            $ms .= APPDB_ROOT."screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
                            $ms .= "\n";
                            $ms .= $_SESSION['current']->username." added screenshot ".$_REQUEST['screenshot_desc']." to ".$fullAppName."\n";
                            $ms .= "\n";
                            $ms .= STANDARD_NOTIFY_FOOTER;
 
                            mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
                        } else
                        {
                            $email = "no one";
                        }
                        addmsg("mesage sent to: ".$email, "green");

                        addmsg("The image was successfully added into the database", "green");
                        redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
                    }
                    else
                    {
                        //error
                        $statusMessage = "<p><b>Database Error!<br />".mysql_error()."</b></p>\n";
                        addmsg($statusMessage, "red");
                    }
                }
            }
        } else // we are a normal user or an anonymous and submitted a screenshot
        {   
            $str_query = "INSERT INTO appDataQueue VALUES (null, ".$_REQUEST['appId'].", ".$_REQUEST['versionId'].
                         ", 'image', '".addslashes($_REQUEST['screenshot_desc'])."', '','".$_SESSION['current']->userid.
                         "', NOW())";

            if(debugging()) addmsg("<p align=center><b>query:</b> $str_query </p>","green");
    
            if (mysql_query($str_query))
            {
                $int_queueId = mysql_insert_id();
    
                if(!copy($_FILES['imagefile']['tmp_name'], "data/queued/screenshots/".$int_queueId))
                {
                    // whoops, copy failed. do something
                    errorpage("debug: copy failed; (".$_FILES['imagefile']['tmp_name'].";".$_FILES['imagefile']['name']);
                    $str_query = "DELETE FROM appDataQueue WHERE queueId = '".$int_queueId."'";
                    mysql_query($str_query);
                    exit;
                } else 
                {   
                    // we have to update the queued entry now that we know its name
                    $str_query = "UPDATE appDataQueue SET url = '".$int_queueId."' WHERE queueId = '".$int_queueId."'";
                    if (mysql_query($str_query))
                    {
                        //success
                        $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
                        if($email)
                        {
                            $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
                            $ms .= APPDB_ROOT."admin/adminAppDataQueue.php?queueId=".mysql_insert_id()."\n";
                            $ms .= "\n";
                            $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "an anonymous user")." submitted a screenshot ".$_REQUEST['screenshot_desc']." for ".$fullAppName."\n";
                            $ms .= "\n";
                            $ms .= STANDARD_NOTIFY_FOOTER;
 
                            mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
                        } else
                        {
                            $email = "no one";
                        }
                        addmsg("mesage sent to: ".$email, "green");

                        addmsg("The image you submitted will be added to the database database after being reviewed", "green");
                        redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
                    }
                    else
                    {
                        //error
                        $statusMessage = "<p><b>Database Error!<br />".mysql_error()."</b></p>\n";
                        addmsg($statusMessage, "red");
                    }
                }
            }
        }
    } elseif($_REQUEST['cmd'] == "delete")
    {
        if(havepriv("admin") ||
              $_SESSION['current']->is_maintainer($_REQUEST['appId'], 
                                                  $_REQUEST['versionId']))
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
                addmsg("mesage sent to: ".$email, "green");
                addmsg("Image deleted", "green");
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            } else
            {
                addmsg("Failed to delete image: ".mysql_error(), "red");
                redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
            }
        }
    } 
    exit;
}

// we didn't issue any command
if($_REQUEST['versionId'])
    $result = mysql_query("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." AND versionId = ".$_REQUEST['versionId']);
else
    $result = mysql_query("SELECT * FROM appData WHERE type = 'image' AND appId = ".$_REQUEST['appId']." ORDER BY versionId");
     
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
        // set img tag
        $imgSRC = '<img src="appimage.php?imageId='.$ob->id.'&width=128&height=128" border=0 alt="'.$ob->description.'">';
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
        if(loggedin() && (havepriv("admin") || 
               $_SESSION['current']->is_maintainer($_REQUEST['appId'],
                                                   $_REQUEST['versionId'])))
        {
            echo "<div align=center>[<a href='screenshots.php?cmd=delete&imageId=$ob->id&appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."'>Delete Image</a>]</div>";
        }

        echo html_frame_end("&nbsp;");
        echo "</td>\n";

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

?>
