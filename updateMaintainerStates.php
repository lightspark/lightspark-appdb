<?php

require_once('path.php');
require_once('include/incl.php');
require_once('include/maintainer.php');

if(!$_SESSION['current']->hasPriv('admin'))
    util_show_error_page_and_exit("Only admins are allowed in here");

$hResult = application::objectGetEntries('accepted');

$i = 0;
while($oRow = mysql_fetch_object($hResult))
{
    $oApp = new application(null, $oRow);
    $oApp->updateMaintainerState();
    $i++;
}

echo "Updated $i entries";

?>
