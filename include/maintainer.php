<?

/*
 * get the applications and versions that this userId maintains
 */
function getAppsFromUserId($userId)
{
    $result = mysql_query("SELECT appId, versionId FROM ".
                          "appMaintainers WHERE userId = '$userId'");
    if(mysql_num_rows($result) == 0)
        return;

    $retval = array();
    $c = 0;
    while($row = mysql_fetch_object($result))
    {
        $retval[$c] = array($row->appId, $row->versionId);
        $c++;
    }

    return $retval;
}

/*
 * get the userIds of maintainers for this appId and versionId
 */
function getMaintainersUserIdsFromAppIdVersionId($appId, $versionId)
{
    $query = "SELECT userId FROM ".
                          "appMaintainers WHERE appId = '$appId' " .
                          "AND versionId = '$versionId';";
    $result = mysql_query($query);
    if(mysql_num_rows($result) == 0)
        return; // no sub categories

    $retval = array();
    $c = 0;
    while($row = mysql_fetch_object($result))
    {
        $retval[$c] = array($row->userId);
        $c++;
    }

    return $retval;
}

?>