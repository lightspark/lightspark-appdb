<?php
/*****************************/
/* functions for maintainers */
/*****************************/
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");
require_once(BASE."include/user.php");

//FIXME: when we have php5 move this into the maintainer class as a constant var
define('iNotificationIntervalDays', 7); // days between each notification level

// class that can retrieve the queued entries for a given maintainer instance
class queuedEntries
{
  var $aVersionIds;
  var $aScreenshotIds;
  var $aTestDataIds;

  var $oMaintainer; // the maintainer we are retrieving entries for

  function queuedEntries($oMaintainer)
  {
    $this->oMaintainer = $oMaintainer;

    $this->aVersionIds = array();
    $this->aScreenshotIds = array();
    $this->aTestDataIds = array();
  }

  // returns true if none of the arrays have any entries in them
  function isEmpty()
  {
    if((count($this->aVersionIds) == 0) &&
       (count($this->aScreenshotIds) == 0) &&
       (count($this->aTestDataIds) == 0))
      {
        return true;
      }

    return false;
  }

  function retrieveQueuedEntries()
  {
    $bDebugOutputEnabled = false;

    if($bDebugOutputEnabled)
      echo "retrieveQueuedEntries() starting\n";

    ////////////////////////////////////
    // retrieve a list of versions to check for queued data
    $aVersionsToCheck = array();
    if($this->oMaintainer->bSuperMaintainer)
    {
      if($bDebugOutputEnabled)
        echo "maintainer is super maintainer\n";

      $oApp = new Application($this->oMaintainer->iAppId);

      //TODO: would like to rely on the constructor but we might be a user with 'admin'
      // privileges and that would mean we would end up retrieved queued versions for
      // this application or unqueued versions depending on which user we were
      $hResult = $oApp->_internal_retrieve_all_versions();

      while($oVersionRow = mysql_fetch_object($hResult))
      {
        if($bDebugOutputEnabled)
        {
          echo "oVersionRow is: ";
          print_r($oVersionRow);
        }

        $oVersion = new Version($oVersionRow->versionId);

        if($bDebugOutputEnabled)
        {
          echo "Processing version: ";
          print_r($oVersion);
        }

        if($oVersion->sQueued == "true")
        {
          $this->aVersions[] = $oVersion->objectGetId();
        } else // version isn't queued
        {
          // add the unqueued version to the list of versions to check for queued data
          $aVersionsToCheck[] = $oVersion->iVersionId;
        }
      }
    } else // just a normal maintainer
    {
      $aVersionsToCheck[] = $this->oMaintainer->iVersionId;
      if($bDebugOutputEnabled)
        echo "Normal maintainer of version ".$this->oMaintainer->iVersionId."\n";
    }

    // go through all of the verions to see what queued data they have
    foreach($aVersionsToCheck as $iVersionId)
    {
      if($bDebugOutputEnabled)
        echo "Processing iVersionId of ".$iVersionId."\n";
        
      //////////////////
      // queued testdata

      // retrieve queued testdata for this version
      $sQuery = "select * from testResults where versionId = '?' and queued = '?'";
      $hResult = query_parameters($sQuery, $iVersionId, "true");

      // go through the test results looking for the oldest queued data
      while($oTestingRow = mysql_fetch_object($hResult))
      {
        if($bDebugOutputEnabled)
          echo "\tQueued TestData found\n";
        $oTestData = new TestData(null, $oTestingRow);

        $this->aTestDataIds[] = $oTestData->objectGetId();
      }
      // queued testdata
      //////////////////


      ////////////////////
      // queued screenshots
      $sQuery = "select * from appData where type = 'screenshot' and versionId = '?' and queued = '?'";
      $hResult = query_parameters($sQuery, $iVersionId, "true");
      while($oScreenshotRow = mysql_fetch_object($hResult))
      {
        $oScreenshot = new Screenshot(null, $oScreenshotRow);

        $this->aScreenshotIds[] = $oScreenshot->objectGetId();
      }
      // queued screenshots
      //////////////////////
    }
  }
}

// contains the results of a notification update so other logic
// can act on these results
class notificationUpdate
{
  var $sEmail;
  var $sSubject;
  var $sMsg; // contents of the email we will send to the maintainer
  var $iTargetLevel; // the target notification level based upon the
                     // maintiners queued entries

  function notificationUpdate($sEmail, $sSubject, $sMsg, $iTargetLevel)
  {
    $this->sEmail = $sEmail;
    $this->sSubject = $sSubject;
    $this->sMsg = $sMsg;
    $this->iTargetLevel = $iTargetLevel;
  }
}


class maintainer
{
    var $iMaintainerId;
    var $iAppId;
    var $iVersionId;
    var $iUserId;
    var $sMaintainReason;
    var $bSuperMaintainer;
    var $aSubmitTime; //FIXME: should be 'sSubmitTime'
    var $bQueued; //FIXME: Should be sQueued
    var $sReplyText;

    // parameters used in the queued data notification system
    // that lets maintainers know that their applications/versions have
    // queued data for them to process
    var $iNotificationLevel; // the current warning level of this maintainer
    var $sNotificationTime; // the time when we last warned this maintainer

    function maintainer($iMaintainerId = null, $oRow = null)
    {
        // set a default notification level of 0
        $this->iNotificationLevel = 0;
      
        if(!$iMaintainerId && !$oRow)
            return;

        if(!$oRow)
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE maintainerId = '?'";
            $hResult = query_parameters($sQuery, $iMaintainerId);
            if($hResult)
                $oRow = mysql_fetch_object($hResult);
        }

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

            $this->iNotificationLevel = $oRow->notificationLevel;
            $this->sNotificationTime = $oRow->notificationTime;
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
        $hResult = query_parameters($sQuery, $this->iMaintainerId);

        if(!$hResult)
            return FALSE;

        return TRUE;
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

    function ObjectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0)
    {
        /* Not implemented */
        if($bRejected)
            return FALSE;

        $sLimit = "";

        /* Should we add a limit clause to the query? */
        if($iRows || $iStart)
        {
            $sLimit = " LIMIT ?,?";

            /* Selecting 0 rows makes no sense, so we assume the user wants to select all of them
               after an offset given by iStart */
            if(!$iRows)
                $iRows = maintainer::objectGetEntriesCount($bQueued, $bRejected);
        }

        /* Excluding requests for queued apps and versions, as these will be
           handled automatically */
        $sQuery = "(SELECT DISTINCT appMaintainers.* FROM 
            appMaintainers, appFamily WHERE
            appMaintainers.queued = '?'
            AND
            appMaintainers.superMaintainer = '1'
            AND
            appFamily.appId = appMaintainers.appId
            AND
            appFamily.queued = 'false') UNION
            (SELECT DISTINCT appMaintainers.* FROM
            appMaintainers, appVersion WHERE
            appMaintainers.queued = '?'
            AND
            appMaintainers.versionId = appVersion.versionId
            AND
            appMaintainers.superMaintainer = '0'
            AND
            appVersion.queued = 'false')$sLimit";

        if($bQueued)
        {
            if($_SESSION['current']->hasPriv("admin"))
            {
                if($sLimit)
                {
                    return query_parameters($sQuery, $bQueued ? "true" : "false",
                                            $bQueued ? "true" : "false",
                                            $iStart, $iRows);
                } else
                {
                    return query_parameters($sQuery, $bQueued ? "true" : "false",
                                            $bQueued ? "true" : "false");
                }
            } else
            {
                return NULL;
            }
        } else
        {
            if($sLimit)
            {
                return query_parameters($sQuery, $bQueued ? "true" : "false",
                                        $bQueued ? "true" : "false", $iStart, $iRows);
            } else
            {
                return query_parameters($sQuery, $bQueued ? "true" : "false",
                                        $bQueued ? "true" : "false");
            }
        }
    }

    /* retrieve a maintainer object from a row returned by */
    /* ObjectGetEntries() */
    function ObjectGetObjectFromObjectGetEntriesRow($oRow)
    {
        return new maintainer($oRow->maintainerId);
    }

    // returns the number of applications/versions a particular user maintains
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
       $sQuery = "(SELECT COUNT(DISTINCT maintainerId) as count FROM 
            appMaintainers, appFamily WHERE
            appMaintainers.queued = '?'
            AND
            appMaintainers.superMaintainer = '1'
            AND
            appFamily.appId = appMaintainers.appId
            AND
            appFamily.queued = 'false') UNION
            (SELECT COUNT(DISTINCT maintainerId) as count FROM
            appMaintainers, appVersion WHERE
            appMaintainers.queued = '?'
            AND
            appMaintainers.versionId = appVersion.versionId
            AND
            appMaintainers.superMaintainer = '0'
            AND
            appVersion.queued = 'false')";

        if(!($hResult = query_parameters($sQuery, $bQueued ? "true" : "false",
                                         $bQueued ? "true" : "false")))
            return FALSE;

        for($iCount = 0; $oRow = mysql_fetch_object($hResult);)
            $iCount += $oRow->count;

        return $iCount;
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
                WHERE
                    appMaintainers.queued = 'false'
                    AND
                    appVersion.versionId = '?'
                    AND
                    (
                        appMaintainers.versionId = appVersion.versionId
                        OR
                        (
                            appMaintainers.appId = appVersion.appId
                            AND superMaintainer = '1'
                        )
                    )", $iVersionId);
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

    function ObjectGetTableRow()
    {
        $oUser = new User($this->iUserId);
        $oApp = new Application($this->iAppId);
        $oVersion = new Version($this->iVersionId);

        $oTableRow = new TableRow();

        $oTableRow->AddTextCell(print_date(mysqldatetime_to_unixtimestamp($this->aSubmitTime)));
        $oTableRow->AddTextCell($oApp->objectMakeLink());
        $oTableRow->AddTextCell(($this->bSuperMaintainer) ? "N/A" : $oVersion->objectMakeLink());
        $oTableRow->AddTextCell(($this->bSuperMaintainer) ? "Yes" : "No");
        $oTableRow->AddTextCell($oUser->objectMakeLink());

        $oOMTableRow = new OMTableRow($oTableRow);
        return $oOMTableRow;
    }

    function ObjectDisplayQueueProcessingHelp()
    {
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a maintainer request is rejected.\n";
        echo "Its not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    
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

        /* User name */
        $oSubmitter = new user($this->iUserId);
        echo html_tr(array(
                array("<b>User name</b>", 'style="text-align:right" class="color0"'),
                $oSubmitter->objectMakeLink()
                          ));

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

                // because Version::getMaintainersUserIds() includes super maintainers
                // we need to exclude these from the list of maintainers that we are
                // building
                if(!maintainer::isUserSuperMaintainer($oUser, $oVersion->iAppId))
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

    function objectGetItemsPerPage($bQueued = false)
    {
        $aItemsPerPage = array(25, 50, 100, 200);
        $iDefaultPerPage = 25;
        return array($aItemsPerPage, $iDefaultPerPage);
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

    function allowAnonymousSubmissions()
    {
        return FALSE;
    }

    function fetchNotificationUpdate()
    {
      $bDebugOutputEnabled = false;

      if($bDebugOutputEnabled)
        echo "notifyMaintainerOfQueuedData()\n";

      // if a maintainer has an non-zero warning level
      // has it been enough days since we last warned them to warrent
      // checking to see if we should warn them again?
      if($this->iNotificationLevel != 0)
      {
        $iLastWarnTime = strtotime($this->sNotificationTime);
        $iLastWarnAgeInSeconds = strtotime("now") - $iLastWarnTime;
                                                                        
        if($bDebugOutputEnabled)
          echo "iLastWarnAgeInSeconds: ".$iLastWarnAgeInSeconds."\n";
 
        // if it hasn't been at least $iNotificationIntervalDays since the last
        // warning we can skip even checking the user
        if($iLastWarnAgeInSeconds < (iNotificationIntervalDays * 24 * 60 * 60))
        {
          if($bDebugOutputEnabled)
            echo "iNotificationIntervalDays has not elapsed, skipping checking the user\n";
          return null;
        }
      }

      if($bDebugOutputEnabled)
        echo "notification level is: ".$this->iNotificationLevel."\n";

      // instantiate the user so we can retrieve particular values
      $oUser = new User($this->iUserId);

      if($bDebugOutputEnabled)
      {
        echo "this->iUserId: ".$this->iUserId."\n";
        print_r($oUser);
      }

      // get the time the user signed up
      // if queued entries have been queued since before the user signed up
      // we can't punish them for this fact
      $iMaintainerSignupTime = strtotime($this->aSubmitTime);

      if($bDebugOutputEnabled)
        echo "iMaintainerSignupTime: ".$iMaintainerSignupTime."\n";

      // store the oldest queued entry
      $iOldestQueuedEntryTime = strtotime("now");

      // construct the subject of the notification email that we may send
      $sSubject = "Notification of queued data for ";
      $sMsg = "You are receiving this email to notify you that there is queued data";
      $sMsg.=" for the ";
      if($this->bSuperMaintainer)
      {
        $oApp = new Application($this->iAppId);
        $sSubject.= $oApp->sName;
        $sMsg.='application, '.$oApp->objectMakeLink().', that you maintain.'."\n";
      } else
      {
        $sFullname = version::fullName($this->iVersionId);
        $oVersion = new Version($this->iVersionId);
        $sSubject.= $sFullname;
        $sMsg.='version, <a href="'.$oVersion->objectMakeUrl().'">'.$sFullname.
          '</a>, that you maintain.'."\n";
      }
      $sSubject.=" ready for your processing";

      // retrieve the queued entries
      $oQueuedEntries = new queuedEntries($this);
      $oQueuedEntries->retrieveQueuedEntries();
      if($oQueuedEntries->isEmpty())
      {
        if($bDebugOutputEnabled)
          echo "No entries, returning\n";

        //no entries, we might as well return here
        return;
      }

      // process each of the queued versions
      foreach($oQueuedEntries->aVersionIds as $iVersionId)
      {
        $oVersion = new Version($iVersionId);
          
        $sMsg .= 'Version <a href="'.$oVersion->objectMakeUrl().'">'.$sFullname.
          '</a> is queued and ready for processing.';

        $iSubmitTime = strtotime($oVersion->sSubmitTime);

        // is this submission time older than the oldest queued time?
        // if so this is the new oldest time
        if($iSubmitTime < $iOldestQueuedEntryTime)
        {
          $iOldestQueuedEntryTime = $iSubmitTime;
        }
      }

      if(count($oQueuedEntries->aVersionIds) != 0)
      {
        // FIXME: should use a function to generate these urls and use it here and
        // in sidebar_maintainer.php and sidebar_admin.php
        $sMsg = 'Please visit <a href="'.BASE."objectManager.php?sClass=version_queue&bIsQueue=true&sTitle=".
          "Version%20Queue".'">AppDB Version queue</a> to process queued versions for applications you maintain.\n';
      }

      //////////////////
      // queued testdata

      // go through the test results looking for the oldest queued data
      foreach($oQueuedEntries->aTestDataIds as $iTestDataId)
      {
        if($bDebugOutputEnabled)
          echo "Testresult found\n";
        $oTestData = new TestData($iTestDataId);

        $iSubmitTime = strtotime($oTestData->sSubmitTime);
        if($bDebugOutputEnabled)
          echo "iSubmitTime is ".$iSubmitTime."\n";

        // is this submission time older than the oldest queued time?
        // if so this is the new oldest time
        if($iSubmitTime < $iOldestQueuedEntryTime)
        {
          if($bDebugOutputEnabled)
            echo "setting new oldest time\n";

          $iOldestQueuedEntryTime = $iSubmitTime;
        }
      }

      $iTestResultCount = count($oQueuedEntries->aTestDataIds);
      if($iTestResultCount != 0)
      {
        // grammar is slightly different for singular vs. plural
        if($iTestResultCount == 1)
          $sMsg.="There is $iTestResultCount queued test result. ";
        else
          $sMsg.="There are $iTestResultCount queued test results. ";

        // FIXME: should use a function to generate these urls and use it here and
        // in sidebar_maintainer.php and sidebar_admin.php
        $sMsg .= 'Please visit <a href="'.BASE."objectManager.php?sClass=testData_queue&bIsQueue=true&sTitle=".
          "Test%20Results%20Queue".'">AppDB Test Data queue</a> to process queued test data for versions you maintain.\r\n';
      }
      // queued testdata
      //////////////////


      ////////////////////
      // queued screenshots
      foreach($oQueuedEntries->aScreenshotIds as $iScreenshotId)
      {
        $oScreenshot = new Screenshot($iScreenshotId);

        $iSubmitTime = strtotime($oScreenshot->sSubmitTime);

        // is this submission time older than the oldest queued time?
        // if so this is the new oldest time
        if($iSubmitTime < $iOldestQueuedEntryTime)
        {
          $iOldestQueuedEntryTime = $iSubmitTime;
        }
      }

      // if the oldest queue entry time is older than the time the maintainer
      // signed up, use the maintainer signup time as the oldest time
      if($iOldestQueuedEntryTime < $iMaintainerSignupTime)
        $iOldestQueuedEntryTime = $iMaintainerSignupTime;


      // if we found any queued screenshots add the screenshot queue processing link
      // to the email
      if(count($oQueuedEntries->aScreenshotIds) != 0)
      {
        // FIXME: should use a function to generate these urls and use it here and
        // in sidebar_maintainer.php and sidebar_admin.php
        $sMsg .= 'Please visit <a href="'.BASE."objectManager.php?sClass=screenshot_queue&bIsQueue=true&sTitle=".
          "Screenshot%20Queue".'">Screenshot queue</a> to process queued screenshots for versions you maintain.\r\n';
      }
        
      // queued screenshots
      //////////////////////

      // compute the age in seconds of the oldest entry
      $iAgeInSeconds = strtotime("now") - $iOldestQueuedEntryTime;

      // compute the target warning level based on the age and the notification interval
      // we divide the age by the number of seconds in a day multiplied by the days per notification interval
      $iTargetLevel = (integer)($iAgeInSeconds / (iNotificationIntervalDays * 24 * 60 * 60));

      if($bDebugOutputEnabled)
      {
        echo "iOldestQueuedEntryTime is $iOldestQueuedEntryTime\n";
        echo "iAgeInSeconds is $iAgeInSeconds\n";
        echo "iNotificationIntervalDays is ".iNotificationIntervalDays."\n";
        echo "strtotime(now) is ".strtotime("now")."\n";
        echo "iTargetLevel is ".$iTargetLevel."\n";
      }

      $oNotificationUpdate = new notificationUpdate($oUser->sEmail, $sSubject,
                                                    $sMsg, $iTargetLevel);

      return $oNotificationUpdate;
    }

    // level 0 is from 0 to iNotificationIntervalDays
    // level 1 is from (iNotificationIntervalDays + 1) to (iNotificationIntervalDays * 2)
    // level 2 is from (iNotificationIntervalDays * 2 + 1) to (iNotificationIntervalDays * 3)
    // level 3 is beyond (iNotificationIntervalDays * 3)
    function processNotificationUpdate($oNotificationUpdate)
    {
      $bDebugOutputEnabled = false;

      // if the target level is less than the current level, adjust the current level
      // This takes into account the entries in the users queue that may have been processed
      if($oNotificationUpdate->iTargetLevel < $this->iNotificationLevel)
      {
        if($bDebugOutputEnabled)
          echo "Using iTargetLevel of $oNotificationUpdate->iTargetLevel\n";
        $this->iNotificationLevel = $oNotificationUpdate->iTargetLevel;
      }

      // if the target level is higher than the current level then adjust the
      // current level up by 1
      // NOTE: we adjust up by one because we want to ensure that we go through
      //       notification levels one at a time
      if($oNotificationUpdate->iTargetLevel > $this->iNotificationLevel)
      {
        if($bDebugOutputEnabled)
          echo "Increasing notification level of $this->iNotificationLevel by 1\n";
        $this->iNotificationLevel++;
      }

      switch($this->iNotificationLevel)
      {
      case 0: // lowest level, no notification
        // nothing to do here
        break;
      case 1: // send the first notification
        // nothing to do here, the first notification is just a reminder
        $oNotificationUpdate->sMsg.= "\n\nThanks,\n";
        $oNotificationUpdate->sMsg.= "Appdb Admins";
        break;
      case 2: // send the second notification, notify them that if the queued entries aren't
              // processed after another $iNotificationIntervalDays that
              // we'll have to remove their maintainership for this application/version
              // so a more active person can fill the spot
        $oNotificationUpdate->sMsg.= "\nThis your second notification of queued entries. If the queued entries are";
        $oNotificationUpdate->sMsg.= " not processed within the next ".iNotificationIntervalDays. "we will remove";
        $oNotificationUpdate->sMsg.= " your maintainership for this application/version so a more active person";
        $oNotificationUpdate->sMsg.= " can fill the spot.";
        $oNotificationUpdate->sMsg.= "\n\nThanks,\n";
        $oNotificationUpdate->sMsg.= "Appdb Admins";
        break;
      case 3: // remove their maintainership
        $this->delete(); // delete ourselves from the database
        break;
      }

      // save the notification level and notification time back into the database
      $sQuery = "update appMaintainers set notificationLevel = '?', notificationTime = ?".
        " where maintainerId = '?'";
      query_parameters($sQuery, $this->iNotificationLevel, "NOW()", $this->iMaintainerId);

      //TODO: we probably want to copy the mailing list on each of these emails
      $oNotificationUpdate->sEmail.=" cmorgan@alum.wpi.edu"; // FIXME: for debug append my email address

      if($this->iNotificationLevel == 0)
      {
        if($bDebugOutputEnabled)
          echo "At level 0, no warning issued to ".$oUser->sEmail."\n";
      } else
      {
        if($bDebugOutputEnabled)
        {
          echo "Email: ".$oNotificationUpdate->sEmail."\n";
          echo "Subject: ".$oNotificationUpdate->sSubject."\n";
          echo "Msg: ".$oNotificationUpdate->sMsg."\n\n";
        }

        mail_appdb($oNotificationUpdate->sEmail, $oNotificationUpdate->sSubject, $oNotificationUpdate->sMsg);
      }
    }

    function notifyMaintainerOfQueuedData()
    {
      $oNotificationUpdate = $this->fetchNotificationUpdate();

      // if we have a valid notificationUpdate then process it, otherwise skip it
      if($oNotificationUpdate != NULL)
        $this->processNotificationUpdate($oNotificationUpdate);
    }

    // static method called by the cron maintenance script to notify
    // maintainers of data pending for their applications and versions
    //TODO: php5 make this static when we have php5
    function notifyMaintainersOfQueuedData()
    {
      // retrieve all of the maintainers
      $hResult = maintainer::objectGetEntries(false, false);

      //      echo "Processing ".mysql_num_rows($hResult)." maintainers\n";

      // notify this user, the maintainer, of queued data, if any exists
      while($oRow = mysql_fetch_object($hResult))
      {
        $oMaintainer = new maintainer(null, $oRow);
        $oMaintainer->notifyMaintainerOfQueuedData();
      }
    }
}

?>
