<?php

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");


$_REQUEST['appId'] = strip_tags($_REQUEST['appId']);
$_REQUEST['versionId'] = strip_tags($_REQUEST['versionId']);
$_REQUEST['commentId'] = strip_tags($_REQUEST['commentId']);
$_REQUEST['commentId'] = mysql_escape_string($_REQUEST['commentId']);

/* if we aren't an admin or the maintainer of this app we shouldn't be */
/* allowed to delete any comments */
if(!havepriv("admin") && !isMaintainer($_REQUEST['appId'], $_REQUEST['versionId']))
{
    errorpage('You don\'t have admin privilages');
    exit;
}

opendb();

/* retrieve the parentID of the comment we are deleting */
/* so we can fix up the parentIds of this comments children */
$result = mysql_query("SELECT parentId FROM appComments WHERE commentId = '".$_REQUEST['commentId']."'");
if (!$result)
{
    errorpage('Internal error retrieving parent of commentId');
    exit;
}

$ob = mysql_fetch_object($result);
$deletedParentId = $ob->parentId;

/* get the subject and body from the comment */
$result = mysql_query("select * FROM appComments WHERE commentId = '".$_REQUEST['commentId']."'");
if (!$result)
{
    errorpage('Internal Database Access Error',mysql_error());
    exit;
}
$ob = mysql_fetch_object($result);
$body = $ob->body;
$subject = $ob->subject;

if($_SESSION['current']->getpref("confirm_comment_deletion") != "no" && 
   !isset($_REQUEST['int_delete_it']))
{
    apidb_header("Delete Comment");
    $mesTitle = "<b>Please state why you are deleting the following comment</b>";
    echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">\n";
    echo html_frame_start($mesTitle,500,"",0);
    echo "<br />";
    echo html_frame_start($ob->subject,500);
    echo htmlify_urls($ob->body), "<br /><br />\n";
    echo html_frame_end();
    echo '<table width="100%" border=0 cellpadding=0 cellspacing=1>',"\n";
    echo "<tr bgcolor=#C0C0C0><td colspan=2><textarea name=\"str_why\" cols=\"70\" rows=\"15\" wrap=\"virtual\"></textarea></td></tr>\n";
    echo "<tr bgcolor=#C0C0C0><td colspan=2 align=center>\n";
    echo "  <input type=\"SUBMIT\" value=\"Delete Comment\" class=\"button\" />\n";
    echo "</td></tr>\n";
    echo "</table>\n";
    echo html_frame_end();
    echo "<input type=\"HIDDEN\" name=\"int_delete_it\" value=\"1\" />\n";
    echo "<input type=\"HIDDEN\" name=\"thread\" value=\"".$_REQUEST['thread']."\" />\n";
    echo "<input type=\"HIDDEN\" name=\"appId\" value=\"".$_REQUEST['appId']."\" />\n";
    echo "<input type=\"HIDDEN\" name=\"versionId\" value=\"".$_REQUEST['versionId']."\" />\n";
    echo "<input type=\"hidden\" name=\"commentId\" value=\"".$_REQUEST['commentId']."\" />";
    echo "</form>";
    ?>

    <p>&nbsp;</p>

    <?php
    apidb_footer();
} else
{
/* delete the comment from the database */
$result = mysql_query("DELETE FROM appComments WHERE commentId = '".$_REQUEST['commentId']."'");

if (!isset($result))
{
    errorpage('Internal Database Access Error',mysql_error());
    exit;
}

/* fixup the child comments so the parentId points to a valid parent comment */
$result = mysql_query("UPDATE appComments set parentId = '$deletedParentId' WHERE parentId = '".$_REQUEST['commentId']."'");
if(!isset($result))
{
    errorpage('Internal database error fixing up the parentId of child comments');
    exit;
}
$email = getNotifyEmailAddressList($_REQUEST['appId'], $_REQUEST['versionId']);
$notify_user_email=lookupEmail($ob->userId);
$notify_user_username=lookupUsername($ob->userId);
$email .= $notify_user_email;
if($email)
{
    $fullAppName = "Application: ".lookupAppName($_REQUEST['appId'])." Version: ".lookupVersionName($_REQUEST['appId'], $_REQUEST['versionId']);
    $ms = APPDB_ROOT."appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']."\n";
    $ms .= "\n";
    $ms .= $_SESSION['current']->username." deleted comment from ".$fullAppName."\n";
    $ms .= "\n";
    $ms .= "This comment was made on ".substr($ob->time,0,10)." by $notify_user_username \n";
    $ms .= "\n";
    $ms .= "Subject: ".$subject."\n";
    $ms .= "\n";
    $ms .= $body."\n";
    $ms .= "\n";
    $ms .= "Because:\n";
    if($_REQUEST['str_why'])
        $ms .= stripslashes($_REQUEST['str_why'])."\n";
    else
        $ms .= "No reason given.\n";
    $ms .= "\n";
    $ms .= STANDARD_NOTIFY_FOOTER;
    echo $ms;
    mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);
} else
{
   $email = "no one";
}
addmsg("mesage sent to: ".$email, "green");

addmsg("Comment deleted", "green");
redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId']."&versionId=".$_REQUEST['versionId']));
}
?>

