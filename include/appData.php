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

    function delete()
    {
        if(!$this->canEdit())
            return FALSE;

        $sQuery = "DELETE FROM appData WHERE id = '?'";

        $hResult = query_parameters($sQuery, $this->iId);

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function listSubmittedBy($iUserId, $bQueued = true)
    {
        $hResult = query_parameters("SELECT * FROM appData WHERE
                appData.submitterId = '?'
                AND
                appData.queued = '?'
                    ORDER BY appData.id",
                        $iUserId, $bQueued ? "true" : "false");

        if(!$hResult || !mysql_num_rows($hResult))
            return false;

        $sReturn = html_table_begin("width=\"100%\" align=\"center\"");
        $sReturn .= html_tr(array(
            "Version",
            "Type",
            "Description",
            "Submission Date"),
            "color4");

        for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
        {
            if($oRow->versionId)
            {
                $oVersion = new version($oRow->versionId);
                $sLink = "<a href=\"".$oVersion->objectMakeUrl()."\">".
                         $oVersion->fullName($oVersion->iVersionId)."</a>";
            } else
            {
                $oApp = new application($this->appId);
                $sLink = $oApp->objectMakeLink();
            }
            $sReturn .= html_tr(array(
                $sLink,
                $oRow->type,
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

    function objectGetEntriesCount($sQueued, $bRejected, $sType = null)
    {
        /* Not implemented for appData */
        if($bRejected)
            return FALSE;

        /* Compatibility with objectManager */
        if($sQueued === true)
            $sQueued = "true";
        if($sQueued === false)
            $sQueued = "false";

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

    function objectGetHeader($sType)
    {
        $aCells = array(
            "Submission Date",
            "Submitter",
            "Application",
            "Version");

        return $aCells;
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0, $sType)
    {
        /* Not implemented for appData */
        if($bRejected)
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
                                                            $bRejected, $sType);
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
                                                            $bRejected, $sType);
                $sQuery .= " LIMIT ?,?";
                $hResult = query_parameters($sQuery, $bQueued ? "true" : "false", $sType,
                                            $iStart, $iRows);
            }
        }

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;
        if($this)
        {
            if($this->iVersionId)
            {
                $oVersion = new version($this->iVersionId);
                if($oVersion->canEdit())
                    return TRUE;
                else
                    return FALSE;
            } else if($this->iAppId)
            {
                $oApp = new application($this->iAppId);
                if($oApp->canEdit())
                    return TRUE;
                else
                    return FALSE;
            } else
                return FALSE;
        }
    }

    function mustBeQueued()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return FALSE;
        if($this)
        {
            if($this->iVersionId)
            {
                $oVersion = new version($this->iVersionId);
                if($oVersion->canEdit())
                    return FALSE;
                else
                    return TRUE;
            } else if($this->iAppId)
            {
                $oApp = new application($this->iAppId);
                if($oApp->canEdit())
                    return FALSE;
                else
                    return TRUE;
            } else
                return TRUE;
        }
    }

    /* arg1 = OM object, arg2 = CSS style, arg3 = text for edit link */
    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        $oVersion = new Version($this->iVersionId);

        if(!$this->iAppId)
            $this->iAppId = $oVersion->iAppId;

        $oApp = new Application($this->iAppId);
        $oUser = new User($this->iSubmitterId);
        $aCells = array(
                print_date(mysqltimestamp_to_unixtimestamp($this->sSubmitTime)),
                $oUser->objectMakeLink(),
                $oApp->objectMakeLink(),
                $this->iVersionId ? $oVersion->objectMakeLink() : "N/A");

        if(appData::canEdit($oObject->sClass))
            $aCells[] = "[ <a href=\"".$oObject->makeUrl("edit",
                        $this->iId)."\">$sEditLinkLabel</a> ]";

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
