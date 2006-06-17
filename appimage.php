<?php
/*************************/
/* code to show an image */
/*************************/

include("path.php");
require(BASE."include/"."incl.php");
require_once(BASE."include/"."screenshot.php");

$aClean = array(); //array of filtered user input

$aClean['id'] = makeSafe($_REQUEST['id']);
$aClean['REQUEST_METHOD'] = makeSafe($_REQUEST['REQUEST_METHOD']);
$aClean['thumbnail'] = makeSafe($_REQUEST['thumbnail']);

/* an image doesn't have a link, so a cookie makes no sense */
header("Set-Cookie: ");
header("Pragma: ");

/* if the user isn't supposed to be viewing this image */
/* display an error message and exit */
if(!$_SESSION['current']->canViewImage($aClean['id']))
{
    errorpage("Insufficient privileges.");
    exit;
}

if ($aClean['REQUEST_METHOD']='HEAD')
{
   /* WARNING! optimization of logic in include/screenshots.php */
   if (sscanf($aClean['id'],"%d", &$iId) < 1)
   {
      errorpage("Bad parameter");
      exit;
   }
   $sQuery = "SELECT id, url FROM appData 
              WHERE id = ".$iId."
              AND type = 'image' LIMIT 1";
   $hResult = query_appdb($sQuery);
   $fImage = 0;
   if($hResult)
   {
     $oRow = mysql_fetch_object($hResult);
     
     /* we need to use the url field from appData, this is the name of the file */
     /* in the filesystem */
     $fImage = fopen(appdb_fullpath("data/screenshots/".$oRow->url), "rb");
   }

   /* if the query failed or if we didn't find the image, we should */
   /* report a 404 to the browser */
   if(!$hResult || !$fImage)
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
$oScreenshot = new Screenshot($aClean['id']);

/* at this point, we know that .../screenshots/$id and
 *  .../screenshots/thumbnails/$id both exist as normally 
 *  they would both be created at the same time. */
$fstat_val = stat(appdb_fullpath("data/screenshots/".$aClean['id']));
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

if(!$aClean['thumbnail'])
    $oScreenshot->oScreenshotImage->output_to_browser(1);
else
    $oScreenshot->oThumbnailImage->output_to_browser(1);
?>
