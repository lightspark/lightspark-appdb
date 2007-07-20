<?php

/* common functions used in appdb unit tests */

function test_start($sFunctionName)
{
    echo $sFunctionName."() starting\n";
}

// create an application and a version of that application
// return the iVersionId of the created version
function create_version_and_parent_app()
{
    $oApp = new application();
    $oApp->sName = "OM App";
    $oApp->create();
    $oVersion = new version();
    $oVersion->sName = "OM version";
    $oVersion->iAppId = $oApp->iAppId;
    $oVersion->create();
    return $oVersion->iVersionId;
}

// delete a version based on the $iVersionId parameter
// and delete its parent application
function delete_version_and_parent_app($iVersionId)
{
    $oVersion = new version($iVersionId);
    $oApp = new application($oVersion->iAppId);
    $oApp->delete();
}

?>
