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
$sQuery = "SELECT * from appNotes where noteId = {$_REQUEST['noteId']}";
$hResult = query_appdb($sQuery);
$ob = mysql_fetch_object($hResult);

/* Check for privs */
if(!loggedin() || (!havepriv("admin") && !$_SESSION['current']->is_maintainer($ob->appId,$ob->versionId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['sub']))
{
    $sOldNoteTitle = $ob->noteTitle;
    $sOldNoteDesc  = $ob->noteDesc;
    
    $sFullAppName = "Application: ".lookupAppName($ob->appId)." Version: ".lookupVersionName($ob->appId, $ob->versionId);
    
    /* Start of e-mail */
    $sMsg = APPDB_ROOT."appview.php?appId={$ob->appId}&versionId={$ob->versionId}\r\n";
    $sMsg .= "\r\n";
            
    $sEmail = getNotifyEmailAddressList($ob->appId, $ob->versionId);
    
    if ($_REQUEST['sub'] == 'Delete')
    {
        // delete Note
        query_appdb("DELETE from `appNotes` where noteId = {$_REQUEST['noteId']}");
       
        if($sEmail)
        {
            $sMsg .= $_SESSION['current']->realname." deleted note from ".$sFullAppName."\r\n";
            $sMsg .= "\r\n";
            $sMsg .= "title: ".$sOldNoteTitle."\r\n";
            $sMsg .= "\r\n";
            $sMsg .= $sOldNoteDesc."\r\n";
            $sMsg .= "\r\n";

            mail_appdb($sEmail, $sFullAppName ,$sMsg);
        } 
        // success
        addmsg("Note Deleted.", "green");
    } 
    else if ($_REQUEST['sub'] == 'Update')
    {
        $sUpdate = compile_update_string(array( 'noteTitle' => $_REQUEST['noteTitle'],
                                               'noteDesc'  => $_REQUEST['noteDesc']));
        
        query_appdb("UPDATE appNotes SET $sUpdate WHERE noteId = {$_REQUEST['noteId']}");
        
        if($sEmail)
        {
            $sMsg .= $_SESSION['current']->realname." changed note for ".$sFullAppName."\r\n";
            $sMsg .= "From --------------------------\r\n";
            $sMsg .= "title: ".$sOldNoteTitle."\r\n";
            $sMsg .= "\r\n";
            $sMsg .= $sOldNoteDesc."\r\n";
            $sMsg .= "To --------------------------\r\n";
            $sMsg .= "title: ".$_REQUEST['noteTitle']."\r\n";
            $sMsg .= "\r\n";
            $sMsg .= $_REQUEST['noteDesc']."\r\n";
            $sMsg .= "\r\n";

            mail_appdb($sEmail, $sFullAppName ,$sMsg);

        } 
        addmsg("Note Updated", "green");
    }
    
    redirect(apidb_fullurl("appview.php?appId={$ob->appId}&versionId={$ob->versionId}"));
}
else
{
    if (!isset($_REQUEST['preview']))
    {
        $_REQUEST['noteTitle'] = $ob->noteTitle;
        $_REQUEST['noteDesc']  = $ob->noteDesc;
        $_REQUEST['appId'] = $ob->appId;
        $_REQUEST['versionId'] = $ob->versionId;
    }
    // show form
    apidb_header("Edit Application Note");

    echo "<form method=post action='editAppNote.php'>\n";
    echo html_frame_start("Edit Application Note {$_REQUEST['noteId']}", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");
    echo '<tr><td colspan=2 class=color4>';
    echo '<center><b>You can use html to make your Warning, Howto or Note look better.</b></center>';
    echo '</td></tr>',"\n";

    echo add_br($_REQUEST['noteDesc']);
    
    echo '<input type=hidden name="noteId" value='.$_REQUEST['noteId'].'>';
    
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
    echo '<textarea cols=50 rows=10 name="noteDesc">'.stripslashes($_REQUEST['noteDesc']).'</textarea></td></tr>',"\n";
    echo '<tr><td colspan=2 align=center class=color3>',"\n";
    echo '<input type="submit" name=preview value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Update">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Delete"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link();

}

apidb_footer();

?>
