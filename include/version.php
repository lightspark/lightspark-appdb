<?php
/************************************/
/* this class represents an version */
/************************************/

require_once(BASE."include/note.php");
require_once(BASE."include/comment.php");
require_once(BASE."include/url.php");
require_once(BASE."include/screenshot.php");

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
    var $aCommentsIds;        // an array that contains the commentId of every comment linked to this version
    var $aScreenshotsIds;     // an array that contains the screenshotId of every screenshot linked to this version
    var $aUrlsIds;            // an array that contains the screenshotId of every url linked to this version

    /**    
     * constructor, fetches the data.
     */
    function Version($iVersionId = null)
    {
        // we are working on an existing version
        if(is_numeric($iVersionId))
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
                    $this->iCatId = $oRow->catId;
                    $this->iSubmitterId = $oRow->submitterId;
                    $this->sSubmitTime = $oRow->submitTime;
                    $this->sDate = $oRow->submitTime;
                    $this->sName = $oRow->versionName;
                    $this->sKeywords = $oRow->keywords;
                    $this->sDescription = $oRow->description;
                    $this->sTestedRelease = $oRow->maintainer_release;
                    $this->sTestedRating = $oRow->maintainer_rating;
                    $this->sWebpage = $oRow->webPage;
                    $this->bQueued = ($oRow->queued=="true")?true:false;
                }
            }

            /*
             * We fetch notesIds. 
             */
            $this->aNotesIds = array();
            $sQuery = "SELECT noteId
                       FROM appNotes
                       WHERE versionId = ".$iVersionId;
            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aNotesIds[] = $oRow->noteId;
                }
            }

            /*
             * We fetch commentsIds. 
             */
            $this->aCommentsIds = array();
            $sQuery = "SELECT commentId
                       FROM appComments
                       WHERE versionId = ".$iVersionId;
            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aCommentsIds[] = $oRow->commentId;
                }
            }


            /*
             * We fetch screenshotsIds and urlsIds. 
             */
            $this->aScreenshotsIds = array();
            $this->aUrlsIds = array();
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
                        $this->aUrlsIds[] = $oRow->id;
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
                                                'submitterId'       => $_SESSION['current']->iUserId,
                                                'queued'            => $this->bQueued?"true":"false" ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appVersion $sFields VALUES $sValues", "Error while creating a new version."))
        {
            $this->iVersionId = mysql_insert_id();
            $this->version($this->iVersionId);
            $this->mailMaintainers();
            return true;
        }
        else
            return false;
    }


    /**
     * Update version.
     * FIXME: Use compile_update_string instead of addslashes.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sDescription=null, $sTestedRelease=null, $sTestedRating=null, $iAppId=null)
    {
        $sWhatChanged = "";

        if ($sName && $sName!=$this->sName)
        {
            $sUpdate = compile_update_string(array('versionName'    => $sName));
            if (!query_appdb("UPDATE appVersion SET ".$sUpdate." WHERE versionId = ".$this->iVersionId))
                return false;
            $sWhatChanged .= "Name was changed from ".$this->sName." to ".$sName.".\n\n";
            $this->sName = $sName;
        }     

        if ($sDescription && $sDescription!=$this->sDescription)
        {
            $sUpdate = compile_update_string(array('description'    => $sDescription));
            if (!query_appdb("UPDATE appVersion SET ".$sUpdate." WHERE versionId = ".$this->iVersionId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($sTestedRelease && $sTestedRelease!=$this->sTestedRelease)
        {
            $sUpdate = compile_update_string(array('maintainer_release'    => $sTestedRelease));
            if (!query_appdb("UPDATE appVersion SET ".$sUpdate." WHERE versionId = ".$this->iVersionId))
                return false;
            $sWhatChanged .= "Last tested release was changed from ".$this->sTestedRelease." to ".$sTestedRelease.".\n\n";
            $this->sTestedRelease = $sTestedRelease;
        }

        if ($sTestedRating && $sTestedRating!=$this->sTestedRating)
        {
            $sUpdate = compile_update_string(array('maintainer_rating'    => $sTestedRating));
            if (!query_appdb("UPDATE appVersion SET maintainer_rating = '".$sTestedRating."' WHERE versionId = ".$this->iVersionId))
                return false;

            if($this->sTestedRating)
                $sWhatChanged .= "Rating was changed from ".$this->sTestedRating." to ".$sTestedRating.".\n\n";
            else
                $sWhatChanged .= "Rating was changed to ".$sTestedRating.".\n\n";

            $this->sTestedRating = $sTestedRating;
        }
     
        if ($iAppId && $iAppId!=$this->iAppId)
        {
            $sUpdate = compile_update_string(array('appId'    => $iAppId));
            if (!query_appdb("UPDATE appVersion SET ".$sUpdate." WHERE versionId = ".$this->iVersionId))
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


    /**    
     * Deletes the version from the database. 
     * and request the deletion of linked elements.
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM appVersion 
                   WHERE versionId = ".$this->iVersionId." 
                   LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            foreach($this->aNotesIds as $iNoteId)
            {
                $oNote = new Note($iNoteId);
                $oNote->delete($bSilent);
            }
            foreach($this->aCommentsIds as $iCommentId)
            {
                $oComment = new Comment($iCommentId);
                $oComment->delete($bSilent);
            }
            foreach($this->aScreenshotsIds as $iScreenshotId)
            {
                $oScreenshot = new Screenshot($iScreenshotId);
                $oScreenshot->delete($bSilent);
            }
            foreach($this->aUrlsIds as $iUrlId)
            {
                $oUrl = new Url($iUrlId);
                $oUrl->delete($bSilent);
            }

            // remove any maintainers for this version so we don't orphan them
            $sQuery = "DELETE from appMaintainers WHERE versionId='".$this->iVersionId."';";
            if(!($hResult = query_appdb($sQuery)))
            {
                addmsg("Error removing version maintainers for the deleted version!", "red");
            }
        }
        if(!$bSilent)
            $this->mailMaintainers("delete");

        $this->mailSubmitter(true);
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
            $this->bQueued = false;
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

 
    function mailMaintainers($sAction="add",$sMsg=null)
    {
        $oApp = new Application($this->iAppId);
        switch($sAction)
        {
            case "add":
                if(!$this->bQueued)
                {
                    $sSubject = "Version ".$this->sName." of ".$oApp->sName." added by ".$_SESSION['current']->sRealname;
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
                    $sSubject = "Version ".$this->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This version has been queued.";
                    $sMsg .= "\n";
                    addmsg("The version you submitted will be added to the database database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject =  $oApp->sName." ".$this->sName." has been modified by ".$_SESSION['current']->sRealname;
                addmsg("Version modified.", "green");
            break;
            case "delete":
                $sSubject = "Version ".$this->sName." of ".$oApp->sName." has been deleted by ".$_SESSION['current']->sRealname;

                /* if replyText is set we should report the reason the application was deleted */
                if($_REQUEST['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; /* append the reply text, if there is any */
                }

                addmsg("Version deleted.", "green");
            break;
        }
        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}
?>
