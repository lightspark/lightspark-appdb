<?php
/****************/
/* Edit AppNote */
/****************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

if(!is_numeric($_REQUEST['noteId']))
{
    errorpage('Wrong note ID');
    exit;
}  

/* Get note data */
$oNote = new Note($_REQUEST['noteId']);

/* Check for privs */
if(!$_SESSION['current']->isLoggedIn() || (!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($oNote->iVersionId) && !isSuperMaintainer($oNote->iAppId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['sub']))
{
    if ($_REQUEST['sub'] == 'Delete')
    {
        $oNote->delete();
    } 
    else if ($_REQUEST['sub'] == 'Update')
    {
        $oNote->update($_REQUEST['noteTitle'],$_REQUEST['noteDesc']);
    }
    redirect(apidb_fullurl("appview.php?versionId={$oNote->iVersionId}"));
}
else
{
    if (!isset($_REQUEST['preview']))
    {
        $_REQUEST['noteTitle'] = $oNote->sTitle;
        $_REQUEST['noteDesc']  = $oNote->sDescription;
        $_REQUEST['appId'] = $oNote->iAppId;
        $_REQUEST['versionId'] = $oNote->iVersionId;
    }
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<!-- load HTMLArea -->
<script type="text/javascript" src="../htmlarea/htmlarea_loader.js"></script>
<?php
    // show form
    apidb_header("Edit Application Note");

    echo "<form method=post action='editAppNote.php'>\n";
    echo html_frame_start("Edit Application Note {$_REQUEST['noteId']}", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");
    echo add_br($_REQUEST['noteDesc']);
    
    echo '<input type="hidden" name="noteId" value='.$_REQUEST['noteId'].'>';
    
    if ($_REQUEST['noteTitle'] == "HOWTO" || $_REQUEST['noteTitle'] == "WARNING")
    {
        echo '<tr><td class=color1>Title (Do not change)</td>';
        echo '<td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$_REQUEST['noteTitle'].'"></td></tr>',"\n";
    }
    else
    {
        echo '<tr><td class=color1>Title</td><td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$_REQUEST['noteTitle'].'"></td></tr>',"\n";
    }
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<p style="width:700px">', "\n";
    echo '<textarea cols="80" rows="20" id="editor" name="noteDesc">'.stripslashes($_REQUEST['noteDesc']).'</textarea>',"\n";
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
