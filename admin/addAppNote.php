<?php
/************************/
/* Add Application Note */
/************************/

include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

//check for admin privs
if(!loggedin() || (!havepriv("admin") && !$_SESSION['current']->is_maintainer($_REQUEST['appId'],$_REQUEST['versionId'])) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

//set link for version
if(is_numeric($_REQUEST['versionId']) and !empty($_REQUEST['versionId']))
{
    $versionLink = "&versionId={$_REQUEST['versionId']}";
}
else 
    exit;

if(!is_numeric($_REQUEST['appId']))
{
    errorpage('Wrong ID');
    exit;
}  

if($_REQUEST['sub'] == "Submit")
{

    $query = "INSERT into appNotes VALUES (null, '".
                                addslashes($_REQUEST['noteTitle'])."', '".
                                addslashes($_REQUEST['noteDesc'])."', ".
                                "{$_REQUEST['appId']}, {$_REQUEST['versionId']})"; 
    if (mysql_query($query))
    {
        // successful
        $email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
        if($email)
        {
            $fullAppName  = "Application: ".lookupAppName($_REQUEST['appId']);
            $fullAppName .= " Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
            $ms = APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
            $ms .= "\n";
            $ms .= ($_SESSION['current']->realname ? $_SESSION['current']->realname : "Anonymous")." added note to ".$fullAppName."\n";
            $ms .= "\n";
            $ms .= "title: ".$_REQUEST['noteTitle']."\n";
            $ms .= "\n";
            $ms .= $_REQUEST['noteDesc']."\n";
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
        // error
        addmsg($query,red);
        $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        addmsg($statusMessage,red);
    }
    redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId'].$versionLink));
    exit;
}
else if($_REQUEST['sub'] == 'Preview' OR empty($_REQUEST['submit']))
{
    apidb_header("Add Application Note");

    echo "<form method=post action='addAppNote.php'>\n";
    echo html_frame_start("Add Application Note {$_REQUEST['appId']}", "90%","",0);
    echo html_table_begin("width='100%' border=0 align=left cellpadding=6 cellspacing=0 class='box-body'");

    echo "<input type=hidden name='appId' value='{$_REQUEST['appId']}'>";
    echo "<input type=hidden name='versionId' value='{$_REQUEST['versionId']}'>";
    echo '<tr><td colspan=2 class=color4>';
    echo '<center><b>You can use html to make your Warning, Howto or Note look better.</b></center>';
    echo '</td></tr>',"\n";

    echo add_br($_REQUEST['noteDesc']);

    if ($_REQUEST['noteTitle'] == "HOWTO" || $_REQUEST['noteTitle'] == "WARNING")
    {
        echo "<input type=hidden name='noteTitle' value='{$_REQUEST['noteTitle']}'>";
        echo "<tr><td class=color1>Type</td><td class=color0>{$_REQUEST['noteTitle']}</td></tr>\n";
    }
    else
    {
        echo "<tr><td class=color1>Title</td><td class=color0><input size='80%' type='text' name='noteTitle' type='text' value='{$_REQUEST['noteTitle']}'></td></tr>\n";
    }
    echo '<tr><td class=color4>Description</td><td class=color0>', "\n";
    echo '<textarea cols=50 rows=10 name="noteDesc">'.stripslashes($_REQUEST['noteDesc']).'</textarea></td></tr>',"\n";

    echo '<tr><td colspan=2 align=center class=color3>',"\n";
    echo '<input type="submit" name=sub value="Preview">&nbsp',"\n";
    echo '<input type="submit" name=sub value="Submit"></td></tr>',"\n";
    echo html_table_end();
    echo html_frame_end();
    
    echo html_back_link(1,BASE."appview.php?appId={$_REQUEST['appId']}$versionLink");
    apidb_footer();
}

?>
