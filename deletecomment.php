<?php
/*******************/
/* delete comments */
/*******************/

/*
 * application environment
 */
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");


$_REQUEST['appId'] = strip_tags($_REQUEST['appId']);
$_REQUEST['versionId'] = strip_tags($_REQUEST['versionId']);
$_REQUEST['commentId'] = strip_tags($_REQUEST['commentId']);
$_REQUEST['commentId'] = mysql_escape_string($_REQUEST['commentId']);

if(!$_SESSION['current']->isLoggedIn())
{
    errorpage("You need to be logged in to delete a comment.");
    exit;
}

/* if we aren't an admin or the maintainer of this app we shouldn't be */
/* allowed to delete any comments */
if(!$_SESSION['current']->hasPriv("admin") && 
   !$_SESSION['current']->isMaintainer($_REQUEST['appId'], $_REQUEST['versionId']))
{
    errorpage('You don\'t have sufficient privileges to delete this comment.');
    exit;
}

$oComment = new Comment($_REQUEST['commentId']);


if($_SESSION['current']->getPref("confirm_comment_deletion") != "no" && !isset($_REQUEST['int_delete_it']))
{
    apidb_header("Delete Comment");
    $mesTitle = "<b>Please state why you are deleting the following comment</b>";
    echo "<form method=\"POST\" action=\"".$_SERVER['PHP_SELF']."\">\n";
    echo html_frame_start($mesTitle,500,"",0);
    echo "<br />";
    echo html_frame_start($oComment->sSubject,500);
    echo htmlify_urls($oComment->sBody), "<br /><br />\n";
    echo html_frame_end();
    echo '<table width="100%" border=0 cellpadding=0 cellspacing=1>',"\n";
    echo "<tr class=color1><td colspan=2><textarea name=\"str_why\" cols=\"70\" rows=\"15\" wrap=\"virtual\"></textarea></td></tr>\n";
    echo "<tr class=color1><td colspan=2 align=center>\n";
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
    $oComment->delete($_REQUEST['str_why']);
    redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
}
?>
