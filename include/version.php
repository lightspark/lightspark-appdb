<?php
/************************************/
/* this class represents an version */
/************************************/

/**
 * Version class for handling versions.
 */
class Version {
    var $iVersionId;
    var $iAppId;
    var $sName;
    var $sDescription;
    var $sTestedRelease;
    var $sTestedRating;
    var $sSubmitTime;
    var $iSubmitterId;
    var $sDate;
    var $aNotesIds;           // an array that contains the noteId of every note linked to this version
    var $aScreenshotsIds;     // an array that contains the screenshotId of every screenshot linked to this version
    var $aUrlsIds;            // an array that contains the screenshotId of every url linked to this version

    /**    
     * constructor, fetches the data.
     */
    function Version($iVersionId = null)
    {
        // we are working on an existing version
        if($iVersionId)
        {
            /*
             * We fetch the data related to this version.
             */
            if(!$this->versionId)
            {
                $sQuery = "SELECT *
                           FROM appVersion
                           WHERE versionId = ".$iVersionId;
                if($hResult = query_appdb($sQuery))
                {
                    $oRow = mysql_fetch_object($hResult);
                    $this->iVersionId = $iVersionId;
                    $this->iAppId = $oRow->appId;
                    $this->iVendorId = $oRow->vendorId;
                    $this->iCatId = $oRow->catId;
                    $this->iSubmitterId = $oRow->submitterId;
                    $this->sSubmitTime = $oRow->submitTime;
                    $this->sDate = $oRow->submitTime;
                    $this->sName = $oRow->versionName;
                    $this->sKeywords = $oRow->keywords;
                    $this->sDescription = $oRow->description;
                    $this->sWebpage = $oRow->webPage;
                    $this->bQueued = $oRow->queued;
                }
            }

            /*
             * We fetch notesIds. 
             */
            $sQuery = "SELECT noteId
                       FROM appNotes
                       WHERE versionId = ".$iVersionId;
            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aNotesIds[] = $oRow->versionId;
                }
            }


            /*
             * We fetch screenshotsIds and urlsIds. 
             */
            $sQuery = "SELECT id, type
                       FROM appData
                       WHERE versionId = ".$iVersionId;

            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    if($oRow->type="image")
                        $this->aScreenshotsIds[] = $oRow->id;
                    else
                        $this->aNotesIds[] = $oRow->id;
                }
            }
        }
    }


    /**
     * Creates a new version.
     */
    function create($sName=null, $sDescription=null, $sTestedRelease=null, $sTestedRating=null, $iAppId=null)
    {
        // Security, if we are not an administrator or an appmaintainer the version must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isSupermaintainer($iAppId)))
            $this->bQueued = true;
        else
            $this->bQueued = false;

        $aInsert = compile_insert_string(array( 'versionName'       => $sName,
                                                'description'       => $sDescription,
                                                'maintainer_release'=> $sTestedRelease,
                                                'maintainer_rating' => $sTestedRating,
                                                'appId'             => $iAppId,
                                                'queued'     => $this->bQueued ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appVersion $sFields VALUES $sValues", "Error while creating a new version."))
        {
            $this->iVersionId = mysql_insert_id();
            $this->mailMaintainers();
            $this->version($this->iVersionId);
            return true;
        }
        else
            return false;
    }


    /**
     * Update version.
     * FIXME: Informs interested people about the modification.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sDescription=null, $sTestedRelease=null, $sTestedRating=null, $iAppId=null)
    {
        if ($sName)
        {
            if (!query_appdb("UPDATE appVersion SET versionName = '".$sName."' WHERE versionId = ".$this->iVersionId))
                return false;
            $this->sName = $sName;
        }     

        if ($sDescription)
        {
            if (!query_appdb("UPDATE appVersion SET description = '".$sDescription."' WHERE versionId = ".$this->iVersionId))
                return false;
            $this->sDescription = $sDescription;
        }

        if ($sTestedRelease)
        {
            if (!query_appdb("UPDATE appVersion SET maintainer_release = '".$sTestedRelease."' WHERE versionId = ".$this->iVersionId))
                return false;
            $this->sKeywords = $sTestedRelease;
        }

        if ($sTestedRating)
        {
            if (!query_appdb("UPDATE appVersion SET maintainer_rating = '".$sTestedRating."' WHERE versionId = ".$this->iVersionId))
                return false;
            $this->sWebpage = $sTestedRating;
        }
     
        if ($iAppId)
        {
            if (!query_appdb("UPDATE appVersion SET vendorId = '".$iAppId."' WHERE appId = ".$this->iAppId))
                return false;
            $this->iVendorId = $iAppId;
        }
        return true;
    }


    /**    
     * Deletes the version from the database. 
     * and request the deletion of linked elements.
     * FIXME: DELETE COMMENTS AS WELL !
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM appVersion 
                   WHERE versionId = ".$this->iVersionId." 
                   LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            foreach($aNotesIds as $iNoteId)
            {
                #FIXME: NOT IMPLEMENTED $oNote = new Note($iNoteId);
                #FIXME: NOT IMPLEMENTED $oNote->delete($bSilent);
            }
            foreach($aScreenshotsIds as $iScreenshotId)
            {
                $oScreenshot = new Screenshot($iScreenshotId);
                $oScreenshot->delete($bSilent);
            }
            foreach($aUrlsIds as $iUrlId)
            {
                #FIXME: NOT IMPLEMENTED $oUrl = new Note($iUrlId);
                #FIXME: NOT IMPLEMENTED $oUrl->delete($bSilent);
            }
        }
        if(!$bSilent)
            $this->mailMaintainers(true);
    }


    /**
     * Move version out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the version out of the queue.
        if(!$this->bQueued)
            return false;

        $sUpdate = compile_update_string(array('queued'    => "false"));
        if(query_appdb("UPDATE appVersion SET ".$sUpdate." WHERE versionId = ".$this->iVersionId))
        {
            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->mailMaintainers();

            // the version has been unqueued
            addmsg("The version has been unqueued.", "green");
        }
    }


    function mailSubmitter($bRejected=false)
    {
        if($this->iSubmitterId)
        {
            $oApp = new Application($this->appId);
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted version accepted";
                $sMsg  = "The version you submitted (".$oApp->sName." ".$this->sName.") has been accepted.";
            } else
            {
                 $sSubject =  "Submitted version rejected";
                 $sMsg  = "The version you submitted (".$oApp->sName." ".$this->sName.") has been rejected.";
            }
            $sMsg .= $_REQUEST['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Version Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function mailMaintainers($bDeleted=false)
    {
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Version ".$this->sName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This version has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The version was successfully added into the database.", "green");
            } else // Version queued.
            {
                $sSubject = "Version ".$this->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg .= "This version has been queued.";
                $sMsg .= "\n";
                addmsg("The version you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Version deleted.
        {
            $sSubject = "Version ".$this->sName." deleted by ".$_SESSION['current']->sRealname;
            addmsg("Version deleted.", "green");
        }

        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 

}
?>
