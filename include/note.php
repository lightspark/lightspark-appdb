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
            $sEmail = get_notify_email_address_list($this->iAppId, $this->iVersionId);
            if($sEmail)
            {
                $sSubject = "Note for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?appId=".$this->iAppId."&versionId=".$this->iVersionId."\n";
                $sMsg .= "\n";
                $sMsg .= "Title: ".$this->sTitle."\r\n";
                $sMsg .= "\n";
                $sMsg .= $this->sDescription."\r\n";
                mail_appdb($sEmail, $sSubject ,$sMsg);
            } 
            addmsg("Note created.", "green");
            return true;
        }
        else
            return false;
    }


    /**
     * Update note.
     * FIXME: Informs interested people about the modification.
     * Returns true on success and false on failure.
     */
    function update($sTitle=null, $sDescription=null, $iVersionId=null)
    {
        if ($sTitle)
        {
            if (!query_appdb("UPDATE appNotes SET noteTitle = '".$sTitle."' WHERE noteId = ".$this->iNoteId))
                return false;
            $this->sTitle = $sTitle;
        }

        if ($sDescription)
        {
            if (!query_appdb("UPDATE appNotes SET noteDesc = '".$sDescription."' WHERE noteId = ".$this->iNoteId))
                return false;
            $this->sDescription = $sDescription;
        }

        if ($iVersionId)
        {
            if (!query_appdb("UPDATE appNotes SET versionId = '".$iVersionId."' WHERE noteId = ".$this->iNoteId))
                return false;
            $this->iVersionId = $iVersionId;
            // FIXME: we need to refetch $this->iAppId.
        }
       
        return true;
    }


    /**
     * Removes the current note from the database.
     * Informs interested people about the deletion.
     * Returns true on success and false on failure.
     */
    function delete($sReason=null)
    {
        $hResult = query_appdb("DELETE FROM appNotes WHERE noteId = '".$this->iNoteId."'");
        if ($hResult)
        {
            $sEmail = get_notify_email_address_list($this->iAppId, $this->iVersionId);
            if($sEmail)
            {
                $sSubject = "Note for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." deleted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?appId=".$this->iAppId."&versionId=".$this->iVersionId."\n";
                $sMsg .= "\n";
                $sMsg .= "This note was made on ".substr($this->sDateCreated,0,10)." by ".$this->oOwner->sRealname."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sSubject."\n";
                $sMsg .= "\n";
                $sMsg .= $this->sBody."\n";
                $sMsg .= "\n";
                $sMsg .= "Because:\n";
                if($sReason)
                    $sMsg .= $sReason."\n";
                else
                    $sMsg .= "No reason given.\n";
                mail_appdb($sEmail, $sSubject ,$sMsg);
            } 
            addmsg("Note deleted.", "green");
            return true;
        }
        return false;
    }
}



/*
 * Note functions that are not part of the class
 */

?>
