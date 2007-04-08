<?php
/*****************************/
/* functions for maintainers */
/*****************************/
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");
require_once(BASE."include/user.php");

class maintainer
{
    var $iMaintainerId;
    var $iAppId;
    var $iVersionId;
    var $iUserId;
    var $sMaintainReason;
    var $bSuperMaintainer;
    var $aSubmitTime;
    var $bQueued;
    var $sReplyText;

    function maintainer($iMaintainerId = "")
    {
        $sQuery = "SELECT * FROM appMaintainers WHERE maintainerId = '?'";
        $hResult = query_parameters($sQuery, $iMaintainerId);
        if($hResult)
        {
            $oRow = mysql_fetch_object($hResult);
            if($oRow)
            {
                $this->iMaintainerId = $oRow->maintainerId;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->iUserId = $oRow->userId;
                $this->sMaintainReason = $oRow->maintainReason;
                $this->bSuperMaintainer = $oRow->superMaintainer;
                $this->aSubmitTime = $oRow->submitTime;
                $this->bQueued = $oRow->queued;
            }
        }
    }

    function create()
    {
        /* user id, appid, and maintain reason must be valid to continue */
        if(!$this->iUserId || !$this->iAppId || !$this->sMaintainReason)
            return NULL;

        $hResult = query_parameters("INSERT INTO appMaintainers (appId, versionId, ".
                                    "userId, maintainReason, superMaintainer, submitTime, queued) ".
                                    "VALUES ('?', '?', '?', '?', '?', ?, '?')",
                                    $this->iAppId, $this->iVersionId,
                                    $this->iUserId, $this->sMaintainReason,
                                    $this->bSuperMaintainer, "NOW()", $this->mustBeQueued() ? "true" : "false");

        /* this objects id is the insert id returned by mysql */
        $this->iMaintainerId = mysql_insert_id();

        /* If this is a non-queued maintainer submission, remove the user's non-
           super maintainer entries for the application's versions.  This check is
           also done in unQueue() */
        if(!$this->mustBeQueued() & $this->bSuperMaintainer)
            $this->removeUserFromAppVersions();

        return $hResult;
    }

    function unQueue()
    {
        /* if the user isn't already a supermaintainer of the application and */
        /* if they are trying to become a maintainer and aren't already a maintainer of */
        /* the version, then continue processing the request */

        $oUser = new User($this->iUserId);
        
        if(!$oUser->isSuperMaintainer($this->iAppId) &&
           ((!$this->bSuperMaintainer && !$oUser->isMaintainer($this->iVersionId)) | $this->bSuperMaintainer))
        {
            /* unqueue the maintainer entry */
            $hResult = query_parameters("UPDATE appMaintainers SET queued='false' WHERE userId = '?' AND maintainerId = '?'",
                                        $this->iUserId, $this->iMaintainerId);

            if($hResult)
            {
                $sStatusMessage = "<p>The maintainer was successfully added into the database</p>\n";

                //Send Status Email
                $sEmail = $oUser->sEmail;
                if ($sEmail)
                {
                    if($this->iVersionId)
                    {
                        $oVersion = new Version($this->iVersionId);
                        $sURL = $oVersion->objectMakeUrl();
                        $sName = version::fullName($this->iVersionId);
                    } else
                    {
                        $oApp = new Application($this->iAppId);
                        $sURL = $oApp->objectMakeUrl();
                        $sName = $oApp->sName;
                    }

                    $sSubject =  "Application Maintainer Request Report";
                    $sMsg  = "Your application to be the maintainer of $sName has been accepted.\n";
                    $sMsg .= "$sURL\n";
                    $sMsg .= "$this->sReplyText\n";
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\n\n";

                    mail_appdb($sEmail, $sSubject ,$sMsg);
                }
            }
        } else
        {
            /* Delete entry, but only if queued */
            query_parameters("DELETE from appMaintainers WHERE userId = '?' AND maintainerId = '?' AND queued = 'true'", $this->iUserId, $this->iMaintainerId);

            if($oUser->isSuperMaintainer($this->iAppId) && !$this->bSuperMaintainer)
                $sStatusMessage = "<p>User is already a super maintainer of this application</p>\n";
            else
                $sStatusMessage = "<p>User is already a maintainer/super maintainer of this application/version</p>\n";
        }

        /* Delete any maintainer entries the user had for the application's versions,
           if this is a super maintainer request */
        if($this->bSuperMaintainer)
            $this->removeUserFromAppVersions();

        return $sStatusMessage;
    }

    function reject()
    {
        $oUser = new User($this->iUserId);
        $sEmail = $oUser->sEmail;
        if ($sEmail)
        {
            $oApp = new Application($oRow->appId);
            $oVersion = new Version($oRow->versionId);
            $sSubject =  "Application Maintainer Request Report";
            $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." was rejected. ";
            $sMsg .= $this->sReplyText;
            $sMsg .= "";
            $sMsg .= "-The AppDB admins\n";

           mail_appdb($sEmail, $sSubject ,$sMsg);
        }

        //delete main item
        $sQuery = "DELETE from appMaintainers where maintainerId = '?'";
        $hResult = query_parameters($sQuery, $this->iMaintainerId);

        return $hResult;
    }

    function mustBeQueued()
    {
        /* In place for future fine-grained permisson system, only allow admins for now */
        if($_SESSION['current']->hasPriv("admin"))
            return FALSE;
        else
            return TRUE;
    }

    function delete()
    {
        $sQuery = "DELETE from appMaintainers where maintainerId = '?'";
        query_parameters($sQuery, $this->iMaintainerId);
    }

    function deleteMaintainer($oUser, $iAppId = null, $iVersionId = null)
    {
        /* remove supermaintainer */
        if($iAppId && ($iVersionId == null))
        {
            $superMaintainer = 1;
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'
                                         AND appId = '?' AND superMaintainer = '?'",
                                        $oUser->iUserId, $iAppId, $superMaintainer);
        } else if($iAppId && $iVersionId) /* remove a normal maintainer */
        {
            $superMaintainer = 0;
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'
                                         AND appId = '?' AND versionId = '?' AND superMaintainer = '?'",
                                        $oUser->iUserId, $iAppId, $iVersionId, $superMaintainer);
        } else if(($iAppId == null) && ($iVersionId == null)) /* remove all maintainership by this user */
        {
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'",
                                        $oUser->iUserId);
        }

        if($hResult)
            return true;
        
        return false;
    }

    function deleteMaintainersForVersion($oVersion)
    {
        if(!$oVersion->iVersionId)
            return FALSE;

        $hResult = query_parameters("DELETE from appMaintainers WHERE versionId='?'",
                                    $oVersion->iVersionId);
        return $hResult;
    }

    function deleteMaintainersForApplication($oApp)
    {
        $sQuery = "DELETE from appMaintainers WHERE appId='?'";
        $hResult = query_parameters($sQuery, $oApp->iAppId);
        return $hResult;
    }

    function ObjectGetEntries($bQueued, $bRejected)
    {
        /* Not implemented */
        if($bRejected)
            return FALSE;

        /* Excluding requests for queued apps and versions, as these will be
           handled automatically */
        $sQuery = "SELECT DISTINCT maintainerId, appMaintainers.submitTime FROM 
            appMaintainers, appFamily, appVersion WHERE
            appMaintainers.queued = '?'
            AND
            appFamily.appId = appVersion.appId
            AND
            (
                (
                    appFamily.appId = appMaintainers.appId
                    AND
                    appFamily.queued = 'false'
                    AND 
                    appMaintainers.versionId = ''
                )
                OR
                (
                    appVersion.versionId = appMaintainers.versionId
                    AND
                    appVersion.queued = 'false'
                )
            )";

        if($bQueued)
        {
            if($_SESSION['current']->hasPriv("admin"))
                return query_parameters($sQuery, $bQueued ? "true" : "false");
            else
                return NULL;
        } else
        {
            return query_parameters($sQuery, $bQueued ? "true" : "false");
        }
    }

    /* retrieve a maintainer object from a row returned by */
    /* ObjectGetEntries() */
    function ObjectGetObjectFromObjectGetEntriesRow($oRow)
    {
        return new maintainer($oRow->maintainerId);
    }

    function getMaintainerCountForUser($oUser, $bSuperMaintainer)
    {
        $sQuery = "SELECT count(*) as cnt from appMaintainers WHERE userid = '?' AND superMaintainer = '?'".
                  " AND queued ='?'";
        $hResult = query_parameters($sQuery, $oUser->iUserId, $bSuperMaintainer ? "1" : "0", "false");
        if(!$hResult)
            return 0;
        $oRow = mysql_fetch_object($hResult);
        return $oRow->cnt;
    }

    /**
     * get the applications and versions that this user maintains 
     */
    function getAppsMaintained($oUser)
    {
        /* retrieve the list of application and order them by application name */
        $hResult = query_parameters("SELECT appMaintainers.appId, versionId, superMaintainer, appName FROM ".
                                    "appFamily, appMaintainers WHERE appFamily.appId = appMaintainers.appId ".
                                    "AND userId = '?' AND appMaintainers.queued = '?' ORDER BY appName",
                                    $oUser->iUserId, "false");
        if(!$hResult || mysql_num_rows($hResult) == 0)
            return NULL;

        $aAppsMaintained = array();
        $c = 0;
        while($oRow = mysql_fetch_object($hResult))
        {
            $aAppsMaintained[$c] = array($oRow->appId, $oRow->versionId, $oRow->superMaintainer);
            $c++;
        }

        return $aAppsMaintained;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        /* Not implemented */
        if($bRejected)
            return FALSE;

        /* Excluding requests for queued apps and versions, as these are handled 
           automatically.  One SELECT for super maintainers, one for maintainers. */
       $sQuery = "SELECT COUNT(DISTINCT maintainerId) as queued_maintainers FROM 
                appMaintainers, appFamily, appVersion WHERE
                appMaintainers.queued = '?'
                AND
                appFamily.appId = appVersion.appId
                AND
                (
                    (
                        appFamily.appId = appMaintainers.appId
                        AND
                        appFamily.queued = 'false'
                        AND 
                        appMaintainers.versionId = ''
                    )
                    OR
                    (
                        appVersion.versionId = appMaintainers.versionId
                        AND
                        appVersion.queued = 'false'
                    )
                )";

        if(!($hResult = query_parameters($sQuery, $bQueued ? "true" : "false")))
            return FALSE;

        $oRow = mysql_fetch_object($hResult);

        return $oRow->queued_maintainers;
    }

    /* see how many maintainer entries we have in the database */
    function getMaintainerCount()
    {
        $sQuery = "SELECT count(*) as maintainers FROM appMaintainers where queued='false'";
        $hResult = query_parameters($sQuery);
        $oRow = mysql_fetch_object($hResult);
        return $oRow->maintainers;
    }

    /* see how many unique maintainers we actually have */
    function getNumberOfMaintainers()
    {
        $hResult = query_parameters("SELECT DISTINCT userId FROM appMaintainers WHERE queued='false';");
        return mysql_num_rows($hResult);
    }

    function isUserMaintainer($oUser, $iVersionId = null)
    {
        /* if we are a super maintainer, we are a maintainer of this version as well */
        $oVersion = new Version($iVersionId);
        if($oUser->isSuperMaintainer($oVersion->iAppId))
            return true;

        /* otherwise check if we maintain this specific version */
        if($iVersionId)
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND versionId = '?' AND queued = '?'";
            $hResult = query_parameters($sQuery, $oUser->iUserId, $iVersionId, "false");
        } else // are we maintaining any version ?
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND queued = '?'";
            $hResult = query_parameters($sQuery, $oUser->iUserId, "false");
        }
        if(!$hResult)
            return false;
        
        return mysql_num_rows($hResult);
    }

    function isUserSuperMaintainer($oUser, $iAppId = null)
    {
        if($iAppId)
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND appId = '?' AND superMaintainer = '1' AND queued = '?'";
            $hResult = query_parameters($sQuery, $oUser->iUserId, $iAppId, "false");
        } else /* are we super maintainer of any applications? */
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND superMaintainer = '1' AND queued = '?'";
            $hResult = query_parameters($sQuery, $oUser->iUserId, "false");
        }
        if(!$hResult)
            return false;
        return mysql_num_rows($hResult);
    }

    /* if given an appid or a version id return a handle for a query that has */
    /* the user ids that are maintainers for this particular appid or version id */
    function getMaintainersForAppIdVersionId($iAppId = null, $iVersionId = null)
    {
        $hResult = null;

        if($iVersionId)
        {
            $hResult = query_parameters("SELECT userId from appMaintainers, appVersion
                WHERE appMaintainers.queued = 'false' AND appVersion.versionId = '?'
                AND ((appMaintainers.versionId = appVersion.versionId) OR
                (appMaintainers.appId = appVersion.appId))", $iVersionId);
        } 
        /*
         * If versionId was not supplied we fetch supermaintainers of application and maintainer of all versions.
         */
        elseif($iAppId)
        {
            $hResult = query_parameters("SELECT userId 
                                 FROM appMaintainers
                                 WHERE appId = '?' AND queued = 'false'",
                                        $iAppId);
        }

        return $hResult;
    }

    /*
     * get the userIds of super maintainers for this appId
     */
    function getSuperMaintainersUserIdsFromAppId($iAppId)
    {
        $sQuery = "SELECT userId FROM ".
            "appMaintainers WHERE appId = '?' " .
            "AND superMaintainer = '1' AND queued='?';";
        $hResult = query_parameters($sQuery, $iAppId, "false");
        $aUserIds = array();
        $c = 0;
        while($oRow = mysql_fetch_object($hResult))
        {
            $aUserIds[$c] = $oRow->userId;
            $c++;
        }

        return $aUserIds;
    }

    function ObjectGetHeader()
    {
        $aCells = array(
            "Submission Date",
            "Application Name",
            "Version",
            "Super maintainer?",
            "Submitter");

        return $aCells;
    }

    /* arg1 = OM object, arg2 = CSS style, arg3 = text for edit link */
    function ObjectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        $oUser = new User($this->iUserId);
        $oApp = new Application($this->iAppId);
        $oVersion = new Version($this->iVersionId);

        $aCells = array(
            print_date(mysqldatetime_to_unixtimestamp($this->aSubmitTime)),
            $oApp->objectMakeLink(),
            ($this->bSuperMaintainer) ? "N/A" : $oVersion->objectMakeLink(),
            ($this->bSuperMaintainer) ? "Yes" : "No",
            $oUser->objectMakeLink());

        if(maintainer::canEdit())
            $aCells[sizeof($aCells)] = "[ <a href=\"".$oObject->makeUrl("edit",
            $this->iMaintainerId)."\">$sEditLinkLabel</a> ]";

        echo html_tr($aCells,
            $sClass);
    }

    function ObjectDisplayQueueProcessingHelp()
    {
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a maintainer request is rejected.\n";
        echo "Its not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    
    }

    function objectGetInstanceFromRow($oRow)
    {
        return new maintainer($oRow->maintainerId, $oRow);
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;

        return FALSE;
    }

    function outputEditor()
    {
        //view application details
        echo html_frame_start("New Maintainer Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo "<input type=\"hidden\" name=\"iMaintainerId\" ".
             "value=\"$this->iMaintainerId\" />";
        /**
          * Show the other maintainers of this application, if there are any
          */
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>Other maintainers of this app:</b></td>',"\n";

        /* Fetch maintainers and super maintainers */
        $oVersion = new Version($this->iVersionId);
        $aOtherMaintainers = $oVersion->getMaintainersUserIds();
        $aOtherSuperMaintainers =
               Maintainer::getSuperMaintainersUserIdsFromAppId($this->iAppId);

        if($aOtherMaintainers || $aOtherSuperMaintainers)
            $bFoundMaintainers = true;
        else
            $bFoundMaintainers = false;

        echo "<td>\n";
        /* display maintainers for the version */
        if($aOtherMaintainers)
        {
            while(list($index, $iUserId) = each($aOtherMaintainers))
            {
                $oUser = new User($iUserId);
                echo "$oUser->sRealname<br />\n";
            }
        }

        /* display super maintainers for the given app */

        if($aOtherSuperMaintainers)
        {
            while(list($index, $iUserId) = each($aOtherSuperMaintainers))
            {
                $oUser = new User($iUserId);
                echo "$oUser->sRealname*<br />\n";
            }
        }

        if(!$bFoundMaintainers)
        {
            echo "No other maintainers";
        }

        echo "</td></tr>\n";

        // Show which other apps the user maintains
        echo '<tr valign="top"><td class="color0" style=\'text-align:right\'><b>This user also maintains these apps:</b></td><td>',"\n";

        $oUser = new User($this->iUserId);
        $aOtherApps = Maintainer::getAppsMaintained($oUser);
        if($aOtherApps)
        {
            while(list($index, list($iAppIdOther, $iVersionIdOther, $bSuperMaintainerOther)) = each($aOtherApps))
            {
                $oApp = new Application($iAppIdOther);

                if($bSuperMaintainerOther)
                    echo $oApp->objectMakeLink()."*<br />\n";
                else
                    echo $oVersion->fullNameLink($iVersionIdOther)."<br />\n";
            }
        } else
        {
            echo "User maintains no other applications";
        }

        echo "</td></tr>\n";

        $oApp = new Application($this->iAppId);
        $oVersion = new Version($this->iVersionId);

        //app name
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>App Name:</b></td>',"\n";
        echo "<td>".$oApp->objectMakeLink()."</td></tr>\n";

        //version
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>App Version:</b></td>',"\n";
        echo "<td>".$oVersion->objectMakeLink()."</td></tr>\n";
         
        //maintainReason
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>Maintainer request reason:</b></td>',"\n";
        echo '<td><textarea name="sMaintainReason" rows=10 cols=35>'.$this->sMaintainReason.'</textarea></td></tr>',"\n";
        echo '</table>';

        echo html_frame_end("&nbsp;");
    }

    function ObjectGetId()
    {
        return $this->iMaintainerId;
    }

    function getOutputEditorValues($aClean)
    {
        $this->sReplyText = $aClean['sReplyText'];

        return TRUE;
    }

    function update()
    {
        /* STUB: No updating possible at the moment */
        return TRUE;
    }

    function getDefaultReply()
    {
        $sReplyTextHelp = "Enter a personalized reason for accepting or rejecting the ".
                          "user's maintainer request here";

        return $sReplyTextHelp;
    }

    function objectHideDelete()
    {
        return TRUE;
    }

    function display()
    {
        /* STUB: There is not much use for this, but it may be implemented later */
        return TRUE;
    }

    function objectMakeUrl()
    {
        /* STUB: There is not much use for this, but it may be implemented later */
        return TRUE;
    }

    function objectMakeLink()
    {
        /* STUB: There is not much use for this, but it may be implemented later */
        return TRUE;
    }

    /* Delete a user's non-super maintainer entries for an application.  This is useful
       to ensure that the user has no maintainer entries for an app he supermaintains */
    function removeUserFromAppVersions()
    {
        $sQuery = "DELETE FROM appMaintainers WHERE
                superMaintainer = '0'
                AND
                appId = '?'
                AND
                userId = '?'";
        $hResult = query_parameters($sQuery, $this->iAppId, $this->iUserId);

        if(!$hResult)
            return FALSE;

        return TRUE;
    }
}

?>
