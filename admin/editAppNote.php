<?php
/****************/
/* Edit AppNote */
/****************/

include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

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
    $ms = APPDB_ROOT."appview.php?appId={$ob->appId}&versionId={$ob->versionId}"."\n";
    $ms .= "\n";
            
    $sEmail = getNotifyEmailAddressList($ob->appId, $ob->versionId);
    
    if ($_REQUEST['sub'] == 'Delete')
    {
        // delete Note
        query_appdb("DELETE from `appNotes` where noteId = {$_REQUEST['noteId']}");
       
        if($sEmail)
        {
            $ms .= ($_SESSION['current']->realname ? $_SESSION['current']->realname : "Anonymous")." deleted note from ".$sFullAppName."\n";
            $ms .= "\n";
            $ms .= "title: ".$sOldNoteTitle."\n";
            $ms .= "\n";
            $ms .= $sOldNoteDesc."\n";
            $ms .= "\n";
            $ms .= STANDARD_NOTIFY_FOOTER;

            mail( "", "[AppDB] ".$sFullAppName ,$ms, "Bcc: ".stripslashes( $sEmail));

        } else
        {
            $sEmail = "no one";
        }
        
        addmsg("message sent to: ".$sEmail, 'green');
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
            $ms .= ($_SESSION['current']->realname ? $_SESSION['current']->realname : "Anonymous")." changed note for ".$sFullAppName."\n";
            $ms .= "From --------------------------\n";
            $ms .= "title: ".$sOldNoteTitle."\n";
            $ms .= "\n";
            $ms .= $sOldNoteDesc."\n";
            $ms .= "To --------------------------\n";
            $ms .= "title: ".$_REQUEST['noteTitle']."\n";
            $ms .= "\n";
            $ms .= $_REQUEST['noteDesc']."\n";
            $ms .= "\n";
            $ms .= STANDARD_NOTIFY_FOOTER;

            mail( "", "[AppDB] ".$sFullAppName ,$ms, "Bcc: ".stripslashes( $sEmail));

        } else
        {
            $sEmail = "no one";
        }
        addmsg("message sent to: ".$sEmail, green);

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
