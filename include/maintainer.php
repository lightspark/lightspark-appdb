<?php
/*****************************/
/* functions for maintainers */
/*****************************/

/**
 * get the applications and versions that this userId maintains 
 */
function getAppsFromUserId($userId)
{
    /* retrieve the list of application and order them by application name */
    $hResult = query_appdb("SELECT appMaintainers.appId, versionId, superMaintainer, appName FROM ".
                          "appFamily, appMaintainers WHERE appFamily.appId = appMaintainers.appId ".
                          "AND userId = '$userId' ORDER BY appName");
    if(!$hResult || mysql_num_rows($hResult) == 0)
        return;

    $retval = array();
    $c = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        $retval[$c] = array($oRow->appId, $oRow->versionId, $oRow->superMaintainer);
        $c++;
    }

    return $retval;
}

/*
 * get the userIds of maintainers for a versionId
 */
function getMaintainersUserIdsFromAppIdVersionId($versionId)
{
    $retval = array();

    /* early out if the versionId isn't valid */
    if($versionId == 0)
        return $retval;
    
    $sQuery = "SELECT userId FROM ".
                          "appMaintainers WHERE versionId = '$versionId';";
    $hResult = query_appdb($sQuery);
    $c = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        $retval[$c] = $oRow->userId;
        $c++;
    }

    return $retval;
}

/*
 * get the userIds of super maintainers for this appId
 */
function getSuperMaintainersUserIdsFromAppId($appId)
{
    $sQuery = "SELECT userId FROM ".
                          "appMaintainers WHERE appId = '$appId' " .
                          "AND superMaintainer = '1';";
    $hResult = query_appdb($sQuery);
    $retval = array();
    $c = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        $retval[$c] = $oRow->userId;
        $c++;
    }

    return $retval;
}

?>
