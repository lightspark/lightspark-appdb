<?php
/*****************************/
/* functions for maintainers */
/*****************************/

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
        "appMaintainers WHERE versionId = '?';";
    $hResult = query_parameters($sQuery, $versionId);
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
                          "appMaintainers WHERE appId = '?' " .
                          "AND superMaintainer = '1';";
    $hResult = query_parameters($sQuery, $appId);
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
