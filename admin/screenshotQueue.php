<?php
/************************************************/
/* code to show a queued image                  */
/************************************************/

include("path.php");
if($info=getimagesize("../data/queued/screenshots/".$_REQUEST['queueId']))
{
       header('Content-type: '.$info['mime']);
       $handle = fopen("../data/queued/screenshots/".$_REQUEST['queueId'], "rb");
       echo fread($handle, filesize("../data/queued/screenshots/".$_REQUEST['queueId']));
       fclose($handle);
}
?>
