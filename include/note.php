<?php
require_once(BASE."include/util.php");

$aClean = array(); //array of filtered user input
$aClean['replyText'] = makeSafe( $_REQUEST['replyText'] );

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
                       AND noteId = '?'";
            $hResult = query_parameters($sQuery, $iNoteId);
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
        $hResult = query_parameters("INSERT INTO appNotes (versionId, noteTitle, noteDesc) ".
                                    "VALUES('?', '?', '?')",
                                    $iVersionId, $sTitle, $sDescription);

        if($hResult)
        {
            $this->note(mysql_insert_id());
            $sWhatChanged = "Description is:\n".$sDescription.".\n\n";
            $this->SendNotificationMail("add", $sWhatChanged);
            return true;
        }
        else
        {
            addmsg("Error while creating a new note.", "red");
            return false;
        }
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
            if (!query_parameters("UPDATE appNotes SET noteTitle = '?' WHERE noteId = '?'",
                                  $sTitle, $this->iNoteId))
                return false;
            $sWhatChanged .= "Title was changed from ".$this->sTitle." to ".$sTitle.".\n\n";
            $this->sTitle = $sTitle;
        }

        if ($sDescription && $sDescription!=$this->sDescription)
        {
            if (!query_parameters("UPDATE appNotes SET noteDesc = '?' WHERE noteId = '?'",
                                  $sDescription, $this->iNoteId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($iVersionId && $iVersionId!=$this->iVersionId)
        {
            if (!query_parameters("UPDATE appNotes SET versionId = '?' WHERE noteId = '?'",
                                  $iVersionId, $this->iNoteId))
                return false;
            $oVersionBefore = new Version($this->iVersionId);
            $oVersionAfter = new Version($iVersionId);
            $sWhatChanged .= "Version was changed from ".$oVersionBefore->sName." to ".$oVersionAfter->sName.".\n\n";
            $this->iVersionId = $iVersionId;
            $this->iAppId = $oVersionAfter->iAppId;
        }
        if($sWhatChanged)
            $this->SendNotificationMail("edit",$sWhatChanged);       
        return true;
    }


    /**
     * Removes the current note from the database.
     * Informs interested people about the deletion.
     */
    function delete($bSilent=false)
    {
        $hResult = query_parameters("DELETE FROM appNotes WHERE noteId = '?'", $this->iNoteId);
        if(!$bSilent)
            $this->SendNotificationMail("delete");
    }


    function SendNotificationMail($sAction="add",$sMsg=null)
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
                if($aClean['replyText'])
                    $sMsg .= $aClean['replyText']."\n";
                else
                    $sMsg .= "No reason given.\n";

                addmsg("Note deleted.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}
?>
