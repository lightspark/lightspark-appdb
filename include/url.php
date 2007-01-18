<?php
/***************************************/
/* url class and related functions */
/***************************************/
require_once(BASE."include/util.php");

/**
 * Url class for handling urls
 */
class Url {
    var $iUrlId;
    var $iAppId;
    var $iVersionId;
    var $sDescription;
    var $sUrl;
    var $sSubmitTime;
    var $sSubmitterId;
    var $bQueued;


    /**    
     * Constructor, fetches the url $iUrlId is given.
     */
    function Url($iUrlId = null)
    {
        // we are working on an existing url
        if($iUrlId)
        {
            $sQuery = "SELECT appData.*
                       FROM appData
                       WHERE type = 'url'
                       AND id = '?'";
            if($hResult = query_parameters($sQuery, $iUrlId))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iUrlId = $iUrlId;
                $this->sDescription = $oRow->description;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->sUrl = $oRow->url;
                $this->bQueued = $oRow->queued;
                $this->sSubmitTime = $oRow->submitTime;
                $this->iSubmitterId = $oRow->submitterId;
           }
        }
    }
 

    /**
     * Creates a new url.
     */
    function create($sDescription = null, $sUrl = null, $iVersionId = null, $iAppId = null)
    {
        global $aClean;

        // Security, if we are not an administrator or a maintainer, the url must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($aClean['iVersionId']) || $_SESSION['current']->isSupermaintainer($aClean['iAppId'])))
        {
            $this->bQueued = true;
        }

        $hResult = query_parameters("INSERT INTO appData (appId, versionId, type, description,".
                                    "queued, submitterId) VALUES ('?', '?', '?', '?', '?', '?')",
                                    $iAppId, $iVersionId, "url", $sDescription, $this->bQueued,
                                    $_SESSION['current']->iUserId);
        if($hResult)
        {
            $this->iUrlId = mysql_insert_id();
            $this->url($this->iUrlId,$this->bQueued);
            $this->SendNotificationMail();
            return true;
        }
        else
        {
            addmsg("Error while creating a new url.", "red");
            return false;
        }
    }


    /**    
     * Deletes the url from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM appData 
                   WHERE id = '?' 
                   AND type = 'url' 
                   LIMIT 1";
        if($hResult = query_parameters($sQuery, $this->iUrlId))
        {
            if(!$bSilent)
                $this->SendNotificationMail(true);
        }
        if($this->iSubmitterId)
        {
            $this->mailSubmitter(true);
        }
    }


    /**
     * Move url out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the url out of the queue.
        if(!$this->bQueued)
            return false;

        if(query_parameters("UPDATE appData SET queued = '?' WHERE id='?'",
                       "false", $this->iUrlId))
        {
            // we send an e-mail to interested people
            $this->mailSubmitter();
            $this->SendNotificationMail();
            // the url has been unqueued
            addmsg("The url has been unqueued.", "green");
        }
    }


    /**
     * Update url.
     * Returns true on success and false on failure.
     */
    function update($sDescription = null, $sUrl = null, $iVersionId = null, $iAppId = null)
    {
        $sWhatChanged = "";

        if ($sDescription && $sDescription!=$this->sDescription)
        {
            if (!query_parameters("UPDATE appData SET description = '?' WHERE id = '?'",
                                  $sDescription, $this->iUrlId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($sUrl && $sUrl!=$this->sUrl)
        {
            if (!query_parameters("UPDATE appData SET noteDesc = '?' WHERE id = '?'",
                                  $sDescription, $this->iUrlId))
                return false;
            $sWhatChanged .= "Url was changed from ".$this->sUrl." to ".$sUrl.".\n\n";
            $this->sUrl = $sUrl;
        }

        if ($iVersionId && $iVersionId!=$this->iVersionId)
        {
            if (!query_parameters("UPDATE appData SET versionId = '?' WHERE id = '?'",
                                  $iVersionId, $this->iUrlId))
                return false;
            $oVersionBefore = new Version($this->iVersionId);
            $oVersionAfter = new Version($iVersionId);
            $sWhatChanged .= "Version was changed from ".$oVersionBefore->sName." to ".$oVersionAfter->sName.".\n\n";
            $this->iVersionId = $iVersionId;
            $this->iAppId = $oVersionAfter->iAppId;
        }

        if ($iAppId && $iAppId!=$this->iAppId)
        {
            if (!query_parameters("UPDATE appData SET appId = '?' WHERE id = '?'",
                                  $iAppId, $this->iUrlId))
                return false;
            $oAppBefore = new Application($this->iAppId);
            $oAppAfter = new Application($iAppId);
            $sWhatChanged .= "Application was changed from ".$oAppBefore->sName." to ".$oAppAfter->sName.".\n\n";
            $this->iAppId = $iAppId;
        }
        if($sWhatChanged)
            $this->SendNotificationMail("edit",$sWhatChanged);       
        return true;
    }

    
    function mailSubmitter($bRejected=false)
    {
        global $aClean;

        if($this->iSubmitterId)
        {
            $sAppName = Application::lookup_name($this->appId)." ".Version::lookup_name($this->versionId);
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted url accepted";
                $sMsg  = "The url you submitted for ".$sAppName." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted url rejected";
                 $sMsg  = "The url you submitted for ".$sAppName." has been rejected.";
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($bDeleted=false)
    {
        $sAppName = Application::lookup_name($this->appId)." ".Version::lookup_name($this->versionId);
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Url for ".$sAppName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This url has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The url was successfully added into the database.", "green");
            } else // Url queued.
            {
                $sSubject = "Url for ".$sAppName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                $sMsg .= "This url has been queued.";
                $sMsg .= "\n";
                addmsg("The url you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Url deleted.
        {
            $sSubject = "Url for ".$sAppName." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
            addmsg("Url deleted.", "green");
        }

        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }

    /* Display links for a given version/application */
    function display($iVersionId, $iAppId = NULL)
    {
        if($iVersionId)
        {
            if(!($hResult = appData::getData($iVersionId, "url")))
                return FALSE;
        } else
        {
            if(!($hResult = appData::getData($iAppId, "url", FALSE)))
                return FALSE;
        }

        for($i = 0; $oRow = mysql_fetch_object($hResult); $i++)
        {
            $sReturn .= html_tr(array(
                "<b>Link</b>",
                "<a href=\"$oRow->url\">$oRow->description</a>"),
                "color1");
        }

        return $sReturn;
    }
}
?>
