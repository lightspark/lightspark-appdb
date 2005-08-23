<?php
/************************************/
/* user class and related functions */
/************************************/

require_once(BASE."include/version.php");

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
                       WHERE userId = '".$iUserId."'";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);
            $this->iUserId = $oRow->userid;
            $this->sEmail = $oRow->email;
            $this->sRealname = $oRow->realname;
            $this->sStamp = $oRow->stamp;
            $this->sDateCreated = $oRow->created;
            $this->sWineRelease = $oRow->CVSrelease;
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
                   WHERE email = '".addslashes($sEmail)."'
                   AND password = password('".addslashes($sPassword)."')";
        $hResult = query_appdb($sQuery);
        $oRow = mysql_fetch_object($hResult);
        $this->iUserId = $oRow->userid;
        $this->sEmail = $oRow->email;
        $this->sRealname = $oRow->realname;
        $this->sStamp = $oRow->stamp;
        $this->sDateCreated = $oRow->created;
        $this->sWineRelease = $oRow->CVSrelease;
        if($this->isLoggedIn())
        {
            // Update timestamp
            query_appdb("UPDATE user_list SET stamp=null WHERE userid=".$this->iUserId);
            return true;
        }
        return false;
    }


    /*
     * Creates a new user.
     * returns true on success, false on failure
     */
    function create($sEmail, $sPassword, $sRealname, $sWineRelease)
    {
        if(user_exists($sEmail))
        {
            addMsg("An account with this e-mail exists already.","red");
            return false;
        } else
        {
            $aInsert = compile_insert_string(array( 'realname' => $sRealname,
                                                    'email' => $sEmail,
                                                    'CVSrelease' => $sWineRelease ));

            $sFields = "({$aInsert['FIELDS']}, `password`, `stamp`, `created`)";
            $sValues = "({$aInsert['VALUES']}, password('".$sPassword."'), NOW(), NOW() )";

            query_appdb("INSERT INTO user_list $sFields VALUES $sValues", "Error while creating a new user.");

            $retval = $this->login($sEmail, $sPassword);
            $this->setPref("comments:mode", "threaded"); /* set the users default comments:mode to threaded */

            return $retval;
        }
    }


    /**
     * Update User Account;
     */
    function update($sEmail = null, $sPassword = null, $sRealname = null, $sWineRelease = null)
    {
        if(!$this->isLoggedIn()) return false;

        if ($sEmail)
        {
            if(user_exists($sEmail) && $sEmail != $this->sEmail)
            {
                addMsg("An account with this e-mail exists already.","red");
                return false;
            }
            if (!query_appdb("UPDATE user_list SET email = '".addslashes($sEmail)."' WHERE userid = ".$this->iUserId))
                return false;
            $this->sEmail = $sEmail;
        }

        if ($sPassword)
        {
            if (!query_appdb("UPDATE user_list SET password = password('$sPassword') WHERE userid = ".$this->iUserId))
                return false;
        }

        if ($sRealname)
        {
            if (!query_appdb("UPDATE user_list SET realname = '".addslashes($sRealname)."' WHERE userid = ".$this->iUserId))
                return false;
            $this->sRealname = $sRealname;
        }

        if ($sWineRelease)
        {
            if (!query_appdb("UPDATE user_list SET CVSrelease = '".addslashes($sWineRelease)."' WHERE userid = ".$this->iUserId))
                return false;
            $this->sWineRelease = $sWineRelease;
        }
        return true;
    }


    /**
     * Removes the current, or specified user and preferences from the database.
     * returns true on success and false on failure.
     */
    function delete()
    {
        if(!$this->isLoggedIn()) return false;
        $hResult2 = query_appdb("DELETE FROM user_privs WHERE userid = '".$this->iUserId."'");
        $hResult3 = query_appdb("DELETE FROM user_prefs WHERE userid = '".$this->iUserId."'");
        $hResult4 = query_appdb("DELETE FROM appVotes WHERE userid = '".$this->iUserId."'");
        $hResult5 = query_appdb("DELETE FROM appMaintainers WHERE userid = '".$this->iUserId."'");
        $hResult6 = query_appdb("DELETE FROM appComments WHERE userId = '".$this->iUserId."'");
        return($hResult = query_appdb("DELETE FROM user_list WHERE userid = '".$this->iUserId."'"));
    }


    /**
     * Get a preference for the current user.
     */
    function getPref($sKey, $sDef = null)
    {
        if(!$this->isLoggedIn() || !$sKey)
            return $sDef;

        $hResult = query_appdb("SELECT * FROM user_prefs WHERE userid = ".$this->iUserId." AND name = '$sKey'");
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

        $hResult = query_appdb("DELETE FROM user_prefs WHERE userid = ".$this->iUserId." AND name = '$sKey'");
        $hResult = query_appdb("INSERT INTO user_prefs VALUES(".$this->iUserId.", '$sKey', '$sValue')");
        return $hResult;
    }


    /**
     * Check if this user has $priv.
     */
    function hasPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        $hResult = query_appdb("SELECT * FROM user_privs WHERE userid = ".$this->iUserId." AND priv  = '".$sPriv."'");
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
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '".$this->iUserId."' AND versionId = '$iVersionId'";
        } else // are we maintaining any version ?
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '".$this->iUserId."'";
        }
        $hResult = query_appdb($sQuery);
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
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '$this->iUserId' AND appId = '$iAppId' AND superMaintainer = '1'";
        } else /* are we super maintainer of any applications? */
        {
            $sQuery = "SELECT * FROM appMaintainers WHERE userid = '$this->iUserId' AND superMaintainer = '1'";
        }
        $hResult = query_appdb($sQuery);
        if(!$hResult)
            return false;
        return mysql_num_rows($hResult);
    }

     /**
      * Add the user as a maintainer
      */
    function addAsMaintainer($iAppId, $iVersionId, $bSuperMaintainer, $iQueueId)
     {
         /* if the user isn't already a supermaintainer of the application and */
         /* if they are trying to become a maintainer and aren't already a maintainer of */
         /* the version, then continue processing the request */
         if(!$this->isSuperMaintainer($iAppId) &&
            ((!$bSuperMaintainer && !$this->isMaintainer($iVersionId)) | $bSuperMaintainer))
         {
            // insert the new entry into the maintainers list
            $sQuery = "INSERT into appMaintainers VALUES(null,".
                "$iAppId,".
                "$iVersionId,".
                "$this->iUserId,".
                "$bSuperMaintainer,".
                "NOW());";

            if (query_appdb($sQuery))
            {
                $statusMessage = "<p>The maintainer was successfully added into the database</p>\n";

                //delete the item from the queue
                query_appdb("DELETE from appMaintainerQueue where queueId = ".$iQueueId.";");
                $oApp = new Application($iAppId);
                $oVersion = new Version($iVersionId);
                //Send Status Email
                $sEmail = $oUser->sEmail;
                if ($sEmail)
                {
                    $sSubject =  "Application Maintainer Request Report";
                    $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." has been accepted. ";
                    $sMsg .= $_REQUEST['replyText'];
                    $sMsg .= "We appreciate your help in making the Application Database better for all users.\n\n";

                    mail_appdb($sEmail, $sSubject ,$sMsg);
                }       
            }
        } else
        {
            //delete the item from the queue
            query_appdb("DELETE from appMaintainerQueue where queueId = ".$iQueueId.";");

            if($this->isSuperMaintainer($iAppId) && !$bSuperMaintainer)
                $statusMessage = "<p>User is already a super maintainer of this application</p>\n";
            else
                $statusMessage = "<p>User is already a maintainer/super maintainer of this application/version</p>\n";
        }

        return $statusMessage;
     }

    /* get the number of queued applications */
    function getQueuedAppCount()
    {
        /* return 0 because non-admins have no way to process new apps */
        if(!$this->hasPriv("admin"))
            return 0;

        $qstring = "SELECT count(*) as queued_apps FROM appFamily WHERE queued='true'";
        $result = query_appdb($qstring);
        $ob = mysql_fetch_object($result);
        return $ob->queued_apps;
    }

    function getQueuedVersionCount()
    {
        if($this->hasPriv("admin"))
        {
            $qstring = "SELECT count(*) as queued_versions FROM appVersion WHERE queued='true'";
        } else
        {
            /* find all queued versions of applications that the user is a super maintainer of */
            $qstring = "SELECT count(*) as queued_versions FROM appVersion, appMaintainers
                        WHERE queued='true' AND appMaintainers.superMaintainer ='1'
                        AND appVersion.appId = appMaintainers.appId
                        AND appMaintainers.userId ='".$this->iUserId."';";
        }
        $result = query_appdb($qstring);
        $ob = mysql_fetch_object($result);

        /* we don't want to count the versions that are implicit in the applications */
        /* that are in the queue */
        return $ob->queued_versions - $this->getQueuedAppCount();
    }


    /* get the number of queued appdata */
    function getQueuedAppDataCount()
    {
        $hResult = $this->getAppDataQuery(0, true, false);
        $ob = mysql_fetch_object($hResult);
        return $ob->queued_appdata;
    }

    function addPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        if($this->hasPriv($sPriv))
            return true;

        $hResult = query_appdb("INSERT INTO user_privs VALUES ($this->iUserId, '$sPriv')");
        return $hResult;
    }


    function delPriv($sPriv)
    {
        if(!$this->isLoggedIn() || !$sPriv)
            return false;

        $hRresult = query_appdb("DELETE FROM user_privs WHERE userid = $this->iUserId AND priv = '$sPriv'");
        return $hRresult;
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
                        AND appMaintainers.userId = '".$this->iUserId."'
                        ".$additionalTerms.";";
         }

         return query_appdb($sQuery);
     }

     /**
      * Delete appData
      */
     function deleteAppData($iAppDataId)
     {
         $isMaintainer = false;

         /* if we aren't an admin we should see if we can find any results */
         /* for a query based on this appDataId, if we can then */
         /* we have permission to delete the entry */
         if(!$this->hasPriv("admin"))
         {
             $hResult = $this->getAppDataQuery($iAppDataId, false, false);
             if(!$hResult)
                 return false;

             echo "result rows:".mysql_num_row($hResult);

             if(mysql_num_rows($hResult) > 0)
                 $isMaintainer = true;
         }

         /* do we have permission to delete this item? */
         if($this->hasPriv("admin") || $isMaintainer)
         {
             $sQuery = "DELETE from appData where id = ".$iAppDataId."
                        LIMIT 1;";
             $hResult = query_appdb($sQuery);
             if($hResult)
                 return true;
         }

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

     /**
      * Retrieve the list of applications in the app queue that this user can see
      */
     function getAppQueueQuery($queryAppFamily)
     {
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
                            AND appMaintainers.userId = '".$this->iUserId."';";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily, appMaintainers
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'true'
                            AND appFamily.appId = appMaintainers.appId
                            AND appMaintainers.superMaintainer = '1'
                            AND appMaintainers.userId = '".$this->iUserId."';";
             }
         }

         return query_appdb($sQuery);
     }

     function getAppRejectQueueQuery($queryAppFamily)
     {
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
                            AND appFamily.submitterId = '".$this->iUserId."';";
             } else
             {
                 $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily
                            WHERE appFamily.appId = appVersion.appId 
                            AND appFamily.queued = 'false' AND appVersion.queued = 'rejected'
                            AND appVersion.submitterId = '".$this->iUserId."';";
             }
         }

         return query_appdb($sQuery);
     }

     function getAllRejectedApps()
     {
         $result = query_appdb("SELECT appVersion.versionId, appFamily.appId 
                               FROM appVersion, appFamily
                               WHERE appFamily.appId = appVersion.appId 
                               AND (appFamily.queued = 'rejected' OR appVersion.queued = 'rejected')
                               AND appVersion.submitterId = '".$this->iUserId."';");

         if(!$result || mysql_num_rows($result) == 0)
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

     /**
      * Does the user have permission to modify on this version?
      */
     function hasAppVersionModifyPermission($iVersionId)
     {
         if($this->hasPriv("admin"))
             return true;

         $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily, appMaintainers
                      WHERE appFamily.appId = appVersion.appId 
                      AND appFamily.appId = appMaintainers.appId
                      AND appMaintainers.superMaintainer = '1'
                      AND appMaintainers.userId = '".$this->iUserId."'
                      AND appVersion.versionId = '".$iVersionId."';";
         $hResult = query_appdb($sQuery);
         if(mysql_num_rows($hResult))
             return true;
         else
             return false;
     }

     function isAppSubmitter($iAppId)
     {
         $sQuery = "SELECT appId FROM appFamily
                    WHERE submitterId = '".$this->iUserId."'
                    AND appId = '".$iAppId."';";
         $hResult = query_appdb($sQuery);
         if(mysql_num_rows($hResult))
             return true;
         else
             return false;
    }
     function isVersionSubmitter($iVersionId)
     {
         $sQuery = "SELECT appVersion.versionId FROM appVersion, appFamily
                    WHERE appFamily.appId = appVersion.appId 
                    AND appVersion.submitterId = '".$this->iUserId."'
                    AND appVersion.versionId = '".$iVersionId."';";
         $hResult = query_appdb($sQuery);
         if(mysql_num_rows($hResult))
             return true;
         else
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
        $sQuery = "SELECT appMaintainers.userId 
                   FROM appMaintainers, appVersion
                   WHERE appVersion.appId = appMaintainers.appId 
                   AND appVersion.versionId = '".$iVersionId."'";
    } 
    /*
     * If versionId was not supplied we fetch supermaintainers of application and maintainer of all versions.
     */
    elseif($iAppId)
    {
        $sQuery = "SELECT userId 
                   FROM appMaintainers
                   WHERE appId = '".$iAppId."'";
    }
    $hResult = query_appdb($sQuery);
    if(mysql_num_rows($hResult) > 0)
    {
        while($oRow = mysql_fetch_object($hResult))
        {
            $aUserId[$c] = array($oRow->userId);
            $c++;
        }
    }


    /*
     * Retrieve administrators.
     */
    $hResult = query_appdb("SELECT * FROM user_privs WHERE priv  = 'admin'");
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
    $result = query_appdb("SELECT count(*) as num_users FROM user_list;");
    $row = mysql_fetch_object($result);
    return $row->num_users;
}


/**
 * Get the number of active users within $days of the current day
 */
function get_active_users_within_days($days)
{
    $result = query_appdb("SELECT count(*) as num_users FROM user_list WHERE stamp >= DATE_SUB(CURDATE(), interval $days day);");
    $row = mysql_fetch_object($result);
    return $row->num_users;
}


/**
 * Check if a user exists.
 * returns the userid if the user exists
 */
function user_exists($sEmail)
{
    $result = query_appdb("SELECT userid FROM user_list WHERE email = '$sEmail'");
    if(!$result || mysql_num_rows($result) != 1)
        return 0;
    else
    {
        $oRow = mysql_fetch_object($result);
        return $oRow->userid;
    }
}
?>
