<?php
/************************************/
/* user class and related functions */
/************************************/

require_once(BASE."include/version.php");
require_once(BASE."include/util.php");

define(SUCCESS, 0);
define(USER_CREATE_EXISTS, 1);
define(USER_CREATE_FAILED, 2);
define(USER_LOGIN_FAILED, 3);
define(USER_UPDATE_FAILED, 4);
define(USER_UPDATE_FAILED_EMAIL_EXISTS, 5); /* user updating to an email address that is already in use */
define(USER_UPDATE_FAILED_NOT_LOGGED_IN, 6); /* user::update() called but user not logged in */

/**
 * User class for handling users
 */
class User {
    var $iUserId;
    var $sEmail;
    var $sRealname;
    var $sStamp;
    var $sDateCreated;
    var $sWineRelease;
    var $bInactivityWarned;

    /**
     * Constructor.
     * If $iUserId is provided, logs in user.
     */
    function User($iUserId="")
    {
        $this->sRealname = "an anonymous user";
        if(is_numeric($iUserId))
        {
            $sQuery = "SELECT *
                       FROM user_list
                       WHERE userId = '?'";
            $hResult = query_parameters($sQuery, $iUserId);
            $oRow = mysql_fetch_object($hResult);
            $this->iUserId = $oRow->userid;
            $this->sEmail = $oRow->email;
            $this->sRealname = $oRow->realname;
            $this->sStamp = $oRow->stamp;
            $this->sDateCreated = $oRow->created;
            $this->sWineRelease = $oRow->CVSrelease;
            $this->bInactivityWarned = $oRow->inactivity_warned;
        }
        return $this->isLoggedIn();
    }


    /**
     * Logs in an user using e-mail and password.
     */
    function login($sEmail, $sPassword)
    {
        $sQuery = "SELECT *
                   FROM user_list
                   WHERE email = '?'
                   AND password = password('?')";
        $hResult = query_parameters($sQuery, $sEmail, $sPassword);

        $oRow = mysql_fetch_object($hResult);
        $this->iUserId = $oRow->userid;
        $this->sEmail = $oRow->email;
        $this->sRealname = $oRow->realname;
        $this->sStamp = $oRow->stamp;
        $this->sDateCreated = $oRow->created;
        $this->sWineRelease = $oRow->CVSrelease;
        if($this->isLoggedIn())
        {
            // Update timestamp and clear the inactivity flag if it was set
            query_parameters("UPDATE user_list SET stamp = ?, inactivity_warned = '?' WHERE userid='?'",
                             "NOW()", "false", $this->iUserId);
            return SUCCESS;
        }
        return USER_LOGIN_FAILED;
    }


    /*
     * Creates a new user.
     * returns SUCCESS on success, USER_CREATE_EXISTS if the user already exists
     */
    function create($sEmail, $sPassword, $sRealname, $sWineRelease)
    {
        if(user_exists($sEmail))
        {
            return USER_CREATE_EXISTS;
        } else
        {
            $hResult = query_parameters("INSERT INTO user_list (realname, email, CVSrelease, password, stamp,".
                                        "created) VALUES ('?', '?', '?', password('?'), ?, ?)",
                                        $sRealname, $sEmail, $sWineRelease, $sPassword, "NOW()", "NOW()");

            if(!$hResult) return USER_CREATE_FAILED;

            $retval = $this->login($sEmail, $sPassword);
            if($retval == SUCCESS)
                $this->setPref("comments:mode", "threaded"); /* set the users default comments:mode to threaded */

            return $retval;
        }
    }


    /**
     * Update User Account;
     */
    function update()
    {
        if(!$this->isLoggedIn()) return USER_UPDATE_FAILED_NOT_LOGGED_IN;

        /* create an instance of ourselves so we can see what has changed */
        $oUser = new User($this->iUserId);

        if($this->sEmail && ($this->sEmail != $oUser->sEmail))
        {
            /* make sure this email isn't already in use */
            if(user_exists($this->sEmail))
            {
                addMsg("An account with this e-mail exists already.","red");
                return USER_UPDATE_FAILED_EMAIL_EXISTS;
            }
            if (!query_parameters("UPDATE user_list SET email = '?' WHERE userid = '?'",
                                  $this->sEmail, $this->iUserId))
                return USER_UPDATE_FAILED;
        }

        if ($this->sRealname && ($this->sRealname != $oUser->sRealname))
        {
            if (!query_parameters("UPDATE user_list SET realname = '?' WHERE userid = '?'",
                             $this->sRealname, $this->iUserId))
                return USER_UPDATE_FAILED;
        }

        if ($this->sWineRelease && ($this->sWineRelease != $oUser->sWineRelease))
        {
            if (!query_parameters("UPDATE user_list SET CVSrelease = '?' WHERE userid = '?'",
                                  $this->sWineRelease, $this->iUserId))
                return USER_UPDATE_FAILED;
        }

        return SUCCESS;
    }

    /** 
     * NOTE: we can't update the users password like we can update other
     * fields such as their email or username because the password is hashed
     * in the database so we can't keep the users password in a class member variable
     * and use update() because we can't check if the password changed without hashing
     * the newly supplied one
     */
    function update_password($sPassword)
    {
        if($sPassword)
        {
            if (query_parameters("UPDATE user_list SET password = password('?') WHERE userid = '?'",
                                 $sPassword, $this->iUserId))
                return true;
        }

        return false;
    }


    /**
     * Removes the current, or specified user and preferences from the database.
     * returns true on success and false on failure.
     */
    function delete()
    {
        if(!$this->isLoggedIn()) return false;
        $hResult2 = query_parameters("DELETE FROM user_privs WHERE userid = '?'", $this->iUserId);
        $hResult3 = query_parameters("DELETE FROM user_prefs WHERE userid = '?'", $this->iUserId);
        $hResult4 = query_parameters("DELETE FROM appVotes WHERE userid = '?'", $this->iUserId);
        $hResult5 = query_parameters("DELETE FROM appMaintainers WHERE userid = '?'", $this->iUserId);
        $hResult6 = query_parameters("DELETE FROM appComments WHERE userId = '?'", $this->iUserId);
        return($hResult = query_parameters("DELETE FROM user_list WHERE userid = '?'", $this->iUserId));
    }


    /**
     * Get a preference for the current user.
     */
    function getPref($sKey, $sDef = null)
    {
        if(!$this->isLoggedIn() || !$sKey)
            return $sDef;

        $hResult = query_parameters("SELECT * FROM user_prefs WHERE userid = '?' AND name = '?'",
                                $this->iUserId, $sKey);
        if(!$hResult || mysql_num_rows($hResult) == 0)
            return $sDef;
        $ob = mysql_fetch_object($hResult);
        return $ob->value; 
    }


    /**
     * Set a preference for the current user.
     */
    function setPref($sKey, $sValue)
    {
        if(!$this->isLoggedIn() || !$sKey || !$sValue)
            return false;

        $hResult = query_parameters("DELETE FROM user_prefs WHERE userid = '?' AND name = '?'",
                                    $this->iUserId, $sKey);
        $hResult = query_parameters("INSERT INTO user_prefs (userid, name, value) VALUES".
                                    "('?', '?', '?')", $this->iUserId, $sKey, $sValue);
        return $hResult;
    }


    /**
     * Check if this user has $priv.
     */
    function hasPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        $hResult = query_parameters("SELECT * FROM user_privs WHERE userid = '?' AND priv  = '?'",
                                $this->iUserId, $sPriv);
        if(!$hResult)
            return false;
        return mysql_num_rows($hResult);
    }


    /**
     * Check if this user is a maintainer of a given appId/versionId.
     */
    function isMaintainer($iVersionId=null)
    {
        if(!$this->isLoggedIn()) return false;

        /* if we are a super maintainer, we are a maintainer of this version as well */
        $oVersion = new Version($iVersionId);
        if($this->isSuperMaintainer($oVersion->iAppId))
            return true;

        /* otherwise check if we maintain this specific version */
        if($iVersionId)
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND versionId = '?'";
            $hResult = query_parameters($sQuery, $this->iUserId, $iVersionId);
        } else // are we maintaining any version ?
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?'";
            $hResult = query_parameters($sQuery, $this->iUserId);
        }
        if(!$hResult)
            return false;

        return mysql_num_rows($hResult);
    }


    /*
     * Check if this user is a maintainer of a given appId/versionId.
     */
    function isSuperMaintainer($iAppId=null)
    {
        if(!$this->isLoggedIn()) return false;

        if($iAppId)
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND appId = '?' AND superMaintainer = '1'";
            $hResult = query_parameters($sQuery, $this->iUserId, $iAppId);
        } else /* are we super maintainer of any applications? */
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '?' AND superMaintainer = '1'";
            $hResult = query_parameters($sQuery, $this->iUserId);
        }
        if(!$hResult)
            return false;
        return mysql_num_rows($hResult);
    }

    function getMaintainerCount($bSuperMaintainer)
    {
        if(!$this->isLoggedIn()) return 0;

        $sQuery = "SELECT count(*) as cnt from appMaintainers WHERE userid = '?' AND superMaintainer = '?'";
        $hResult = query_parameters($sQuery, $this->iUserId, $bSuperMaintainer);
        if(!$hResult)
            return 0;
        $ob = mysql_fetch_object($hResult);
        return $ob->cnt;
    }


     /**
      * Add the user as a maintainer
      */
    function addAsMaintainer($iAppId, $iVersionId, $bSuperMaintainer, $iQueueId)
     {

         $aClean = array();
         $aClean['replyText'] = makeSafe($_REQUEST['replyText']);

         /* if the user isn't already a supermaintainer of the application and */
         /* if they are trying to become a maintainer and aren't already a maintainer of */
         /* the version, then continue processing the request */
         if(!$this->isSuperMaintainer($iAppId) &&
            ((!$bSuperMaintainer && !$this->isMaintainer($iVersionId)) | $bSuperMaintainer))
         {
             // insert the new entry into the maintainers list
             $hResult = query_parameters("INSERT INTO appMaintainers (maintainerId, appId,".
                                         "versionId, userId, superMaintainer, submitTime) ".
                                         "VALUES (?, '?', '?', '?', '?', ?)",
                                         "null", $iAppId, $iVersionId, $this->iUserId,
                                         $bSuperMaintainer, "NOW()");
            if($hResult)
            {
                $statusMessage = "<p>The maintainer was successfully added into the database</p>\n";

                //delete the item from the queue
                query_parameters("DELETE from appMaintainerQueue where queueId = '?'", $iQueueId);
                $oApp = new Application($iAppId);
                $oVersion = new Version($iVersionId);
                //Send Status Email
                $sEmail = $oUser->sEmail;
                if ($sEmail)
                {
                    $sSubject =  "Application Maintainer Request Report";
                    $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." has been accepted. ";
                    $sMsg .= $aClean['replyText'];
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\n\n";

                    mail_appdb($sEmail, $sSubject ,$sMsg);
                }       
            }
        } else
        {
            //delete the item from the queue
            query_parameters("DELETE from appMaintainerQueue where queueId = '?'", $iQueueId);

            if($this->isSuperMaintainer($iAppId) && !$bSuperMaintainer)
                $statusMessage = "<p>User is already a super maintainer of this application</p>\n";
            else
                $statusMessage = "<p>User is already a maintainer/super maintainer of this application/version</p>\n";
        }

        return $statusMessage;
     }

    /* remove maintainership */
    /* if $iAppId and $iVersionId are null, delete all maintainership for this user */
    function deleteMaintainer($iAppId = null, $iVersionId = null)
    {
        /* remove supermaintainer */
        if($iAppId && ($iVersionId == null))
        {
            $superMaintainer = 1;
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'
                                         AND appId = '?' AND superMaintainer = '?'",
                                        $this->iUserId, $iAppId, $superMaintainer);
        } else if($iAppId && $iVersionId) /* remove a normal maintainer */
        {
            $superMaintainer = 0;
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'
                                         AND appId = '?' AND versionId = '?' AND superMaintainer = '?'",
                                        $this->iUserId, $iAppId, $iVersionId, $superMaintainer);
        } else if(($iAppId == null) && ($iVersionId == null)) /* remove all maintainership by this user */
        {
            $hResult = query_parameters("DELETE FROM appMaintainers WHERE userId = '?'", $this->iUserId);
        }

        if($hResult)
            return true;
        
        return false;
    }

    /* get the number of queued applications */
    function getQueuedAppCount()
    {
        /* return 0 because non-admins have no way to process new apps */
        if(!$this->hasPriv("admin"))
            return 0;

        $sQuery = "SELECT count(*) as queued_apps FROM appFamily WHERE queued='true'";
        $hResult = query_parameters($sQuery);
        $oRow = mysql_fetch_object($hResult);
        return $oRow->queued_apps;
    }

    function getQueuedVersionCount()
    {
        if($this->hasPriv("admin"))
        {
            $hResult = query_parameters("SELECT count(*) as queued_versions FROM appVersion WHERE queued='true'");
        } else
        {
            /* find all queued versions of applications that the user is a super maintainer of */
            $hResult = query_parameters("SELECT count(*) as queued_versions FROM appVersion, appMaintainers
                        WHERE queued='true' AND appMaintainers.superMaintainer ='1'
                        AND appVersion.appId = appMaintainers.appId
                        AND appMaintainers.userId ='?'", $this->iUserId);
        }

        $oRow = mysql_fetch_object($hResult);

        /* we don't want to count the versions that are implicit in the applications */
        /* that are in the queue */
        return $oRow->queued_versions - $this->getQueuedAppCount();
    }


    /* get the number of queued appdata */
    function getQueuedAppDataCount()
    {
        $hResult = $this->getAppDataQuery(0, true, false);
        $oRow = mysql_fetch_object($hResult);
        return $oRow->queued_appdata;
    }

    function addPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        if($this->hasPriv($sPriv))
            return true;

        $hResult = query_parameters("INSERT INTO user_privs (userid, priv) VALUES".
                                    " ('?', '?')", $this->iUserId, $sPriv);
        return $hResult;
    }


    function delPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        $hResult = query_parameters("DELETE FROM user_privs WHERE userid = '?' AND priv = '?'",
                                 $this->iUserId, $sPriv);
        return $hResult;
    }


    /**
     * Checks if the current user is valid.
     */
    function isLoggedIn()
    {
        return $this->iUserId;
    }


    /**
     * Checks if user should see debugging infos.
     */
     function showDebuggingInfos()
     {
         return (($this->isLoggedIn() && $this->getPref("debug") == "yes") || APPDB_DEBUG == 1);
     }


    /**
     * Checks if user wants to get e-mails.
     */    
     function wantsEmail()
     {
         return ($this->isLoggedIn() && $this->getPref("send_email","yes")=="yes");
     }

     /**
      * Return an app query based on the user permissions and an iAppDataId
      * Used to display appropriate appdata entries based upon admin vs. maintainer
      * as well as to determine if the maintainer has permission to delete an appdata entry
      */
     function getAppDataQuery($iAppDataId, $queryQueuedCount, $queryQueued)
     {
         /* escape all of the input variables */
         /* code is too complex to easily use query_parameters() */
         $iAppDataId = mysql_real_escape_string($iAppDataId);
         $queryQueuedCount = mysql_real_escape_string($queryQueuedCount);
         $queryQueued = mysql_real_escape_string($queryQueued);

         /* either look for queued app data entries */
         /* or ones that match the given id */
         if($queryQueuedCount)
         {
             $selectTerms = "count(*) as queued_appdata";
             $additionalTerms = "AND appData.queued='true'";
         } else if($queryQueued)
         {
             $selectTerms = "appData.*, appVersion.appId AS appId";
             $additionalTerms = "AND appData.queued='true'";
         } else
         {
             $selectTerms = "appData.*, appVersion.appId AS appId";
             $additionalTerms = "AND id='".$iAppDataId."'";
         }

         if($this->hasPriv("admin"))
         {
             $sQuery = "SELECT ".$selectTerms."
               FROM appData,appVersion 
               WHERE appVersion.versionId = appData.versionId 
               ".$additionalTerms.";";
         } else
         {
             /* select versions where we supermaintain the application or where */
             /* we maintain the appliation, and where the versions we supermaintain */
             /* or maintain are in the appData list */
             /* then apply some additional terms */
             $sQuery = "select ".$selectTerms." from appMaintainers, appVersion, appData where
                        (
                         ((appMaintainers.appId = appVersion.appId) AND
                          (appMaintainers.superMaintainer = '1'))
                         OR
                          ((appMaintainers.versionId = appVersion.versionId)
                           AND (appMaintainers.superMaintainer = '0'))
                        )
                        AND appData.versionId = appVersion.versionId
                        AND appMaintainers.userId = '".mysql_real_escape_string($this->iUserId)."'
                        ".$additionalTerms.";";
         }

         return query_appdb($sQuery);
     }

     /**
      * Delete appData
      */
     function deleteAppData($iAppDataId)
     {
         if(!$_SESSION['current']->canDeleteAppDataId($iAppDataId))
             return false;

         $hResult = query_parameters("DELETE from appData where id = '?' LIMIT 1",
                                 $iAppDataId);
         if($hResult)
             return true;

         return false;
     }

     /**
      * Retrieve the list of applications in the app queue that this user can see
      */
     function getAppQueueQuery($queryAppFamily)
     {
         /* escape input as we can't easily use query_parameters() */
         $queryAppFamily = mysql_real_escape_string($queryAppFamily);

         if($this->hasPriv("admin"))
         {
             if($queryAppFamily)
             {
                 $sQuery = "SELECT appFamily.appId FROM appFamily WHERE queued = 'true'";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'true'";
             }
         } else
         {
             if($queryAppFamily)
             {
                 $sQuery = "SELECT appFamily.appId FROM appFamily, appMaintainers
                            WHERE queued = 'true'
                            AND appFamily.appId = appMaintainers.appId
                            AND appMaintainers.superMaintainer = '1'
                            AND appMaintainers.userId = '".mysql_real_escape_string($this->iUserId)."';";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily, appMaintainers
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'true'
                            AND appFamily.appId = appMaintainers.appId
                            AND appMaintainers.superMaintainer = '1'
                            AND appMaintainers.userId = '".mysql_real_escape_string($this->iUserId)."';";
             }
         }

         return query_appdb($sQuery);
     }

     function getAppRejectQueueQuery($queryAppFamily)
     {
         /* escape input as we can't easily use query_parameters() */
         $queryAppFamily = mysql_real_escape_string($queryAppFamily);

         if($this->hasPriv("admin"))
         {
             if($queryAppFamily)
             {
                 $sQuery = "SELECT appFamily.appId FROM appFamily WHERE queued = 'rejected'";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'rejected'";
             }
         } else
         {
             if($queryAppFamily)
             {
                 $sQuery = "SELECT appFamily.appId FROM appFamily
                            WHERE queued = 'rejected'
                            AND appFamily.submitterId = '".mysql_real_escape_string($this->iUserId)."';";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'rejected'
                            AND appVersion.submitterId = '".mysql_real_escape_string($this->iUserId)."';";
             }
         }

         return query_appdb($sQuery);
     }

     function getAllRejectedApps()
     {
         $hResult = query_parameters("SELECT appVersion.versionId, appFamily.appId 
                               FROM appVersion, appFamily
                               WHERE appFamily.appId = appVersion.appId 
                               AND (appFamily.queued = 'rejected' OR appVersion.queued = 'rejected')
                               AND appVersion.submitterId = '?'",
                                     $this->iUserId);

         if(!$hResult || mysql_num_rows($hResult) == 0)
             return;

         $retval = array();
         $c = 0;
         while($oRow = mysql_fetch_object($hResult))
         {
             $retval[$c] = array($oRow->appId, $oRow->versionId);
             $c++;
         }

         return $retval;
     }

     function isAppSubmitter($iAppId)
     {
         $hResult = query_parameters("SELECT appId FROM appFamily
                                      WHERE submitterId = '?'
                                      AND appId = '?'",
                                     $this->iUserId, $iAppId);
         if(mysql_num_rows($hResult))
             return true;
         else
             return false;
     }

     function isVersionSubmitter($iVersionId)
     {
         $hResult = query_parameters("SELECT appVersion.versionId FROM appVersion, appFamily
                                  WHERE appFamily.appId = appVersion.appId 
                                  AND appVersion.submitterId = '?'
                                  AND appVersion.versionId = '?'",
                                 $this->iUserId, $iVersionId);
         if(mysql_num_rows($hResult))
             return true;
         else
             return false;
    }

     /* if this user has data associated with them we will return true */
     /* otherwise we return false */
     function hasDataAssociated()
     {
         $hResult = query_parameters("SELECT count(userId) as c FROM appComments WHERE userId = '?'",
                                 $this->iUserId);
         $ob = mysql_fetch_object($hResult);
         if($ob->c != 0) return true;

         $hResult = query_parameters("SELECT count(userId) as c FROM appMaintainers WHERE userId = '?'",
                                 $this->iUserId);
         $ob = mysql_fetch_object($hResult);
         if($ob->c != 0) return true;

         $hResult = query_parameters("SELECT count(userId) as c FROM appVotes WHERE userId = '?'",
                                 $this->iUserId);
         $ob = mysql_fetch_object($hResult);
         if($ob->c != 0) return true;

         return false;
     }

     /* warn the user that their account has been marked as inactive */
     function warnForInactivity()
     {
         /* we don't want to warn users that have data associated with them */
         if($this->hasDataAssociated())
         {
             return false;
         }

         if($this->isMaintainer())
         {
             $sSubject  = "Warning: inactivity detected";
             $sMsg  = "You didn't log in in the past six months to the AppDB.\r\n";
             $sMsg .= "As a maintainer we would be pleased to see you once in a while.\r\n";
             $sMsg .= "Please log in or you will lose your maintainer's abilities in one month.\r\n";
         } else
         {
             $sSubject  = "Warning: inactivity detected";
             $sMsg  = "You didn't log in in the past six months to the AppDB.\r\n";
             $sMsg .= "Please log in or your account will automatically be deleted in one month.\r\n";
         }
         $sMsg .= APPDB_ROOT."account.php?cmd=login\r\n";

         mail_appdb($this->sEmail, $sSubject, $sMsg);

         /* mark this user as being inactive and set the appropriate timestamp */
         $sQuery = "update user_list set inactivity_warned='true', inactivity_warn_stamp=NOW() where userid='?'";
         query_parameters($sQuery, $this->iUserId);

         return true;
     }






     /************************/
     /* Permission functions */
     /************************/

     function canDeleteCategory($oCategory)
     {
        if($this->hasPriv("admin"))
            return true;

        return false;
     }

     /**
      * Returns true or false depending on whether the user can view the image
      */
     function canViewImage($iImageId)
     {
         $oScreenshot = new Screenshot($iImageId);

         if(!$oScreenshot->bQueued ||
            ($oScreenshot->bQueued && ($this->hasPriv("admin") ||
                                       $this->isMaintainer($oScreenshot->iVersionId) ||
                                       $this->isSuperMaintainer($oScreenshot->iAppId))))
             return true;

         return false;
     }

     function canDeleteAppDataId($iAppDataId)
     {
         /* admins can delete anything */
         if($this->hasPriv("admin"))
             return true;

         $isMaintainer = false;

         /* if we aren't an admin we should see if we can find any results */
         /* for a query based on this appDataId, if we can then */
         /* we have permission to delete the entry */
         $hResult = $this->getAppDataQuery($iAppDataId, false, false);
         if(!$hResult)
             return false;

         if(mysql_num_rows($hResult) > 0)
             $isMaintainer = true;

         /* if this user maintains the app data, they can delete it */
         if($isMaintainer)
             return true;

         return false;
     }

     /***************************/
     /* application permissions */
     function canViewApplication($oApp)
     {
         /* if the application isn't queued */
         if($oApp->sQueued == 'false')
             return true;

         if($this->hasPriv("admin"))
             return true;

         /* if this user is the submitter and the application is queued */
         if(($this->iUserId == $oApp->iSubmitterId) &&
            ($oApp->sQueued != 'false'))
             return true;

         return false;
     }

     /**
      * Does the user have permission to modify this application?
      */
     function canModifyApplication($oApp)
     {
         if($this->hasPriv("admin"))
             return true;

         /* is this user a super maintainer of this app? */
         if($this->isSuperMaintainer($oApp->iAppId))
             return true;
         
         /* if the user is the submitter of the application */
         /* and the application is still queued */
         /* the user can modify the app */
         if(($this->iUserId == $oApp->iSubmitterId) &&
            ($oApp->sQueued != 'false'))
             return true;

         return false;
     }

     /**
      * Can this user create applications?
      */
     function canCreateApplication()
     {
         return $this->isLoggedIn();
     }

     /**
      * Returns 'true' if the current user has the permission to delete
      * this application, 'false' otherwise
      */
     function canDeleteApplication($oApp)
     {
         if($this->hasPriv("admin"))
             return true;

         /* is this the user that submitted the application and is still queued */
         if(($oApp->sQueued != 'false') && ($oApp->iSubmitterId == $this->iUserId))
             return true;

         return false;
     }

     /* Can this user unQueue applications? */
     function canUnQueueApplication()
     {
         return $this->hasPriv("admin");
     }

     /* Can this user Requeue an application? */
     function canRequeueApplication($oApp)
     {
         if($oApp->sQueued == 'false')
             return false;

         if($this->hasPriv("admin"))
             return true;

         if(($oApp->sQueued != 'false') && ($oApp->iSubmitterId == $this->iUserId))
             return true;

         return false;
     }

     /* Can the user reject application? */
     function canRejectApplication()
     {
         return $this->hasPriv("admin");
     }

     /**
      * Does the created application have to be queued for admin processing?
      */
     function appCreatedMustBeQueued()
     {
         return !$this->hasPriv("admin");
     }


     /***********************/
     /* version permissions */

     function canViewVersion($oVersion)
     {
         /* if the version isn't queued */
         if($oVersion->sQueued == 'false')
             return true;

         if($this->hasPriv("admin"))
             return true;

         /* if the user is the submitter and the version is still queued */
         if(($this->iUserId == $oVersion->iSubmitterId) &&
            ($oVersion->sQueued != 'false'))
             return true;

         /* if this user supermaintains the application this version belongs to */
         if($this->isSupermaintainer($oVersion->iAppId))
             return true;

         return false;
     }

     /**
      * Does the user have permission to modify on this version?
      */
     function hasAppVersionModifyPermission($oVersion)
     {
         if(!$this->isLoggedIn())
             return false;

         if($this->hasPriv("admin"))
             return true;

         if($this->isSuperMaintainer($oVersion->iAppId))
             return true;

         if($this->isMaintainer($oVersion->iVersionId))
             return true;

         /* the version is queued and the user is the submitter */
         if(($oVersion->sQueued != 'false') && ($this->iUserId == $oVersion->iSubmitterId))
             return true;

         return false;
     }

     /**
      * Can this user create a version?
      */
     function canCreateVersion()
     {
         return $this->isLoggedIn();
     }

     function versionCreatedMustBeQueued($oVersion)
     {
         if($this->hasPriv("admin"))
             return false;

         if($this->isSupermaintainer($oVersion->iAppId))
             return false;

         return true;
     }


     /**
      * Returns 'true' if the current user has the permission to delete
      * this version, 'false' otherwise
      */
     function canDeleteVersion($oVersion)
     {
         if($this->hasPriv("admin"))
             return true;

         /* if the app is anything other than not queued and if the user is the submitter */
         /* then allow the user to delete the app */
         if(($oVersion->sQueued != 'false') && ($oVersion->iSubmitterId == $this->iUserId))
             return true;
         
         /* is this user a supermaintainer of the application this version is under? */
         if($this->isSuperMaintainer($oVersion->iAppId))
             return true;

         return false;
     }

     /**
      * Can the user unqueue this version?
      */
     function canUnQueueVersion($oVersion)
     {
         if($this->hasPriv("admin"))
             return true;
         
         if($this->hasAppVersionModifyPermission($oVersion))
             return true;

         return false;
     }

     /**
      * Can the user reject this version?
      */
     function canRejectVersion($oVersion)
     {
         if($this->hasPriv("admin"))
             return true;

         if($this->hasAppVersionModifyPermission($oVersion))
             return true;

         return false;
     }

     /**
      * Can the user reject this version?
      */
     function canRequeueVersion($oVersion)
     {
         if($this->hasPriv("admin"))
             return true;

         if($this->hasAppVersionModifyPermission($oVersion))
             return true;

         if(($this->iUserId == $oVersion->iSubmitterId) &&
            ($oVersion->sQueued != 'false'))
             return true;

         return false;
     }
}


/*
 * User functions that are not part of the class
 */

/**
 * Creates a new random password.
 */
function generate_passwd($pass_len = 10)
{
    $nps = "";
    mt_srand ((double) microtime() * 1000000);
    while (strlen($nps)<$pass_len)
    {
        $c = chr(mt_rand (0,255));
        if (eregi("^[a-z0-9]$", $c)) $nps = $nps.$c;
    }
    return ($nps);
}


/**
 * Get the email address of people to notify for this appId and versionId.
 */
function get_notify_email_address_list($iAppId = null, $iVersionId = null)
{
    $aUserId = array();
    $c = 0;
    $retval = "";

    /*
     * Retrieve version maintainers.
     */
    /*
     * If versionId was supplied we fetch supermaintainers of application and maintainer of version.
     */
    if($iVersionId)
    {
        $hResult = query_parameters("SELECT appMaintainers.userId 
                                 FROM appMaintainers, appVersion
                                 WHERE appVersion.appId = appMaintainers.appId 
                                 AND appVersion.versionId = '?'",
                                $iVersionId);
    } 
    /*
     * If versionId was not supplied we fetch supermaintainers of application and maintainer of all versions.
     */
    elseif($iAppId)
    {
        $hResult = query_parameters("SELECT userId 
                                 FROM appMaintainers
                                 WHERE appId = '?'",
                                $iAppId);
    }

    if($hResult)
    {
        if(mysql_num_rows($hResult) > 0)
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aUserId[$c] = array($oRow->userId);
                $c++;
            }
        }
    }

    /*
     * Retrieve version Monitors.
     */
    /*
     * If versionId was supplied we fetch superMonitors of application and Monitors of version.
     */
    if($iVersionId)
    {
        $hResult = query_parameters("SELECT appMonitors.userId 
                                 FROM appMonitors, appVersion
                                 WHERE appVersion.appId = appMonitors.appId 
                                 AND appVersion.versionId = '?'",
                                $iVersionId);
    } 
    /*
     * If versionId was not supplied we fetch superMonitors of application and Monitors of all versions.
     */
    elseif($iAppId)
    {
        $hResult = query_parameters("SELECT userId 
                                 FROM appMonitors
                                 WHERE appId = '?'",
                                $iAppId);
    }
    if($hResult)
    {
        if(mysql_num_rows($hResult) > 0)
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aUserId[$c] = array($oRow->userId);
                $c++;
            }
        }
    }

    /*
     * Retrieve administrators.
     */
    $hResult = query_parameters("SELECT * FROM user_privs WHERE priv  = 'admin'");
    if(mysql_num_rows($hResult) > 0)
    {
        while($oRow = mysql_fetch_object($hResult))
        {
            $i = array_search($oRow->userid, $aUserId);
            if ($aUserId[$i] != array($oRow->userid))
            {
                $aUserId[$c] = array($oRow->userid);
                $c++;
            }
        }
    }
    if ($c > 0)
    {
        while(list($index, list($userIdValue)) = each($aUserId))
        {
            $oUser = new User($userIdValue);
            if ($oUser->wantsEmail())
                $retval .= $oUser->sEmail." ";
        }
    }
    return $retval;
}


/**
 * Get the number of users in the database 
 */
function get_number_of_users()
{
    $hResult = query_parameters("SELECT count(*) as num_users FROM user_list;");
    $oRow = mysql_fetch_object($hResult);
    return $oRow->num_users;
}


/**
 * Get the number of active users within $days of the current day
 */
function get_active_users_within_days($days)
{
    $hResult = query_parameters("SELECT count(*) as num_users FROM user_list WHERE stamp >= DATE_SUB(CURDATE(), interval '?' day);",
                                $days);
    $oRow = mysql_fetch_object($hResult);
    return $oRow->num_users;
}


/**
 * Get the count of users who have been warned for inactivity and are
 * pending deletion after the X month grace period
 */
function get_inactive_users_pending_deletion()
{
    /* retrieve the number of users that have been warned and are pending deletion */
    $hResult = query_parameters("select count(*) as count from user_list where inactivity_warned = 'true'");
    $oRow = mysql_fetch_object($hResult);
    return $oRow->count;
}

/**
 * Check if a user exists.
 * returns the userid if the user exists
 */
function user_exists($sEmail)
{
    $hResult = query_parameters("SELECT userid FROM user_list WHERE email = '?'",
                                $sEmail);
    if(!$hResult || mysql_num_rows($hResult) != 1)
    {
        return 0;
    } else
    {
        $oRow = mysql_fetch_object($hResult);
        return $oRow->userid;
    }
}
?>
