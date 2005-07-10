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
   if (sscanf($_REQUEST['id'],"%d", &$iId) < 1) {
      errorpage("Bad parameter");
      exit;
   }
   $sQuery = "SELECT id FROM appData 
              WHERE id = ".$iId."
              AND type = 'image' LIMIT 1";
   if (!($hResult = query_appdb($sQuery) &&
         $fImage = fopen($_SERVER['DOCUMENT_ROOT']."/data/screenshots/".$iId, "rb")))
   {
      header("404 No such image");
      exit;
   }
   $fstat_val = fstat($fImage);
   $iModTime = $fstat_val['mtime'];
   $sMagic = fread($fImage,8);
   fclose($fImage);
   if (strcmp("\x89PNG\r\n\x1A\n",$sMagic)==0) 
   {
      header("Content-Type: image/png");
   } else if (preg_match("^\xFF\xD8..(JFIF|Exif)",$sMagic)) {
      header("Content-Type: image/jpeg");
   } else {
      header("500 Bad image format");
      exit;
   }            
   header("Cache-Control: public");
   header("Expires: ");
   header("Last-Modified: ".fHttpDate($iModTime));
   exit;
}
$oScreenshot = new screenshot($_REQUEST['id'],$_REQUEST['queued']);

	     /* at this point, we know that .../screenshots/$id and
	      *  .../screenshots/thumbnails/$id both exist;  normally 
	      *  they would both be created at the same time. */
$fstat_val = stat($_SERVER['DOCUMENT_ROOT']
                  ."/data/screenshots/".$_REQUEST['id']);
$iModTime = $fstat_val['mtime'];

header("Cache-Control: public");
header("Expires: ");

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && 
    $iModTime == pHttpDate($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
   header("HTTP/1.0 304 Not Modified");
   exit;
}

header("Last-Modified: ".fHttpDate($iModTime));

if(!$_REQUEST['thumbnail'])
    $oScreenshot->oScreenshotImage->output_to_browser(1);
else
    $oScreenshot->oThumbnailImage->output_to_browser(1);

?>