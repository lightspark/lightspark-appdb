<?php
/****************/
/* Edit AppNote */
/****************/

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/note.php");

if(!is_numeric($aClean['iNoteId']))
    util_show_error_page_and_exit('Wrong note ID');

/* Get note data */
$oNote = new Note($aClean['iNoteId']);
$oVersion = new version($oNote->iVersionId);

/* Check for privs */
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($oNote->iVersionId) && !$_SESSION['current']->isSuperMaintainer($oNote->iAppId))
    util_show_error_page_and_exit("Insufficient Privileges!");

if(!empty($aClean['sSub']))
{
    $oNote->GetOutputEditorValues($aClean); /* retrieve the updated values */

    if ($aClean['sSub'] == 'Delete')
    {
        $oNote->delete();
    } 
    else if ($aClean['sSub'] == 'Update')
    {
        $oNote->update();
    }
    util_redirect_and_exit($oVersion->objectMakeUrl());
} else /* display note */
{
    // show form
    apidb_header("Application Note");

    /* if preview is set display the note for review */
    if($aClean['sPreview'])
    {
        $oNote->GetOutputEditorValues($aClean); /* retrieve the updated values */
        $oNote->show(true);
    }

    echo "<form method=post action='editAppNote.php'>\n";

    /* display the editor for this note */
    $oNote->outputEditor();
   
    echo '<center>';
    echo '<input type="submit" name=sPreview value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sSub value="Update">&nbsp',"\n";
    echo '<input type="submit" name=sSub value="Delete"></td></tr>',"\n";
    echo '</center>';
    
    echo html_back_link(1,$oVersion->objectMakeUrl());
}

apidb_footer();
?>
