<?php
/*************************/
/* code to show an image */
/*************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."screenshot.php");
if(!$_SESSION['current']->hasPriv("admin") && $_REQUEST['queued'])
{
    errorpage("Insufficient privileges.");
    exit;
}
$oScreenshot = new screenshot($_REQUEST['id'],$_REQUEST['queued']);
if(!$_REQUEST['thumbnail'])
    $oScreenshot->oScreenshotImage->output_to_browser(1);
else
    $oScreenshot->oThumbnailImage->output_to_browser(1);
?>
