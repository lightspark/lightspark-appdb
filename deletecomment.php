<?

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");


$appId = strip_tags($_POST['appId']);
$versionId = strip_tags($_POST['versionId']);

$commentId = strip_tags($_POST['commentId']);
$commentId = mysql_escape_string($commentId);

/* if we aren't an admin or the maintainer of this app we shouldn't be */
/* allowed to delete any comments */
if(!havepriv("admin") && !isMaintainer($appId, $versionId))
{
    errorpage('You don\'t have admin privilages');
    exit;
}

opendb();

/* retrieve the parentID of the comment we are deleting */
/* so we can fix up the parentIds of this comments children */
$result = mysql_query("SELECT parentId FROM appComments WHERE commentId = '$commentId'");
if (!$result)
{
    errorpage('Internal error retrieving parent of commentId');
    exit;
}

$ob = mysql_fetch_object($result);
$deletedParentId = $ob->parentId;

/* get the subject and body from the comment */
$result = mysql_query("select * FROM appComments WHERE commentId = '$commentId'");
if (!$result)
{
    errorpage('Internal Database Access Error',mysql_error());
    exit;
}
$ob = mysql_fetch_object($result);
$body = $ob->body;
$subject = $ob->subject;

/* delete the comment from the database */

$result = mysql_query("DELETE FROM appComments WHERE commentId = '$commentId'");

if (!$result)
{
    errorpage('Internal Database Access Error',mysql_error());
    exit;
}

/* fixup the child comments so the parentId points to a valid parent comment */
$result = mysql_query("UPDATE appComments set parentId = '$deletedParentId' WHERE parentId = '$commentId'");
if(!$result)
{
    errorpage('Internal database error fixing up the parentId of child comments');
    exit;
}
$email = getNotifyEmailAddressList($appId, $versionId);
if($email)
{
    $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
    $ms .= APPDB_ROOT."appview.php?appId=$appId&versionId=$versionId\n";
    $ms .= "\n";
    $ms .= ($current->username ? $current->username : "Anonymous")." deleted comment from ".$fullAppName."\n";
    $ms .= "\n";
    $ms .= "Subject: ".$subject."\n";
    $ms .= "\n";
    $ms .= $body."\n";
    $ms .= "\n";
    $ms .= STANDARD_NOTIFY_FOOTER;

    mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);

} else
{
   $email = "no one";
}
addmsg("mesage sent to: ".$email, green);

addmsg("Comment deleted", "green");
redirect(apidb_fullurl("appview.php?appId=$appId&versionId=$versionId"));

?>
