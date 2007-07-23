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

    function appData($iId = null, $oRow = null, $oObject = null)
    {
        if(!$iId && !$oRow)
            return;

        /* Since all objects stored in the appData table have a number of common
           members, we can import such an object into an appData one without
           making an SQL query */
        if($oObject && $iId)
        {
            $this->iSubmitterId = $oObject->iSubmitterId;
            $this->sDescription = $oObject->sDescription;
            $this->iAppId = $oObject->iAppId;
            $this->iVersionId = $oObject->iVersionId;
            $this->sSubmitTime = $oObject->sSubmitTime;
            $this->iId = $iId;
            return;
        }

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

    function reQueue()
    {
        if(!$this->canEdit())
            return FALSE;

        $sQuery = "UPDATE appData SET queued = '?' WHERE id = '?'";
        $hResult = query_parameters($sQuery, "true", $this->iId);

        if(!$hResult)
            return FALSE;
        else
            return TRUE;
    }

    function reject()
    {
        if(!$this->canEdit())
            return FALSE;

        $sQuery = "UPDATE appData SET queued = '?' WHERE id = '?'";
        $hResult = query_parameters($sQuery, "rejected", $this->iId);

        if(!$hResult)
            return FALSE;
        else
            return TRUE;
    }

    function update($bSilent = FALSE)
    {
        if(!$this->canEdit())
            return FALSE;

        $sQuery = "UPDATE appData SET versionId = '?', appId = '?', sDescription = '?'
                        WHERE id = '?'";
        $hResult = query_parameters($this->iVersionId, $this->iAppId,
                                    $this->sDescription, $this->iId);
 
        if(!$hResult)
        {
            if(!$bResult)
                addmsg("Failed to update add data", "red");
            return FALSE;
        }

        if(!$bSilent)
            addmsg("Updated app data successfully", "green");

        return TRUE;
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
    function getData($iId, $sType, $bIsVersion = TRUE, $bQueued = FALSE, $bRejected = FALSE)
    {
        $iAppId = 0;
        $iVersionId = 0;

        if($bIsVersion)
            $iVersionId = $iId;
        else
            $iAppId = $iId;

        $sQueued = objectManager::getQueueString($bQueued, $bRejected);

        $hResult = query_parameters("SELECT * FROM appData WHERE appId = '?' AND
                                     versionId = '?' AND TYPE = '?' AND queued = '?'",
                                    $iAppId, $iVersionId, $sType, $sQueued);

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

        $sSelectType = "";
        $sLimit = "";

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
            } else
            {
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId);
            }
        } else
        {
            if($sQueued == "true" || $sQueued == "false")
                $sAppDataQueued = " AND appData.queued = '$sQueued'";

            if($sType)
                $sSelectType = " AND type = '?'";

            $sQuery = "(SELECT COUNT(DISTINCT appData.id) as count FROM appData,
                appFamily, appVersion WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    (
                        appData.appId = appFamily.appId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'$sAppDataQueued$sSelectType) UNION
                    (
                    SELECT COUNT(DISTINCT appData.id) as count FROM appData,
                appFamily, appVersion WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    (
                        appData.versionId = appVersion.versionId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'$sAppDataQueued$sSelectType)";

            if($sType)
                $hResult = query_parameters($sQuery, $sType, $sType);
            else
                $hResult = query_parameters($sQuery);
        }

        if(!$hResult)
            return FALSE;

        for($iCount = 0; $oRow = mysql_fetch_object($hResult);)
            $iCount += $oRow->count;

        return $iCount;
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

        $sSelectType = "";
        $sLimit = "";

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
            if(!$iRows && !$iStart)
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
            if($iStart || $iRows)
                $sLimit = " LIMIT ?,?";

            $sQuery = "(SELECT DISTINCT appData.* FROM appData,
                appFamily, appVersion WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    (
                        appData.appId = appFamily.appId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'
                    AND
                    appData.queued = '?'
                    AND
                    appData.type = '?'$sLimit) UNION
                    (
                    SELECT DISTINCT appData.* FROM appData,
                appFamily, appVersion WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    (
                        appData.versionId = appVersion.versionId
                    )
                    AND
                    appVersion.queued = 'false'
                    AND
                    appFamily.queued = 'false'
                    AND
                    appData.queued = '?'
                    AND
                    appData.type = '?'$sLimit)";
            if(!$iRows && !$iStart)
            {
                $hResult = query_parameters($sQuery, $bQueued ? "true" : "false", $sType,
                                           $bQueued ? "true" : "false", $sType);
            } else
            {
                if(!$iRows)
                    $iRows = appData::objectGetEntriesCount($bQueued ? "true" : "false",
                                                            $bRejected, $sType);
                $hResult = query_parameters($sQuery, $bQueued ? "true" : "false", $sType,
                                            $iStart, $iRows,
                                            $bQueued ? "true" : "false", $sType,
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
                if($oVersion->canEdit() && $oVersion->sQueued == "false")
                    return FALSE;
                else
                    return TRUE;
            } else if($this->iAppId)
            {
                $oApp = new application($this->iAppId);
                if($oApp->canEdit() && $oApp->sQueued == "false")
                    return FALSE;
                else
                    return TRUE;
            } else
                return TRUE;
        }
    }

    function objectGetTableRow()
    {
        $oVersion = new Version($this->iVersionId);

        if(!$this->iAppId)
            $this->iAppId = $oVersion->iAppId;

        $oApp = new Application($this->iAppId);
        $oUser = new User($this->iSubmitterId);

        $oTableRow = new TableRow();
        $oTableRow->AddTextCell(print_date(mysqltimestamp_to_unixtimestamp($this->sSubmitTime)));
        $oTableRow->AddTextCell($oUser->objectMakeLink());
        $oTableRow->AddTextCell($oApp->objectMakeLink());
        $oTableRow->AddTextCell($this->iVersionId ? $oVersion->objectMakeLink() : "N/A");

        // create the object manager specific row
        $oOMTableRow = new OMTableRow($oTableRow);

        return $oOMTableRow;
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

    function objectGetId()
    {
        return $this->iId;
    }
}

?>
