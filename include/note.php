<?php
require_once(BASE."include/util.php");
require_once(BASE."include/version.php");

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
    var $shDescription;
    var $iSubmitterId;
    var $sSubmitTime;

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
            if($oRow)
            {
                $this->iNoteId = $oRow->noteId;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->sTitle = $oRow->noteTitle;
                $this->shDescription = $oRow->noteDesc;
                $this->sSubmitTime = $oRow->submitTime;
                $this->iSubmitterId = $oRow->submitterId;
            }
        }
    }


    /*
     * Creates a new note.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create()
    {
        $hResult = query_parameters("INSERT INTO appNotes (versionId, noteTitle, noteDesc, submitterId) ".
                                    "VALUES('?', '?', '?', '?')",
                                    $this->iVersionId, $this->sTitle, $this->shDescription, $_SESSION['current']->iUserId);

        if($hResult)
        {
            $this->note(mysql_insert_id());
            $sWhatChanged = "Description is:\n".$this->shDescription.".\n\n";
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
    function update()
    {
        $sWhatChanged = "";
        
        /* create an instance of ourselves so we can see what has changed */
        $oNote = new Note($this->iNoteId);

        if ($this->sTitle && $this->sTitle!=$oNote->sTitle)
        {
            if (!query_parameters("UPDATE appNotes SET noteTitle = '?' WHERE noteId = '?'",
                                  $this->sTitle, $this->iNoteId))
                return false;
            $sWhatChanged .= "Title was changed from ".$oNote->sTitle." to ".$this->sTitle.".\n\n";
        }

        if ($this->shDescription && $this->shDescription!=$oNote->shDescription)
        {
            if (!query_parameters("UPDATE appNotes SET noteDesc = '?' WHERE noteId = '?'",
                                  $this->shDescription, $this->iNoteId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$oNote->shDescription."\n to \n".$this->shDescription.".\n\n";
        }

        if ($this->iVersionId && $this->iVersionId!=$oNote->iVersionId)
        {
            if (!query_parameters("UPDATE appNotes SET versionId = '?' WHERE noteId = '?'",
                                  $this->iVersionId, $this->iNoteId))
                return false;
            $sVersionBefore = Version::lookup_name($oNote->iVersionId);
            $sVersionAfter = Version::lookup_name($this->iVersionId);
            $sWhatChanged .= "Version was changed from ".$sVersionBefore." to ".$sVersionAfter.".\n\n";
            $this->iVersionId = $iVersionId;

            //TODO: iAppId isn't in the appNotes table
            // and we only use it for permissions checking in showNote() and in SendNotificationEmail
            // we may be able to look it up on the fly if we had a more efficient way of doing so
            // instead of having to construct a version object each time
            $oVersionAfter = new Version($this->iVersionId);
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
        $oVersion = new version($this->iVersionId);
        $sAppName = version::fullName($this->iVersionId);
        $sMsg .= $oVersion->objectMakeUrl()."\n";
        switch($sAction)
        {
            case "add":
                $sSubject = "Note $this->sTitle for $sAppName added by ".
                $_SESSION['current']->sRealname;
                addmsg("The note was successfully added into the database.", "green");
            break;
            case "edit":
                $sSubject =  "Note $this->sTitle for $sAppName has been modified by ".
                $_SESSION['current']->sRealname;
                addmsg("Note modified.", "green");
            break;
            case "delete":
                $oSubmitter = new User($this->iSubmitterId);
                $sSubject = "Note $this->sTitle for $sAppName has been deleted by ".
                $_SESSION['current']->sRealname;
                $sMsg .= "This note was made on ".print_date(mysqltimestamp_to_unixtimestamp($this->sSubmitTime))." by ".$oSubmitter->sRealname."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sTitle."\n";
                $sMsg .= "\n";
                $sMsg .= "Note contents:\n";
                $sMsg .= $this->shDescription."\n";
                $sMsg .= "\n";
                $sMsg .= "Because:\n";
                if($aClean['sReplyText'])
                    $sMsg .= $aClean['sReplyText']."\n";
                else
                    $sMsg .= "No reason given.\n";

                addmsg("Note deleted.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 

    /* Show note */
    /* $bDisplayOnly means we should not display any editing controls, even if */
    /*   the user has the ability to edit this note */
    function show($bDisplayOnly = false)
    {
        switch($this->sTitle)
        {
        case 'WARNING':
            $sColor = 'red';
            $sTitle = 'Warning';
            break;

        case 'HOWTO':
            $sColor = 'green';
            $sTitle = 'HOWTO';
            break;

        default:
            if(!empty($this->sTitle))
                $sTitle = $this->sTitle;
            else 
                $sTitle = 'Note';
            
            $sColor = 'blue';
        }
    
        $shOutput = html_frame_start("","98%",'',0);

        $shOutput .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\">\n";
        $shOutput .= "<tr bgcolor=\"".$sColor."\" align=\"center\" valign=\"top\"><td><b>".$sTitle."</b></td></tr>\n";
        $shOutput .= "<tr><td class=\"note\">\n";
        $shOutput .= $this->shDescription;
        $shOutput .= "</td></tr>\n";

        if(!$bDisplayOnly)
        {
            if ($_SESSION['current']->hasPriv("admin") ||
                $_SESSION['current']->isMaintainer($this->iVersionId) ||
                $_SESSION['current']->isSuperMaintainer($this->iAppId))
            {
                $shOutput .= "<tr class=\"color1\" align=\"center\" valign=\"top\"><td>";
                $shOutput .= "<form method=\"post\" name=\"message\" action=\"admin/editAppNote.php?iNoteId={$this->iNoteId}\">";
                $shOutput .= '<input type="submit" value="Edit Note" class="button">';
                $shOutput .= '</form></td></tr>';
            }
        }

        $shOutput .= "</table>\n";
        $shOutput .= html_frame_end();

        echo $shOutput;
    }


    function outputEditor()
    {
        HtmlAreaLoaderScript(array("editor"));
    
        echo html_frame_start("Edit Application Note {$aClean['noteId']}", "90%","",0);
        echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

        echo '<input type="hidden" name="iNoteId" value="'.$this->iNoteId.'" />';
        echo '<input type="hidden" name="iAppId" value="'.$this->iAppId.'" />';
        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" />';

        echo '<tr><td class=color1>Title</td>'."\n";
        echo '    <td class=color0><input size=80% type="text" name="sNoteTitle" type="text" value="'.$this->sTitle.'"></td></tr>',"\n";
        echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
        echo '<p style="width:700px">', "\n";
        echo '<textarea cols="80" rows="20" id="editor" name="shNoteDesc">'.$this->shDescription.'</textarea>',"\n";
        echo '</p>';
        echo '</td></tr>'."\n";
        echo '<tr><td colspan="2" align="center" class="color3">',"\n";

        echo html_table_end();
        echo html_frame_end();
    }

    /* retrieves values from $aValue that were output by outputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from outputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iVersionId = $aValues['iVersionId'];
        $this->iAppId = $aValues['iAppId'];
        $this->sTitle = $aValues['sNoteTitle'];
        $this->shDescription = $aValues['shNoteDesc'];
    }
}
?>
