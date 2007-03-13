<?php

/**
 * Functions related to application data
 */

require_once(BASE."include/util.php");

class appData
{
    var $iId;
    var $iAppId;
    var $iVersionId;
    var $iSubmitterId;
    var $sSubmitTime;
    var $sDescription;

    function appData($iId = null, $oRow = null)
    {
        if(!$iId)
            return;

        if(!$oRow)
        {
            $hResult = query_parameters("SELECT * FROM appData WHERE id = '?'", $iId);
            $oRow = mysql_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iSubmitterId = $oRow->submitterId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->sSubmitTime = $oRow->submitTime;
            $this->iId = $iId;
            $this->sDescription = $oRow->description;
        }
    }

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

    function objectGetEntriesCount($sQueued, $sType = null)
    {
        if(($sQueued == "true" || $sQueued == "all") && !appData::canEdit($sType))
           return FALSE;

        if(($sQueued == "true" || $sQueued == "all") &&
            !$_SESSION['current']->hasPriv("admin"))
        {
           $sQuery = "SELECT COUNT(DISTINCT appData.id) as count FROM appData,
           appMaintainers, appVersion, appFamily WHERE
                appFamily.appId = appVersion.appId
                AND
                appMaintainers.userId = '?'
                AND
                (
                    (
                        appMaintainers.appId = appFamily.appId
                        OR
                        appMaintainers.appId = appVersion.appId
                    )
                    AND
                    appMaintainers.superMaintainer = '1'
                    AND
                    (
                        appData.appId = appMaintainers.appId
                        OR
                        (
                            appData.versionId = appVersion.versionId
                            AND
                            appVersion.appId = appMaintainers.appId
                        )
                    )
                    OR
                    (
                        appMaintainers.superMaintainer = '0'
                        AND
                        appMaintainers.versionId = appVersion.versionId
                        AND
                        appMaintainers.versionId = appData.versionId
                    )
                )
                AND
                appVersion.queued = 'false'
                AND
                appFamily.queued = 'false'";

            if($sQueued == "true")
                $sQuery .= " AND appData.queued = 'true'";

            if($sType)
            {
                $sQuery .= " AND type = '?'";
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId, 
                                            $sType);
            } else {
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId);
            }
        } else
        {
            $sQuery = "SELECT COUNT(DISTINCT appData.id) as count FROM appData,
                appFamily, appVersion WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    (
                        appData.appId = appFamily.appId
                        OR
                        appData.versionId = appVersion.versionId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'";

            if($sQueued == "true" || $sQueued == "false")
                $sQuery .= " AND appData.queued = '$sQueued'";


            if($sType)
            {
                $sQuery .= " AND type = '?'";
                $hResult = query_parameters($sQuery, $sType);
            } else
                $hResult = query_parameters($sQuery);
        }

        if(!$hResult)
            return FALSE;

        if(!$oRow = mysql_fetch_object($hResult))
            return FALSE;

        return $oRow->count;

    }

    function objectOutputHeader($sClass, $sType)
    {
        $aCells = array(
            "Submission Date",
            "Submitter",
            "Application",
            "Version");

        if(appData::canEdit($sType))
            $aCells[] = "Action";

        echo html_tr($aCells, $sClass);
    }

    function objectGetEntries($bQueued, $iRows = 0, $iStart = 0, $sType)
    {
        if($bQueued && !appData::canEdit($sType))
            return FALSE;

        if($bQueued && !$_SESSION['current']->hasPriv("admin"))
        {
            $sQuery = "SELECT DISTINCT appData.* FROM appData, appMaintainers,
                appVersion, appFamily WHERE
                appFamily.appId = appVersion.appId
                AND
                appMaintainers.userId = '?'
                AND
                (
                    (
                        (
                            appMaintainers.appId = appFamily.appId
                            OR
                            appMaintainers.appId = appVersion.appId
                        )
                        AND
                        appMaintainers.superMaintainer = '1'
                        AND
                        (
                            appData.appId = appMaintainers.appId
                            OR
                            (
                                appData.versionId = appVersion.versionId
                                AND
                                appVersion.appId = appMaintainers.appId
                            )
                        )
                    )
                    OR
                    (
                        appMaintainers.superMaintainer = '0'
                        AND
                        appMaintainers.versionId = appVersion.versionId
                        AND
                        appMaintainers.versionId = appData.versionId
                    )
                )
                AND
                appVersion.queued = 'false'
                AND
                appFamily.queued = 'false'
                AND
                appData.queued = '?'
                AND
                appData.type = '?'";
            if(!$iRows && !$iStarts)
            {
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                        $bQueued ? "true" : "false", $sType);
            } else
            {
                if(!$iRows)
                    $iRows = appData::objectGetEntriesCount($bQueued ? "true" : "false",
                                                            $sType);
                $sQuery .= " LIMIT ?,?";
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                            $bQueued ? "true" : "false", $sType,
                                            $iStart, $iRows);
            }
        } else
        {
            $sQuery = "SELECT DISTINCT appData.* FROM appData, appFamily, appVersion
                    WHERE
                    appVersion.appId = appFamily.appId
                    AND
                    (
                        appData.appId = appFamily.appId
                        OR
                        appData.versionId = appVersion.versionId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'
                    AND
                    appData.queued = '?'
                    AND
                    appData.type = '?'";
            if(!$iRows && !$iStart)
            {
                $hResult = query_parameters($sQuery, $bQueued ? "true" : "false", $sType);
            } else
            {
                if(!$iRows)
                    $iRows = appData::objectGetEntriesCount($bQueued ? "true" : "false",
                                                            $sType);
                $sQuery .= " LIMIT ?,?";
                $hResult = query_parameters($sQuery, $bQueued ? "true" : "false", $sType,
                                            $iStart, $iRows);
            }
        }

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function canEdit($sType = null)
    {
        if($sType)
        {
            $oObject = new $sType();
            return $oObject->canEdit();
        } else
        {
            if($_SESSION['current']->hasPriv("admin") ||
               maintainer::isUserMaintainer($_SESSION['current']))
                return TRUE;
            else
                return FALSE;
        }
    }

    function objectOutputTableRow($oObject, $sClass)
    {
        $oVersion = new Version($this->iVersionId);

        if(!$this->iAppId)
            $this->iAppId = $oVersion->iAppId;

        $oApp = new Application($this->iAppId);
        $oUser = new User($this->iSubmitterId);
        $aCells = array(
                print_date(mysqldatetime_to_unixtimestamp($this->sSubmitTime)),
                $oUser->sRealname,
                $oApp->sName,
                $this->iVersionId ? $oVersion->sName : "N/A");

        if(appData::canEdit($oObject->sClass))
            $aCells[] = "[ <a href=\"".$oObject->makeUrl("edit",
                        $this->iId)."\">Process</a> ]";

        echo html_tr($aCells, $sClass);
    }

    function objectDisplayQueueProcessingHelp()
    {
        $sHelp = "<p>This is a list of application data submitted by users. ".
                 "Please inspect the data carefully before accepting or rejecting it.</p>";
        echo $sHelp;
    }

    /* Output the part of an appData editor which is the same for all data types */
    function outputEditorGeneric()
    {
        $oVersion = new version($this->iVersionId);
        if($oVersion->iVersionId)
        {
            $this->iAppId = $oVersion->iAppId;
            $sVersionName = $oVersion->objectMakeLink();
        }
        else
            $sVersionName = "N/A";

        $oApp = new Application($this->iAppId);

        // view application details
        echo html_frame_start("New Application Data Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".$oApp->objectMakeLink()."</td></tr>\n";

        // version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>$sVersionName</td></tr>\n";

        //dataDescription
        echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
        echo '<td><textarea name="sDescription" rows=10 cols=35>'.stripslashes($this->sDescription).'</textarea></td></tr>',"\n";
    }

    function getDefaultReply()
    {
        $sReplyText = "Enter a personalized reason for acceptance or rejection of the".
                      " submitted application data here";
        return $sReplyText;
    }
}

?>
