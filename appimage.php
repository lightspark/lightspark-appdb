<?php
/*************************************************************/
/* app image handler                                         */
/*                                                           */
/* valid arguments:                                          */
/*                                                           */
/*   appId          (required)                               */
/*   versionId                                               */
/*                                                           */
/*   imageId        (no appId required if this is specified) */
/*                                                           */
/*   width                                                   */
/*   height                                                  */
/*                                                           */
/* When both width/height are specified, the image is scaled */
/*************************************************************/

include("path.php");
require(BASE."include/"."incl.php");


function handle_error($text)
{
    echo $text;
    // output image with the text, or something
    exit;
}

$appId = $_GET['appid'];
$imageId = $_GET['imageId'];
$versionId = $_GET['versionId'];
$width = $_GET['width'];
$height = $_GET['height'];

if(!$versionId) {
    $versionId = 0;
}

// We have input, but wrong input
if( ( $width AND !is_numeric($width) ) || ( $height AND !is_numeric($height) ) )
{
    $width = 100;
    $height = 75;
}

if($imageId AND is_numeric($imageId) )
    $result = query_appdb("SELECT * FROM appData WHERE id = $imageId");

else if($appId AND $versionId AND is_numeric($appId) AND is_numeric($versionId) )
    $result = query_appdb("SELECT * FROM appData WHERE appId = $appId AND ".
                          "versionId = $versionId AND type = 'image' LIMIT 1");
else
	handle_error("IDs wrong");

if(mysql_num_rows($result) == 0)
    handle_error("No image found");

$ob = mysql_fetch_object($result);

// atm assumes the image is in png format

if(!ereg("/", $ob->url))
    $url = "data/screenshots/$ob->url";
else
    $url = $ob->url;

$imageInfo = getimagesize($url);

if( $imageInfo[2] == 2 )
{
	$type = 'jpeg';
	$im = imagecreatefromjpeg($url);
}
else if( $imageInfo[2] == 3 )
{
	$type = 'png';
	$im = imagecreatefrompng($url);
}
else
	handle_error("Unhandeled image type");

if( !$imageInfo || !$im)
    handle_error("Error handeling file.");

if($width && $height)
{
    // do scaling
    $sim = ImageCreate($width, $height);
    ImageCopyResized($sim, $im, 0, 0, 0, 0, $width, $height, ImageSX($im), ImageSY($im));
}
else
{
	// display full image
	$sim = $im;
}

// output the image
if($type == "png")
{
    header("Content-type: image/png");
    ImagePNG($sim);
}
else
if($type == "jpeg")
{
    header("Content-type: image/jpeg");
    ImageJPEG($sim);
}

// Clear the memory
imagedestroy($im);
if(is_resource($sim))imagedestroy($sim);
?>
