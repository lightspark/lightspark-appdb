<?php
/***************************************/
/* url class and related functions */
/***************************************/


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
                       AND id = ".$iUrlId;
            if($hResult = query_appdb($sQuery))
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
        // Security, if we are not an administrator or a maintainer, the url must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($_REQUEST['versionId']) || $_SESSION['current']->isSupermaintainer($_REQUEST['appId'])))
        {
            $this->bQueued = true;
        }

        $aInsert = compile_insert_string(array( 'appId'       => $iAppId,
                                                'versionId'   => $iVersionId,
                                                'type'        => "url",
                                                'description' => $sDescription,
                                                'queued'      => $this->bQueued,
                                                'submitterId' => $_SESSION['current']->iUserId ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appData $sFields VALUES $sValues", "Error while creating a new url."))
        {
            $this->iUrlId = mysql_insert_id();
            $this->url($this->iUrlId,$this->bQueued);
            $this->mailMaintainers();
            return true;
        }
        else
            return false;
    }


    /**    
     * Deletes the url from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM appData 
                   WHERE id = ".$this->iUrlId." 
                   AND type = 'url' 
                   LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            if(!$bSilent)
                $this->mailMaintainers(true);
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

        $sUpdate = compile_update_string(array('queued' => "false"));
        if(query_appdb("UPDATE appData SET ".$sUpdate." WHERE id=".$this->iUrlId))
        {
            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->mailMaintainers();
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
            $sUpdate = compile_update_string(array('description' => $sDescription));
            if (!query_appdb("UPDATE appData SET ".$sUpdate." WHERE id = ".$this->iUrlId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($sUrl && $sUrl!=$this->sUrl)
        {
            $sUpdate = compile_update_string(array('noteDesc' => $sDescription));
            if (!query_appdb("UPDATE appData SET ".$sUpdate." WHERE id = ".$this->iUrlId))
                return false;
            $sWhatChanged .= "Url was changed from ".$this->sUrl." to ".$sUrl.".\n\n";
            $this->sUrl = $sUrl;
        }

        if ($iVersionId && $iVersionId!=$this->iVersionId)
        {
            $sUpdate = compile_update_string(array('versionId' => $iVersionId));
            if (!query_appdb("UPDATE appData SET ".$sUpdate." WHERE id = ".$this->iUrlId))
                return false;
            $oVersionBefore = new Version($this->iVersionId);
            $oVersionAfter = new Version($iVersionId);
            $sWhatChanged .= "Version was changed from ".$oVersionBefore->sName." to ".$oVersionAfter->sName.".\n\n";
            $this->iVersionId = $iVersionId;
            $this->iAppId = $oVersionAfter->iAppId;
        }

        if ($iAppId && $iAppId!=$this->iAppId)
        {
            $sUpdate = compile_update_string(array('appId'    => $iAppId));
            if (!query_appdb("UPDATE appData SET ".$sUpdate." WHERE id = ".$this->iUrlId))
                return false;
            $oAppBefore = new Application($this->iAppId);
            $oAppAfter = new Application($iAppId);
            $sWhatChanged .= "Application was changed from ".$oAppBefore->sName." to ".$oAppAfter->sName.".\n\n";
            $this->iAppId = $iAppId;
        }
        if($sWhatChanged)
            $this->mailMaintainers("edit",$sWhatChanged);       
        return true;
    }

    
    function mailSubmitter($bRejected=false)
    {
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted url accepted";
                $sMsg  = "The url you submitted for ".lookup_app_name($this->appId)." ".lookup_version_name($this->versionId)." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted url rejected";
                 $sMsg  = "The url you submitted for ".lookup_app_name($this->appId)." ".lookup_version_name($this->versionId)." has been rejected.";
            }
            $sMsg .= $_REQUEST['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function mailMaintainers($bDeleted=false)
    {
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Url for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This url has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The url was successfully added into the database.", "green");
            } else // Url queued.
            {
                $sSubject = "Url for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                $sMsg .= "This url has been queued.";
                $sMsg .= "\n";
                addmsg("The url you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Url deleted.
        {
            $sSubject = "Url for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
            addmsg("Url deleted.", "green");
        }

        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}
?>
