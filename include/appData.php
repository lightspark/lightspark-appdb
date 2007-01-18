<?php

/**
 * Functions related to application data
 */

require_once(BASE."include/util.php");

class appData
{
    function listSubmittedBy($iUserId, $bQueued = true)
    {
        $hResult = query_parameters("SELECT appData.TYPE, appData.appId,
            appData.versionId, appData.description, appData.submitTime,
            appFamily.appName, appVersion.versionName FROM appData,
            appFamily, appVersion
                WHERE (appFamily.appId = appData.appId OR
                (appData.versionId = appVersion.versionId AND appFamily.appId =
                appVersion.appId)) AND (appFamily.queued = '?' OR
                appVersion.queued = '?') AND appData.submitterId = '?' AND
                appData.queued = '?'
                    ORDER BY appData.id",
                        "false", "false", $iUserId, $bQueued ? "true" : "false");

        if(!$hResult || !mysql_num_rows($hResult))
            return false;

        $sReturn .= html_table_begin("width=\"100%\" align=\"center\"");
        $sReturn .= html_tr(array(
            "Version",
            "Type",
            "Description",
            "Submission Date"),
            "color4");

        for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
        {
            $sReturn .= html_tr(array(
                $oRow->versionId ?
                "<a href=\"".BASE."appview.php?iVersionId=$oRow->versionId\">".
                "$oRow->appName: $oRow->versionName</a>" :
                "<a href=\"".BASE."appview.php?iAppId=$oRow->appId\">".
                "$oRow->appName</a>",
                $oRow->TYPE,
                $oRow->description,
                print_date(mysqltimestamp_to_unixtimestamp($oRow->submitTime))),
                ($i % 2) ? "color0" : "color1");
        }

        $sReturn .= html_table_end("");

        return $sReturn;
    }

    /* Get appData for a given version/application, optionally filter by type */
    function getData($iId, $sType, $bIsVersion = TRUE, $bQueued = FALSE)
    {
        $iAppId = 0;
        $iVersionId = 0;

        if($bIsVersion)
            $iVersionId = $iId;
        else
            $iAppId = $iId;

        $hResult = query_parameters("SELECT * FROM appData WHERE appId = '?' AND
            versionId = '?' AND TYPE = '?' AND queued = '?'",
                $iAppId, $iVersionId, $sType, $bQueued ? "true" : "false");

        if(!$hResult || !mysql_num_rows($hResult))
            return FALSE;

        return $hResult;
    }
}

?>
