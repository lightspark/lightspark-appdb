<?

/*
 * Edit AppNote
 */

include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

global $apidb_root;


//check for admin privs
if(!loggedin() || (!havepriv("admin") && !isMaintainer($appId,$versionId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

//set link for version
if ($versionId != 0)
{
    $versionLink = "&versionId=$versionId";
}

if($sub)
{
    $query = "SELECT * from appNotes where noteId = $noteId;";
    $result = mysql_query($query);
    if(!$result)
    {
        $ob = mysql_fetch_object($result);

        $oldNoteTitle = $ob->noteTitle;
        $oldNoteDesc  = $ob->noteDesc;
    }
    if ($sub == 'Delete')
    {
        //delete Note
        $query = "DELETE from appNotes where noteId = $noteId;";
        $result = mysql_query($query);
        if(!$result)
        {
            //error
            addmsg("Internal Error: unable to delete selected note!", "red");
        }
        else
        {   
            $email = getNotifyEmailAddressList($appId, $versionId);
            if($email)
            {
                $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
                $ms = APPDB_ROOT."appview.php?appId=$appId&versionId=$versionId"."\n";
                $ms .= "\n";
                $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." deleted note from ".$fullAppName."\n";
                $ms .= "\n";
                $ms .= "title: ".$oldNoteTitle."\n";
                $ms .= "\n";
                $ms .= $oldNoteDesc."\n";
                $ms .= "\n";
                $ms .= STANDARD_NOTIFY_FOOTER;

                mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
    
            } else
            {
                $email = "no one";
            }
            addmsg("mesage sent to: ".$email, green);
            //success
            addmsg("Note Deleted.", "green");
        }
    } 
    if ($sub == 'Update')
    {
        //Update Note
        $NewNoteTitle = addslashes($noteTitle);
        $NewNoteDesc  = addslashes($noteDesc);
        if (!mysql_query("UPDATE appNotes SET noteTitle = '".$NewNoteTitle."', ".
            "noteDesc = '".$NewNoteDesc."'".
            " WHERE noteId = $noteId"))
        {
            $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
            addmsg($statusMessage, "red");
	}
        else
        {
            $email = getNotifyEmailAddressList($appId, $versionId);
            if($email)
            {
                $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
                $ms = APPDB_ROOT."appview.php?appId=$appId&versionId=$versionId"."\n";
                $ms .= "\n";
                $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." changed note for ".$fullAppName."\n";
                $ms .= "\n";
                $ms .= "From --------------------------\n";
                $ms .= "title: ".$oldNoteTitle."\n";
                $ms .= "\n";
                $ms .= $oldNoteDesc."\n";
                $ms .= "To --------------------------\n";
                $ms .= "title: ".$noteTitle."\n";
                $ms .= "\n";
                $ms .= $noteDesc."\n";
                $ms .= "\n";
                $ms .= STANDARD_NOTIFY_FOOTER;

                mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
    
            } else
            {
                $email = "no one";
            }
            addmsg("mesage sent to: ".$email, green);

            addmsg("Note Updated", "green");
        }
    }
    redirect(apidb_fullurl("appview.php?appId=".$appId.$versionLink));

}
else
{
    if (!$preview)
    {
        $table = "appNotes";
        $query = "SELECT * FROM $table WHERE noteId = $noteId";
        $result = mysql_query($query);
        $ob = mysql_fetch_object($result);
        $noteTitle = $ob->noteTitle;
        $noteDesc  = $ob->noteDesc;
        $appId     = $ob->appId;
        $versionId = $ob->versionId;
    }
    // show form
    apidb_header("Edit Application Note");

    echo "<form method=post action='editAppNote.php'>\n";
    echo html_frame_start("Edit Application Note $ob->noteId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");
    echo '<tr><td colspan=2 class=color4>';
    echo '<center><b>You can use html to make your Warning, Howto or Note look better.</b></center>';
    echo '</td></tr>',"\n";

    echo add_br($noteDesc);
    echo '<input type=hidden name="noteId" value='.$noteId.'>';
    echo '<input type=hidden name="appId" value='.$appId.'>';
    echo '<input type=hidden name="versionId" value='.$versionId.'>';
    if ($noteTitle == "HOWTO" || $noteTitle == "WARNING")
    {
        echo '<tr><td class=color1>Title (Do not change)</td>';
        echo '<td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$noteTitle.'"></td></tr>',"\n";
    }
    else
    {
        echo '<tr><td class=color1>Title</td><td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$noteTitle.'"></td></tr>',"\n";
    }
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=$50 rows=10 name="noteDesc">'.stripslashes($noteDesc).'</textarea></td></tr>',"\n";
    echo '<tr><td colspan=2 align=center class=color3>',"\n";
    echo '<input type="submit" name=preview value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Update">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Delete"></td></tr>',"\n";

    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link(1,$apidb_root."appview.php?appId=$appId".$versionLink);

}

apidb_footer();

?>
