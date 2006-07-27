<?php
/*****************************/
/* functions for maintainers */
/*****************************/

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

    function maintainer($iMaintainerId = "")
    {
        $sQuery = "SELECT * FROM appMaintainers WHERE maintainerId = '?'";
        $hResult = query_parameters($sQuery, $iMaintainerId);
        if($hResult)
        {
            $oRow = mysql_fetch_object($hResult);
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
                                    $this->bSuperMaintainer, "NOW()", 'true');

        /* this objects id is the insert id returned by mysql */
        $this->iMaintainerId = mysql_insert_id();

        return $hResult;
    }

    function unQueue($sReplyText)
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

                $oApp = new Application($iAppId);
                $oVersion = new Version($iVersionId);
                //Send Status Email
                $sEmail = $oUser->sEmail;
                if ($sEmail)
                {
                    $sSubject =  "Application Maintainer Request Report";
                    $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." has been accepted. ";
                    $sMsg .= $sReplyText;
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\n\n";

                    mail_appdb($sEmail, $sSubject ,$sMsg);
                }    
            }
        } else
        {
            //delete the item from the queue
            query_parameters("DELETE from appMaintainers WHERE userId = '?' AND maintainerId = '?'",
                             $this->iUserId, $this->iMaintainerId);

            if($oUser->isSuperMaintainer($this->iAppId) && !$this->bSuperMaintainer)
                $sStatusMessage = "<p>User is already a super maintainer of this application</p>\n";
            else
                $sStatusMessage = "<p>User is already a maintainer/super maintainer of this application/version</p>\n";
        }

        return $sStatusMessage;
    }

    function reject($sReplyText)
    {
        $oUser = new User($this->iUserId);
        $sEmail = $oUser->sEmail;
        if ($sEmail)
        {
            $oApp = new Application($oRow->appId);
            $oVersion = new Version($oRow->versionId);
            $sSubject =  "Application Maintainer Request Report";
            $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." was rejected. ";
            $sMsg .= $sReplyText;
            $sMsg .= "";
            $sMsg .= "-The AppDB admins\n";
                
           mail_appdb($sEmail, $sSubject ,$sMsg);
        }

        //delete main item
        $sQuery = "DELETE from appMaintainers where maintainerId = '?'";
        $hResult = query_parameters($sQuery, $this->iMaintainerId);

        return $hResult;
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
        $hResult = query_parameters("DELETE from appMaintainers WHERE versionId='?'",
                                    $oVersion->iVersionId);
    }

    function deleteMaintainersForApplication($oApp)
    {
        $sQuery = "DELETE from appMaintainers WHERE appId='?'";
        $hResult = query_parameters($sQuery, $oApp->iAppId);
        return $hResult;
    }

    function ObjectGetEntries($bQueued)
    {
        if($bQueued)
            $sQuery = "SELECT maintainerId FROM appMaintainers, user_list WHERE appMaintainers.userid = user_list.userid ".
                "AND queued = '?' ORDER by submitTime";
        else
            $sQuery = "SELECT maintainerId FROM appMaintainers, user_list WHERE appMaintainers.userid = user_list.userid ".
                "AND queued = '?' ORDER by realname";

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

    function getQueuedMaintainerCount()
    {
        $sQuery = "SELECT count(*) as queued_maintainers FROM appMaintainers where queued='true'";
        $hResult = query_parameters($sQuery);
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
            $hResult = query_parameters("SELECT userId from appMaintainers WHERE appMaintainers.queued = 'false' AND ".
                                        "appMaintainers.versionId = '?'", $iVersionId);
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

    function ObjectOutputHeader()
    {
        echo "    <td>Submission Date</td>\n";
        echo "    <td>Application Name</td>\n";
        echo "    <td>Version</td>\n";
        echo "    <td>Super maintainer?</td>\n";
        echo "    <td>Submitter</td>\n";
    }

    function ObjectOutputTableRow()
    {
        $oUser = new User($this->iUserId);
        $oApp = new Application($this->iAppId);
        $oVersion = new Version($this->iVersionId);
        echo "<td>".print_date(mysqldatetime_to_unixtimestamp($this->aSubmitTime))." &nbsp;</td>\n";
        echo "<td>".$oApp->sName."</td>\n";

        if($this->bSuperMaintainer)
        {
            echo "<td>N/A</td>\n";
            echo "<td>Yes</td>\n";
        } else
        {
  	        echo "<td>".$oVersion->sName." &nbsp;</td>\n";
            echo "<td>No</td>\n";
        }

        echo "<td><a href=\"mailto:".$oUser->sEmail."\">".$oUser->sRealname."</a></td>\n";
    }

    function ObjectDisplayQueueProcessingHelp()
    {
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a maintainer request is rejected.\n";
        echo "Its not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    
    }

    function OutputEditor()
    {
        //view application details
        echo html_frame_start("New Maintainer Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        /**
          * Show the other maintainers of this application, if there are any
          */
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>Other maintainers of this app:</b></td>',"\n";

        $bFoundMaintainers = false;

        $bFirstDisplay = true; /* if false we need to fix up table rows appropriately */

        /* display maintainers for the version */
        $oVersion = new Version($this->iVersionId);
        $aOtherUsers = $oVersion->getMaintainersUserIds();
        if($aOtherUsers)
        {
            $bFoundMaintainers = true;
            while(list($index, $iUserId) = each($aOtherUsers))
            {
                $oUser = new User($iUserId);
                if($bFirstDisplay)
                {
                    echo "<td>".$oUser->sRealname."</td></tr>\n";
                    $bFirstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."</td></tr>\n";
                }
            }
        }

        /* display super maintainers for the given app */
        $aOtherUsers = Maintainer::getSuperMaintainersUserIdsFromAppId($this->iAppId);
        if($aOtherUsers)
        {
            $bFoundMaintainers = true;
            while(list($index, $iUserId) = each($aOtherUsers))
            {
                $oUser = new User($iUserId);
                if($bFirstDisplay)
                {
                    echo "<td>".$oUser->sRealname."*</td></tr>\n";
                    $bFirstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."*</td></tr>\n";
                }
            }
        }

        if(!$bFoundMaintainers)
        {
            echo "<td>No other maintainers</td></tr>\n";
        }

        // Show which other apps the user maintains
        echo '<tr valign="top"><td class="color0" style=\'text-align:right\'><b>This user also maintains these apps:</b></td>',"\n";

        $bFirstDisplay = true;
        $oUser = new User($this->iUserId);
        $aOtherApps = Maintainer::getAppsMaintained($oUser);
        if($aOtherApps)
        {
            while(list($index, list($iAppIdOther, $iVersionIdOther, $bSuperMaintainerOther)) = each($aOtherApps))
            {
                $oApp = new Application($iAppIdOther);
                $oVersion = new Version($iVersionIdOther);
                if($bFirstDisplay)
                {
                    $bFirstDisplay = false;
                    if($bSuperMaintainerOther)
                        echo "<td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                } else
                {
                    if($bSuperMaintainerOther)
                        echo "<td class=color0></td><td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td class=color0></td><td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                }
            }
        } else
        {
            echo "<td>User maintains no other applications</td></tr>\n";
        }

        $oApp = new Application($this->iAppId);
        $oVersion = new Version($this->iVersionId);

        //app name
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>App Name:</b></td>',"\n";
        echo "<td>".$oApp->sName."</td></tr>\n";

        //version
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>App Version:</b></td>',"\n";
        echo "<td>".$oVersion->sName."</td></tr>\n";
         
        //maintainReason
        echo '<tr valign=top><td class=color0 style=\'text-align:right\'><b>Maintainer request reason:</b></td>',"\n";
        echo '<td><textarea name="sMaintainReason" rows=10 cols=35>'.$this->sMaintainReason.'</textarea></td></tr>',"\n";
        echo '</table>';

        echo html_frame_end("&nbsp;");
    }
};

?>
