<?php
/*****************************/
/* functions for maintainers */
/*****************************/

/*
 * get the userIds of super maintainers for this appId
 */
function getSuperMaintainersUserIdsFromAppId($iAppId)
{
    $sQuery = "SELECT userId FROM ".
                          "appMaintainers WHERE appId = '?' " .
                          "AND superMaintainer = '1' AND queued='?';";
    $hResult = query_parameters($sQuery, $iAppId, "false");
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
