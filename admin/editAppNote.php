<?php
/****************/
/* Edit AppNote */
/****************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

$aClean = array(); //array of filtered user input

$aClean['noteId'] = makeSafe($_REQUEST['noteId']);
$aClean['sub'] = makeSafe($_REQUEST['sub']);
$aClean['noteTitle'] = makeSafe($_REQUEST['noteTitle']);
$aClean['noteDesc'] = makeSafe($_REQUEST['noteDesc']);
$aClean['preview'] = makeSafe($_REQUEST['preview']);
$aClean['appId'] = makeSafe($_REQUEST['appId']);
$aClean['versionId'] = makeSafe($_REQUEST['versionId']);

if(!is_numeric($aClean['noteId']))
{
    errorpage('Wrong note ID');
    exit;
}  

/* Get note data */
$oNote = new Note($aClean['noteId']);

/* Check for privs */
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($oNote->iVersionId) && !$_SESSION['current']->isSuperMaintainer($oNote->iAppId))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(!empty($aClean['sub']))
{
    if ($aClean['sub'] == 'Delete')
    {
        $oNote->delete();
    } 
    else if ($aClean['sub'] == 'Update')
    {
        $oNote->update($aClean['noteTitle'],$aClean['noteDesc']);
    }
    redirect(apidb_fullurl("appview.php?versionId={$oNote->iVersionId}"));
}
else
{
    if (empty($aClean['preview']))
    {
        $aClean['noteTitle'] = $oNote->sTitle;
        $aClean['noteDesc']  = $oNote->sDescription;
        $aClean['appId'] = $oNote->iAppId;
        $aClean['versionId'] = $oNote->iVersionId;
    }

    HtmlAreaLoaderScript(array("editor"));
    
    // show form
    apidb_header("Edit Application Note");

    echo "<form method=post action='editAppNote.php'>\n";
    echo html_frame_start("Edit Application Note {$aClean['noteId']}", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");
    echo add_br($aClean['noteDesc']);
    
    echo '<input type="hidden" name="noteId" value='.$aClean['noteId'].'>';
    
    if ($aClean['noteTitle'] == "HOWTO" || $aClean['noteTitle'] == "WARNING")
    {
        echo '<tr><td class=color1>Title (Do not change)</td>';
        echo '<td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$aClean['noteTitle'].'"></td></tr>',"\n";
    }
    else
    {
        echo '<tr><td class=color1>Title</td><td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$aClean['noteTitle'].'"></td></tr>',"\n";
    }
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<p style="width:700px">', "\n";
    echo '<textarea cols="80" rows="20" id="editor" name="noteDesc">'.$aClean['noteDesc'].'</textarea>',"\n";
    echo '</p>';
    echo '</td></tr><tr><td colspan="2" align="center" class="color3">',"\n";
    echo '<input type="submit" name=preview value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Update">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Delete"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link(1,BASE."appview.php?versionId=".$oNote->iVersionId);
}

apidb_footer();
?>
