<?php
/*****************************/
/* functions for maintainers */
/*****************************/

/**
 * get the applications and versions that this userId maintains 
 */
function getAppsFromUserId($userId)
{
    $result = query_appdb("SELECT appId, versionId, superMaintainer FROM ".
                          "appMaintainers WHERE userId = '$userId'");
    if(!$result || mysql_num_rows($result) == 0)
        return;

    $retval = array();
    $c = 0;
    while($row = mysql_fetch_object($result))
    {
        $retval[$c] = array($row->appId, $row->versionId, $row->superMaintainer);
        $c++;
    }

    return $retval;
}

/*
 * get the userIds of maintainers for this appId and versionId
 */
function getMaintainersUserIdsFromAppIdVersionId($versionId)
{
    $query = "SELECT userId FROM ".
                          "appMaintainers WHERE versionId = '$versionId';";
    $result = query_appdb($query);
    $retval = array();
    $c = 0;
    while($row = mysql_fetch_object($result))
    {
        $retval[$c] = $row->userId;
        $c++;
    }

    return $retval;
}

/*
 * get the userIds of super maintainers for this appId
 */
function getSuperMaintainersUserIdsFromAppId($appId)
{
    $query = "SELECT userId FROM ".
                          "appMaintainers WHERE appId = '$appId' " .
                          "AND superMaintainer = '1';";
    $result = query_appdb($query);
    $retval = array();
    $c = 0;
    while($row = mysql_fetch_object($result))
    {
        $retval[$c] = $row->userId;
        $c++;
    }

    return $retval;
}

?>
