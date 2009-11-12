<?php

require_once('path.php');
require_once('include/incl.php');
require_once('include/maintainer.php');

if(!$_SESSION['current']->hasPriv('admin'))
    util_show_error_page_and_exit("Only admins are allowed in here");

function updateAppMaintainerStates()
{
    $hResult = application::objectGetEntries('accepted');

    $i = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        $oApp = new application(null, $oRow);
        $oApp->updateMaintainerState();
        $i++;
    }

    echo "Updated $i entries";
}

function updateVersionMaintainerStates()
{
    $hResult = version::objectGetEntries('accepted');

    $i = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        $oVersion = new version(null, $oRow);
        $oVersion->updateMaintainerState();
        $i++;
    }

    echo "Updated $i entries";
}

function showChoices()
{
    echo '<a href="updateMaintainerStates.php?sAction=updateAppMaintainerStates">Update application maintainer states</a><br />';
    echo '<a href="updateMaintainerStates.php?sAction=updateVersionMaintainerStates">Update version maintainer states</a>';
}

switch(getInput('sAction', $aClean))
{
    case 'updateAppMaintainerStates':
        updateAppMaintainerStates();
        break;

    case 'updateVersionMaintainerStates':
        updateVersionMaintainerStates();
        break;

    default:
        showChoices();
        break;
}


?>
