<?php
/************************************************/
/* code to show an image stored in mysql        */
/************************************************/

include("path.php");
require(BASE."include/"."incl.php");
if($info=getimagesize("../data/screenshots/".$_REQUEST['file']))
{
       header('Content-type: '.$info['mime']);
       $handle = fopen("../data/screenshots/".$_REQUEST['file'], "rb");
       echo fread($handle, filesize("../data/screenshots/".$_REQUEST['file']));
       fclose($handle);
}
unlink("../data/screenshots/".$_REQUEST['file']);
?>
