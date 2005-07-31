<?php
/*************************/
/* code to show an image */
/*************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."screenshot.php");

/* an image doesn't have a link, so a cookie makes no sense */
header("Set-Cookie: ");
header("Pragma: ");

if(!$_SESSION['current']->hasPriv("admin") && $_REQUEST['queued'])
{
   errorpage("Insufficient privileges.");
   exit;
}
if ($_REQUEST['REQUEST_METHOD']='HEAD')
{
   /* WARNING! optimization of logic in include/screenshots.php */
   if (sscanf($_REQUEST['id'],"%d", &$iId) < 1)
   {
      errorpage("Bad parameter");
      exit;
   }
   $sQuery = "SELECT id FROM appData 
              WHERE id = ".$iId."
              AND type = 'image' LIMIT 1";
   if (!($hResult = query_appdb($sQuery) &&
         $fImage = fopen(appdb_fullpath("data/screenshots/".$iId), "rb")))
   {
      header("404 No such image");
      exit;
   }
   $fstat_val = fstat($fImage);
   $iModTime = $fstat_val['mtime'];
   $sMagic = fread($fImage,8);
   fclose($fImage); /* don't leave the fopened image open */
   /* identify what kind of image this is, if we can't identify it */
   /* we should report that its a bad image */
   if (strcmp("\x89PNG\r\n\x1A\n",$sMagic)==0) 
   {
      header("Content-Type: image/png");
   } else if (preg_match("^\xD8\xFF^",$sMagic)) {
      header("Content-Type: image/jpeg");
   } else {
      header("500 Bad image format");
      exit;
   }
   header("Cache-Control: public");
   header("Expires: ");
   header("Last-Modified: ".fHttpDate($iModTime));
}
$oScreenshot = new Screenshot($_REQUEST['id']);

/* at this point, we know that .../screenshots/$id and
 *  .../screenshots/thumbnails/$id both exist as normally 
 *  they would both be created at the same time. */
$fstat_val = stat(appdb_fullpath("data/screenshots/".$_REQUEST['id']));
$iModTime = $fstat_val['mtime'];

header("Cache-Control: public");
header("Expires: ");

/* if the browser is asking if the file was modified since a particular date */
/* and the date is the same that the file was modified, then we can report */
/* that the file wasn't modified, the browser can used the cached image */
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
    $iModTime == pHttpDate($_SERVER['HTTP_IF_MODIFIED_SINCE']))
{
   header("HTTP/1.0 304 Not Modified");
   exit;
}

header("Last-Modified: ".fHttpDate($iModTime));

if(!$_REQUEST['thumbnail'])
    $oScreenshot->oScreenshotImage->output_to_browser(1);
else
    $oScreenshot->oThumbnailImage->output_to_browser(1);

?>