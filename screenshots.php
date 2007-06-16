<?php
/**
 * Shows a page with several screenshot thumbnails.
 *
 * Mandatory parameters:
 *  - iAppId, application identifier
 *            AND/OR
 *  - iVersionId, version identifier
 * 
 * Optional parameters:
 *  - iImageId, image identifier (for deletion)
 *  - sScreenshotDesc, screenshot description (for insertion)
 *  - sCmd, action to perform ("screenshot_upload", "delete")
 * 
 * TODO:
 *  - replace iImageId with iScreenshotId
 *  - replace sCmd with iAction and replace "delete", "screenshot_upload", etc. with integer constants DELETE, UPLOAD, etc.
 *  - replace require_once with require after checking that it won't break anything
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/screenshot.php");
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");

// we issued a command
if($aClean['sCmd'])
{
    // process screenshot upload
    if($aClean['sCmd'] == "screenshot_upload")
    {   
	//FIXME: use a defined value here instead of just 600000
        if($_FILES['sImageFile']['size']>600000)
        {
            addmsg("Your screenshot was not accepted because it is too big. Please try to keep your screenshots under 600KB by saving games/video screenshots to jpeg and normal applications to png you might be able to achieve very good results with less bytes", "red");
        } else
        {
            $oScreenshot = new Screenshot();
            $oScreenshot->iVersionId = $aClean['iVersionId'];
            $oScreenshot->sDescription = $aClean['sScreenshotDesc'];
            $oScreenshot->hFile = $_FILES['sImageFile'];
            $oScreenshot->create();
            $oScreenshot->free();
        }
    } elseif($aClean['sCmd'] == "delete" && is_numeric($aClean['iImageId'])) // process screenshot deletion
    {
        $oScreenshot = new Screenshot($aClean['iImageId']);
        $oScreenshot->delete();
        $oScreenshot->free();
    } 
    util_redirect_and_exit(apidb_fullurl("screenshots.php?iAppId=".$aClean['iAppId']."&iVersionId=".$aClean['iVersionId']));
}


// we didn't issued any command
$hResult = Screenshot::get_screenshots($aClean['iAppId'], $aClean['iVersionId']);   
apidb_header("Screenshots");
$oApp = new Application($aClean['iAppId']);
$oVersion = new Version($aClean['iVersionId']);

if($hResult && mysql_num_rows($hResult))
{
    echo html_frame_start("Screenshot Gallery for ".$oApp->sName." ".$oVersion->sName,500);

    // display thumbnails
    $c = 1;
    echo "<div align=center><table><tr>\n";
    while($oRow = mysql_fetch_object($hResult))
    {
        // if the current version changed then update the current version
        // and close the previous html frame if this isn't the
        // first frame
        if(!$aClean['iVersionId'] && $oRow->versionId != $currentVersionId)
        {
            if($currentVersionId)
            {
                echo "</tr></table></div>\n";
                echo html_frame_end();
                $c=1;
            }
            $currentVersionId = $oRow->versionId;
            echo html_frame_start("Version ".Version::lookup_name($currentVersionId));
            echo "<div align=center><table><tr>\n";
        }
        $oScreenshot = new Screenshot($oRow->id);
        $img = $oScreenshot->get_thumbnail_img();
        // display image
        echo "<td>\n";
        echo $img;
        echo "<div align=center>". substr($oRow->description,0,20). "\n";
        
        //show admin delete link
        if($_SESSION['current']->isLoggedIn() && ($_SESSION['current']->hasPriv("admin") || 
               $_SESSION['current']->isMaintainer($aClean['iVersionId'])))
        {
            echo "<br />[<a href='screenshots.php?sCmd=delete&iImageId=$oRow->id&iAppId=".$aClean['iAppId']."&iVersionId=".$aClean['iVersionId']."'>Delete Image</a>]";
        }

        echo "</div></td>\n";

        // end row if counter of 3
        if ($c % 3 == 0) echo "</tr><tr>\n";

        $c++;
    }
    echo "</tr></table></div><br />\n";

    echo html_frame_end(); // close the current version we are displaying
    echo html_frame_end(); // close the "Screenshot Gallary..." html frame
} else {
 echo "<p align=\"center\">There are currently no screenshots for the selected version of this application.";
 echo "<br />Please consider submitting a screenshot for the selected version yourself.</p>";
}

// let's show the screenshot uploading box
if($aClean['iVersionId'])
{
    echo "<p align=\"center\">When submitting screenshots please ensure that the focus is on the application running inside Wine.";
    echo "<br />This means if the application is running in a window then please crop the image so that only the application is shown and not your desktop.</p>";

    echo '<form enctype="multipart/form-data" action="screenshots.php" name="sImageForm" method="post">',"\n";
    echo html_frame_start("Upload Screenshot","400","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
      
    echo '<tr><td class=color1>Image</td><td class=color0><input name="sImageFile" type="file" size="24"></td></tr>',"\n";
    echo '<tr><td class="color1">Description</td><td class="color0"><input type="text" name="sScreenshotDesc" maxlength="20" size="24"></td></tr>',"\n";
       
    echo '<tr><td colspan=2 align=center class=color3><input type="submit" value="Send File"></td></tr>',"\n";
    echo '</table>',"\n";
    echo html_frame_end();
    echo '<input type="hidden" name="MAX_FILE_SIZE" value="4000000" />',"\n";
    echo '<input type="hidden" name="sCmd" value="screenshot_upload" />',"\n";
    echo '<input type="hidden" name="iVersionId" value="'.$aClean['iVersionId'].'"></form />',"\n";
}
echo html_back_link(1);

apidb_footer();
?>
