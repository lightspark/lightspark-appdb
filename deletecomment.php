<?

include("path.php");
require(BASE."include/"."incl.php");

//FIXME: should check to see if the user is an application maintainer when we have application maintainers
if(!havepriv("admin"))
{
    errorpage('You don\'t have admin privilages');
    exit;
}

opendb();

$commentId = strip_tags($_POST['commentId']);
$commentId = mysql_escape_string($commentId);

$appId = strip_tags($_POST['appId']);
$versionId = strip_tags($_POST['versionId']);

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

addmsg("Comment deleted", "green");
redirect(apidb_fullurl("appview.php?appId=$appId&versionId=$versionId"));

?>
