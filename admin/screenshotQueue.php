<?php
/************************************************/
/* code to show a queued image                  */
/************************************************/

include("path.php");
require(BASE."include/"."incl.php");
if(!havepriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}
if($info=getimagesize("../data/queued/screenshots/".$_REQUEST['queueId']))
{
       header('Content-type: '.$info['mime']);
       $handle = fopen("../data/queued/screenshots/".$_REQUEST['queueId'], "rb");
       echo fread($handle, filesize("../data/queued/screenshots/".$_REQUEST['queueId']));
       fclose($handle);
}
?>
