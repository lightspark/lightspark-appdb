<?php
/************************************/
/* note class and related functions */
/************************************/


/**
 * Note class for handling notes
 */
class Note {
    var $iNoteId;
    var $iAppId;
    var $iVersionId;
    var $sTitle;
    var $sDescription;

    /**
     * Constructor.
     * If $iNoteId is provided, fetches note.
     */
    function Note($iNoteId="")
    {
        if($iNoteId)
        {
            $sQuery = "SELECT appNotes.*, appVersion.appId AS appId
                       FROM appNotes, appVersion
                       WHERE appNotes.versionId = appVersion.versionId 
                       AND noteId = '".$iNoteId."'";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);
            $this->iNoteId = $oRow->noteId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->sTitle = $oRow->noteTitle;
            $this->sDescription = $oRow->noteDesc;
        }
    }


    /*
     * Creates a new note.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create($sTitle, $sDescription, $iVersionId)
    {
        $aInsert = compile_insert_string(array( 'versionId' => $iVersionId,
                                                'noteTitle' => $sTitle,
                                                'noteDesc' => $sDescription ));

        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appNotes $sFields VALUES $sValues", "Error while creating a new note."))
        {
            $this->note(mysql_insert_id());
            $sWhatChanged = "Description is:\n".$sDescription.".\n\n";
            $this->mailMaintainers("add", $sWhatChanged);
            return true;
        }
        else
            return false;
    }


    /**
     * Update note.
     * Returns true on success and false on failure.
     */
    function update($sTitle=null, $sDescription=null, $iVersionId=null)
    {
        $sWhatChanged = "";

        if ($sTitle && $sTitle!=$this->sTitle)
        {
            $sUpdate = compile_update_string(array('noteTitle'    => $sTitle));
            if (!query_appdb("UPDATE appNotes SET ".$sUpdate." WHERE noteId = ".$this->iNoteId))
                return false;
            $sWhatChanged .= "Title was changed from ".$this->sTitle." to ".$sTitle.".\n\n";
            $this->sTitle = $sTitle;
        }

        if ($sDescription && $sDescription!=$this->sDescription)
        {
            $sUpdate = compile_update_string(array('noteDesc' => $sDescription));
            if (!query_appdb("UPDATE appNotes SET ".$sUpdate." WHERE noteId = ".$this->iNoteId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($iVersionId && $iVersionId!=$this->iVersionId)
        {
            $sUpdate = compile_update_string(array('versionId' => $iVersionId));
            if (!query_appdb("UPDATE appNotes SET ".$sUpdate." WHERE noteId = ".$this->iNoteId))
                return false;
            $oVersionBefore = new Version($this->iVersionId);
            $oVersionAfter = new Version($iVersionId);
            $sWhatChanged .= "Version was changed from ".$oVersionBefore->sName." to ".$oVersionAfter->sName.".\n\n";
            $this->iVersionId = $iVersionId;
            $this->iAppId = $oVersionAfter->iAppId;
        }
        if($sWhatChanged)
            $this->mailMaintainers("edit",$sWhatChanged);       
        return true;
    }


    /**
     * Removes the current note from the database.
     * Informs interested people about the deletion.
     */
    function delete($bSilent=false)
    {
        $hResult = query_appdb("DELETE FROM appNotes WHERE noteId = '".$this->iNoteId."'");
        if(!$bSilent)
            $this->mailMaintainers("delete");
    }


    function mailMaintainers($sAction="add",$sMsg=null)
    {
        switch($sAction)
        {
            case "add":
                $sSubject = "Note ".$this->sTitle." for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." added by ".$_SESSION['current']->sRealname;
                $sMsg .= APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                addmsg("The note was successfully added into the database.", "green");
            break;
            case "edit":
                $sSubject =  "Note ".$this->sTitle." for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." has been modified by ".$_SESSION['current']->sRealname;
                addmsg("Note modified.", "green");
            break;
            case "delete":
                $sSubject = "Note ".$this->sTitle." for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." has been deleted by ".$_SESSION['current']->sRealname;
                $sMsg .= "This note was made on ".substr($this->sDateCreated,0,10)." by ".$this->oOwner->sRealname."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sTitle."\n";
                $sMsg .= "\n";
                $sMsg .= $this->sBody."\n";
                $sMsg .= "\n";
                $sMsg .= "Because:\n";
                if($_REQUEST['replyText'])
                    $sMsg .= $_REQUEST['replyText']."\n";
                else
                    $sMsg .= "No reason given.\n";

                addmsg("Note deleted.", "green");
            break;
        }
        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}
?>
