<?

/*
 * Add Application Note
 *
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

if($sub == "Submit")
{

    $query = "INSERT into appNotes VALUES (null, '".
                                addslashes($noteTitle)."', '".
                                addslashes($noteDesc)."', ".
                                "$appId , $versionId);"; 
    if (mysql_query($query))
    {
        //successful
        $email = getNotifyEmailAddressList($appId, $versionId);
        if($email)
        {
            $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
            $ms = APPDB_ROOT."appview.php?appId=$appId&versionId=$versionId"."\n";
            $ms .= "\n";
            $ms .= ($_SESSION['current']->username ? $_SESSION['current']->username : "Anonymous")." added note to ".$fullAppName."\n";
            $ms .= "\n";
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

        $statusMessage = "<p>Note added into the database</p>\n";
        addmsg($statusMessage,Green);
    }
    else
    {
        //error
        addmsg($query,red);
        $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        addmsg($statusMessage,red);
    }
    redirect(apidb_fullurl("appview.php?appId=".$appId.$versionLink));
    exit;
}
else
{
    apidb_header("Add Application Note");

    echo "<form method=post action='addAppNote.php'>\n";
    echo html_frame_start("Add Application Note $appId", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo '<input type=hidden name="appId" value='.$appId.'>';
    echo '<input type=hidden name="versionId" value='.$versionId.'>';
    echo '<tr><td colspan=2 class=color4>';
    echo '<center><b>You can use html to make your Warning, Howto or Note look better.</b></center>';
    echo '</td></tr>',"\n";

    echo add_br($noteDesc);

    if ($noteTitle == "HOWTO" || $noteTitle == "WARNING")
    {
        echo '<input type=hidden name="noteTitle" value='.$noteTitle.'>';
        echo '<tr><td class=color1>Type</td><td class=color0>'.$noteTitle.'</td></tr>',"\n";
    }
    else
    {
        echo '<tr><td class=color1>Title</td><td class=color0><input size=80% type="text" name="noteTitle" type="text" value="'.$noteTitle.'"></td></tr>',"\n";
    }
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=$50 rows=10 name="noteDesc">'.stripslashes($noteDesc).'</textarea></td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3>',"\n";
    echo '<input type="submit" name=preview value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Submit"></td></tr>',"\n";
    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link(1,$apidb_root."appview.php?appId=$appId".$versionLink);
    apidb_footer();
}

?>
