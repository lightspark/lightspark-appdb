<?

include("path.php");
require(BASE."include/"."incl.php");

/*
 * app image handler
 *
 * valid arguments:
 *
 *   appId          (required)
 *   versionId
 *
 *   imageId        (no appId required if this is specified)
 *
 *   width
 *   height
 *
 * When both width/height are specified, the image is scaled
 */

function handle_error($text)
{
    echo $text;
    // output image with the text, or something
    exit;
}

if(!$appId && !$imageId) {
    handle_error("No appId");
    return;
}

if(!$versionId) {
    $versionId = 0;
}

opendb();

if($imageId)
    $result = mysql_query("SELECT * FROM appData WHERE id = $imageId");
else
    $result = mysql_query("SELECT * FROM appData WHERE appId = $appId AND ".
			  "versionId = $versionId AND type = 'image' LIMIT 1");

if(mysql_num_rows($result) == 0)
    handle_error("No image found");

$ob = mysql_fetch_object($result);

// atm assumes the image is in png format

if(!ereg("/", $ob->url))
    $url = "data/screenshots/$ob->url";
else
    $url = $ob->url;

if(eregi(".+\\.(jpg|jpeg)$", $url))
    $type = "jpeg";
else
if(eregi(".+\\.(png)$", $url))
    $type = "png";

if(!$type)
    handle_error("Unknown Image Type");

if($type == "png")
    $im = ImageCreateFromPNG($url);
else
if($type == "jpeg")
    $im = ImageCreateFromJpeg($url);

if(!$im)
    handle_error("Error");

if($width && $height) {
    // do scaling
    $sim = ImageCreate($width, $height);
    ImageCopyResized($sim, $im, 0, 0, 0, 0, $width, $height, ImageSX($im), ImageSY($im));
    $im = $sim;
}

// output the image
if($type == "png")
{
    header("Content-type: image/png");
    ImagePNG($im);
}
else
if($type == "jpeg")
{
    header("Content-type: image/jpeg");
    ImageJPEG($im);
}

?>
