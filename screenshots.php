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


/*
 * We issued a command.
 */ 
if($_REQUEST['cmd'])
{
    // process screenshot upload
    if($_REQUEST['cmd'] == "screenshot_upload")
    {   
        $oScreenshot = new Screenshot();
        $oScreenshot->create($_REQUEST['versionId'], $_REQUEST['screenshot_desc'], $_FILES['imagefile']);
        $oScreenshot->free();
    } elseif($_REQUEST['cmd'] == "delete" && is_numeric($_REQUEST['imageId'])) // process screenshot deletion
    {
        $oScreenshot = new Screenshot($_REQUEST['imageId']);
        $oScreenshot->delete();
        $oScreenshot->free();
    } 
    redirect(apidb_fullurl("screenshots.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
}


/*
 * We didn't issued any command.
 */ 
$hResult = get_screenshots($_REQUEST['appId'], $_REQUEST['versionId']);   
apidb_header("Screenshots");
$oApp = new Application($_REQUEST['appId']);
$oVersion = new Version($_REQUEST['versionId']);

if($hResult && mysql_num_rows($hResult))
{
    echo html_frame_start("Screenshot Gallery for ".$oApp->sName." ".$oVersion->sName,500);

    // display thumbnails
    $c = 1;
    echo "<div align=center><table><tr>\n";
    while($oRow = mysql_fetch_object($hResult))
    {
        if(!$_REQUEST['versionId'] && $oRow->versionId != $currentVersionId)
        {
            if($currentVersionId)
            {
                echo "</tr></table></div>\n";
                echo html_frame_end();
                $c=1;
            }
            $currentVersionId=$oRow->versionId;
            echo html_frame_start("Version ".lookup_version_name($currentVersionId));
            echo "<div align=center><table><tr>\n";
        }
        $oScreenshot = new Screenshot($oRow->id);
        // generate random tag for popup window
        $randName = generate_passwd(5);
        // set img tag        
        $imgSRC = '<img src="appimage.php?thumbnail=true&id='.$oRow->id.'" alt="'.$oScreenshot->description.'" width="'.$oScreenshot->oThumnailImage->width.'" height="'.$oScreenshot->oThumnailImage->height.'">';

        // set image link based on user pref
        $img = '<a href="javascript:openWin(\'appimage.php?id='.$oRow->id.'\',\''.$randName.'\','.$oScreenshot->oScreenshotImage->width.','.($oScreenshot->oScreenshotImage->height+4).');">'.$imgSRC.'</a>';
        if ($_SESSION['current']->isLoggedIn())
        {
            if ($_SESSION['current']->getpref("window:screenshot") == "no")
            {
                $img = '<a href="appimage.php?imageId='.$oRow->id.'">'.$imgSRC.'</a>';
            }
        }

        // display image
        echo "<td>\n";
        echo $img;
        echo "<div align=center>". substr($oRow->description,0,20). "\n";
        
        //show admin delete link
        if($_SESSION['current']->isLoggedIn() && ($_SESSION['current']->hasPriv("admin") || 
               $_SESSION['current']->isMaintainer($_REQUEST['versionId'])))
        {
            echo "<br />[<a href='screenshots.php?cmd=delete&imageId=$oRow->id&appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."'>Delete Image</a>]";
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
    echo '<form enctype="multipart/form-data" action="screenshots.php" name="imageForm" method="post">',"\n";
    echo html_frame_start("Upload Screenshot","400","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
      
    echo '<tr><td class=color1>Image</td><td class=color0><input name="imagefile" type="file" size="24"></td></tr>',"\n";
    echo '<tr><td class="color1">Description</td><td class="color0"><input type="text" name="screenshot_desc" maxlength="20" size="24"></td></tr>',"\n";
       
    echo '<tr><td colspan=2 align=center class=color3><input type="submit" value="Send File"></td></tr>',"\n";
       
    echo '</table>',"\n";
    echo html_frame_end();
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />',"\n";
    echo '<input type="hidden" name="cmd" value="screenshot_upload" />',"\n";
    echo '<input type="hidden" name="versionId" value="'.$_REQUEST['versionId'].'"></form />',"\n";
}
echo html_back_link(1);
apidb_footer();
?>
