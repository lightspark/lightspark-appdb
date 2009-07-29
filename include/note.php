<?php
require_once(BASE."include/util.php");
require_once(BASE."include/version.php");

/************************************/
/* note class and related functions */
/************************************/


define('APPNOTE_SHOW_FOR_ALL', -1);
define('APPNOTE_SHOW_FOR_VERSIONS', -2);
define('APPNOTE_SHOW_FOR_APP', -3);

/**
 * Note class for handling notes
 */
class Note {
    var $iNoteId;
    var $iVersionId;
    var $iAppId;
    var $sTitle;
    var $shDescription;
    var $iSubmitterId;
    var $sSubmitTime;

    /**
     * Constructor.
     * If $iNoteId is provided, fetches note.
     */
    function Note($iNoteId = null, $oRow = null)
    {
        if(!$iNoteId && !$oRow)
          return;

        if(!$oRow)
        {
            $sQuery = "SELECT * FROM appNotes WHERE noteId = '?'";
            if($hResult = query_parameters($sQuery, $iNoteId))
              $oRow = query_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iNoteId = $oRow->noteId;
            $this->iVersionId = $oRow->versionId;
            $this->iAppId = $oRow->appId;
            $this->sTitle = $oRow->noteTitle;
            $this->shDescription = $oRow->noteDesc;
            $this->sSubmitTime = $oRow->submitTime;
            $this->iSubmitterId = $oRow->submitterId;
        }
    }


    /*
     * Creates a new note.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create()
    {
        $hResult = query_parameters("INSERT INTO appNotes (versionId, ".
                                    "appId, noteTitle, noteDesc, submitterId, ".
                                    "submitTime) ".
                                    "VALUES('?', '?', '?', '?', '?', ?)",
                                    $this->iVersionId, $this->iAppId,
                                    $this->sTitle, $this->shDescription,
                                    $_SESSION['current']->iUserId,
                                    "NOW()");

        if($hResult)
        {
            $this->note(query_appdb_insert_id());
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


    function unQueue()
    {
        return true; // We don't queue notes
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

        if (($this->iVersionId || $this->iAppId) && $this->iVersionId!=$oNote->iVersionId)
        {
            if (!query_parameters("UPDATE appNotes SET versionId = '?' WHERE noteId = '?'",
                                  $this->iVersionId, $this->iNoteId))
                return false;

            if(!$this->iAppId)
            {
                $sVersionBefore = Version::lookup_name($oNote->iVersionId);
                $sVersionAfter = Version::lookup_name($this->iVersionId);
                $sWhatChanged .= "Version was changed from ".$sVersionBefore." to ".$sVersionAfter.".\n\n";
            } else
            {
                $oApp = new application($this->iAppId);
                $sNewApp = $oApp->sName;
                $sVersionBefore = version::fullName($oNote->iVersionId);
                $sWhatChanged .= "Moved from version $sVersionBefore to application $sNewApp.\n\n"; 
            }
        }
        if (($this->iAppId || $this->iVersionId) && $this->iAppId!=$oNote->iAppId)
        {
            if(!query_parameters("UPDATE appNotes SET appId = '?' WHERE noteId = '?'",
                                  $this->iAppId, $this->iNoteId))
                return false;
        }
        if($sWhatChanged)
            $this->SendNotificationMail("edit",$sWhatChanged);       
        return true;
    }

    function purge()
    {
        return $this->delete();
    }

    /**
     * Removes the current note from the database.
     * Informs interested people about the deletion.
     *
     * Returns: true if successful, false if not
     */
    function delete()
    {
        $hResult = query_parameters("DELETE FROM appNotes WHERE noteId = '?'", $this->iNoteId);

        if(!$hResult)
            return FALSE;

        return TRUE;
    }

    function objectShowPreview()
    {
        return TRUE;
    }

    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        if(!$this->iAppId)
        {
            $oVersion = new version($this->iVersionId);
            $sAppName = version::fullName($this->iVersionId);
            $sMsg .= $oVersion->objectMakeUrl()."\n";
        } else
        {
            $oApp = new application($this->iAppId);
            $sAppName = $oApp->sName;
            $sMsg .= $oApp->objectMakeUrl()."\n";
        }

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
                $sMsg .= "This note was made on ".print_date(mysqldatetime_to_unixtimestamp($this->sSubmitTime)).
                         " by ".$oSubmitter->sRealname."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sTitle."\n";
                $sMsg .= "\n";
                $sMsg .= "Note contents:\n";
                $sMsg .= $this->shDescription."\n";
                $sMsg .= "\n";
                $sMsg .= "Because:\n";
                if(isset($aClean['sReplyText']) && $aClean['sReplyText'])
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
    function display($aVars = null)
    {
        switch($this->sTitle)
        {
        case 'WARNING':
            $sClass = 'warning';
            $sTitle = 'Warning';
            break;

        case 'HOWTO':
            $sClass = 'howto';
            $sTitle = 'HOWTO';
            break;

        default:
            if(!empty($this->sTitle))
                $sTitle = $this->sTitle;
            else 
                $sTitle = 'Note';
            
            $sClass = 'defaultnote';
        }

        if(!$aVars || !getInput('shReturnTo', $aVars))
        {
            $oVersion = new version($this->iVersionId);
            $shReturnTo = $oVersion->objectMakeUrl();
        } else
        {
            $shReturnTo = $aVars['shReturnTo'];
        }

        $shOutput = html_frame_start("","98%",'',0);

        $shOutput .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\">\n";
        $shOutput .= "<tr class=\"".$sClass."\" align=\"center\" valign=\"top\"><td>&nbsp;</td></tr><tr class=\"notetitle\" valign=\"top\" align=\"center\"><td>".$sTitle."</td></tr>\n";
        $shOutput .= "<tr><td class=\"note\">\n";
        $shOutput .= $this->shDescription;
        $shOutput .= "</td></tr>\n";

        if((!$aVars || $aVars['bEditing'] != "true") && $this->canEdit())
        {
            $shOutput .= "<tr class=\"color1\" align=\"center\" valign=\"top\"><td>";
            $shOutput .= "<form method=\"post\" name=\"message\" action=\"objectManager.php?sClass=note&amp;sAction=edit&amp;iId=".$this->iNoteId."&amp;sReturnTo=".urlencode($shReturnTo)."\">";
            $shOutput .= '<input type="submit" value="Edit Note" class="button">';
            $shOutput .= '</form></td></tr>';
        }

        $shOutput .= "</table>\n";
        $shOutput .= html_frame_end();

        echo $shOutput;
    }

    function displayNotesForEntry($iVersionId, $iAppId = null)
    {
        if($iVersionId)
        {
            $oVersion = new version($iVersionId);
            $oApp = $oVersion->objectGetParent();
            $hResult = query_parameters("SELECT noteId FROM appNotes WHERE versionId  = '?' OR (appId = '?' AND (versionId = '?' OR versionId = '?'))", $iVersionId, $oApp->objectGetId(), APPNOTE_SHOW_FOR_ALL, APPNOTE_SHOW_FOR_VERSIONS);
        } else if($iAppId)
        {
            $hResult = query_parameters("SELECT noteId FROM appNotes WHERE appId = '?' AND versionId = '?' OR versionId = '?'", $iAppId, APPNOTE_SHOW_FOR_ALL, APPNOTE_SHOW_FOR_APP);
        }

        if(!$hResult)
            return;

        if($iVersionId)
            $oVersion = new version($iVersionId);
        else
            $oApp = new application($iAppId);

        while($oRow = mysql_fetch_object($hResult))
        {
            $oNote = new note($oRow->noteId);

            $shReturnTo = $iVersionId ? $oVersion->objectMakeUrl() : $oApp->objectMakeUrl();

            $aVars = array('shReturnTo' => $shReturnTo, 'bEditing' => 'false');
            $oNote->display($aVars);
        }
    }

    function objectGetCustomVars($sAction)
    {
        switch($sAction)
        {
            case "preview":
                return array("bEditing");

            case "add":
                return array('iVersionId','iAppId','sNoteTitle');

            default:
                return null;
        }
    }

    public static function isRealVersionId($iVersionId)
    {
        return $iVersionId > 0;
    }

    function outputEditor($aValues = null)
    {
        if($aValues)
        {
            if(!$this->iVersionId)
                $this->iVersionId = getInput('iVersionId', $aValues);

            if(!$this->iAppId)
                $this->iAppId = getInput('iAppId', $aValues);

            if(!$this->sTitle)
                $this->sTitle = getInput('sNoteTitle', $aValues);
        }

        if($this->iAppId && !$this->iVersionId)
            $this->iVersionId = APPNOTE_SHOW_FOR_ALL;

        if(!$this->iAppId)
        {
            $oVersion = new version($this->iVersionId);
            $this->iAppId = $oVersion->iAppId;
        }

        HtmlAreaLoaderScript(array("editor"));

        echo html_frame_start("Edit Application Note", "90%","",0);
        echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

        echo '<input type="hidden" name="bEditing" value="true">';
        echo '<input type="hidden" name="iNoteId" value="'.$this->iNoteId.'">';

        echo '<input type="hidden" name="iAppId" value="'.$this->iAppId.'">';

        echo '<tr><td class=color1>Title</td>'."\n";
        echo '    <td class=color0><input size=80% type="text" name="sNoteTitle" type="text" value="'.$this->sTitle.'"></td></tr>',"\n";
        echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
        echo '<p style="width:700px">', "\n";
        echo '<textarea cols="80" rows="20" id="editor" name="shNoteDesc">'.$this->shDescription.'</textarea>',"\n";
        echo '</p>';
        echo '</td></tr>'."\n";

        if($this->iAppId);
            $oApp = new application($this->iAppId);
        if($this->iAppId || $oApp->canEdit())
        {
            $aIds = array();
            $aOptions = array();
            if($this->isRealVersionId($this->iVersionId))
            {
                $aIds[] = $this->iVersionId;
                $aOptions[] = 'Show for this version only';
            }
            $aIds = array_merge($aIds, array(APPNOTE_SHOW_FOR_ALL, APPNOTE_SHOW_FOR_VERSIONS, APPNOTE_SHOW_FOR_APP));
            $aOptions = array_merge($aOptions, array('Show on both application and version pages', 'Show on all version pages only', 'Show on application page only'));;
            echo '<tr><td class="color1">Display mode</td>'."\n";
            echo '<td class="color0">'.html_radiobuttons($aIds, $aOptions, 'iVersionId', $this->iVersionId);
            echo '</td></tr>';
        } else if(!$this->iAppId)
        {
            echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'">';
        }

        echo '<tr><td colspan="2" align="center" class="color3">',"\n";

        echo html_table_end();
        echo html_frame_end();
    }

    /* retrieves values from $aValue that were output by outputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from outputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iVersionId = getInput('iVersionId', $aValues);

        if(!$this->isRealVersionId($this->iVersionId))
            $this->iAppId = getInput('iAppId', $aValues);
        else
            $this->iAppId = 0;
        $this->sTitle = $aValues['sNoteTitle'];
        $this->shDescription = $aValues['shNoteDesc'];
    }

    function allowAnonymousSubmissions()
    {
        return false;
    }

    // NOTE: notes cannot be queued at this point
    function mustBeQueued()
    {
        return false;
    }

    function objectGetId()
    {
        return $this->iNoteId;
    }

    // TODO: we ignore $bQueued and $bRejected as notes
    //       do not support queuing at this point
    // TODO: we have no permissions scope on retrieving entries
    //       as notes are typically only added to unqueued versions
    function objectGetEntries($sState, $iRows = 0, $iStart = 0, $sOrderBy = '', $bAscending = true)
    {
        $sQuery = "select * from appNotes";
        $hResult = query_parameters($sQuery);
        return $hResult;
    }

    function objectGetEntriesCount($sState)
    {
        $sQuery = "SELECT COUNT(DISTINCT noteId) as count FROM appNotes";
        $hResult = query_parameters($sQuery);

        if(!$hResult)
            return false;

        if(($oRow = mysql_fetch_object($hResult)))
            return $oRow->count;

        return false;
    }

    //TODO: not sure how to best let users view a table of notes
    //      since the note contents could be very long we would only
    //      want to show a small amount of the text. Implement this
    //      routine when we need it
    function objectGetHeader()
    {
        return null;
    }

    //TODO: implement this when we implement objectGetHeader()
    function objectGetTableRow()
    {
        return null;
    }

    function objectMakeUrl()
    {
        $oManager = new objectManager("note", "View Note");
        return $oManager->makeUrl("view", $this->objectGetId());
    }

    function objectGetSubmitterId()
    {
        return $this->iSubmitterId;
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        return new mailOptions();
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        /* We don't do this at the moment */
                return array(null, null, null);
    }

    public function objectGetParent($sClass = '')
    {
        if($this->iVersionId)
            return new version($this->iVersionId);
        else
            return new application($this->iAppId);
    }

    public function objectSetParent($iNewId, $sClass = '')
    {
        if($this->iVersionId)
            $this->iVersionId = $iNewId;
        else
            $this->iAppId = $iNewId;
    }

    function objectGetChildren($bIncludeDeleted = false)
    {
        return array();
    }

    //TODO: not sure if we want to use sTitle here or what
    function objectMakeLink()
    {
        $sLink = "<a href=\"".$this->objectMakeUrl()."\">".
                 $this->sTitle."</a>";
        return $sLink;
    }

    function objectGetState()
    {
        return 'accepted'; // We don't queue notes
    }

    // users can edit the note if they:
    //  - have "admin" privileges
    //  - maintain the version, or supermaintain the application that
    //    this version is under
    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return true;
        else if($this->iVersionId && !$this->iAppId)
            return maintainer::isUserMaintainer($_SESSION['current'], $this->iVersionId);
        else if($this->iAppId)
            return maintainer::isUserSuperMaintainer($_SESSION['current'], $this->iAppId);

        return false;
    }
}
?>
