<?php
/**
 * Adds a new comment.
 *
 * Mandatory parameters:
 *  - iVersionId, version identifier
 *
 * Optional parameters:
 *  - iThread, parent comment identifier 
 *  - sBody, body of the comment
 *  - sSubject, title of the comment
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/comment.php");

// you must be logged in to submit comments
if(!$_SESSION['current']->isLoggedIn())
{
  apidb_header("Please login");
  echo "To submit a comment for an application you must be logged in. Please <a href=\"account.php?sCmd=login\">login now</a> or create a <a href=\"account.php?sCmd=new\">new account</a>.","\n";
  exit;
}

// the user submitted his comment
if(!empty($aClean['sBody']))
{
    // create a new comment
    $oComment = new Comment();
    $oComment->sSubject = $aClean['sSubject'];
    $oComment->sBody = $aClean['sBody'];
    $oComment->iParentId = $aClean['iThread'];
    $oComment->iVersionId = $aClean['iVersionId'];
    $oComment->create();

    $oVersion = new version($oComment->iVersionId);
    util_redirect_and_exit($oVersion->objectMakeUrl());
// let's show the comment form
} else
{
  apidb_header("Add Comment");

  $mesTitle = "<b>Post New Comment</b>";

  if($aClean['iThread'] > 0)
  {
    $hResult = query_parameters("SELECT * FROM appComments WHERE commentId = '?'",
                            $aClean['iThread']);
    $oRow = mysql_fetch_object($hResult);
    if($oRow)
    {
      $mesTitle = "<b>Replying To ...</b> $oRow->subject\n";
      echo html_frame_start($oRow->subject,500);
      echo htmlify_urls($oRow->body), "<br /><br />\n";
      echo html_frame_end();
    }
  }

  echo "<form method=\"post\" action=\"addcomment.php\">\n";

  echo html_frame_start($mesTitle,500,"",0);
    
  echo '<table width="100%" border=0 cellpadding=0 cellspacing=1>',"\n";
  echo "<tr class=\"color0\"><td align=right><b>From:</b>&nbsp;</td>\n";
  echo "	<td>&nbsp;".$_SESSION['current']->sRealname."</td></tr>\n";
  echo "<tr class=\"color0\"><td align=right><b>Subject:</b>&nbsp;</td>\n";
  echo "	<td>&nbsp;<input type=\"text\" size=\"35\" name=\"sSubject\" value=\"".$aClean['sSubject']."\" /> </td></tr>\n";
  echo "<tr class=\"color1\"><td colspan=2><textarea name=\"sBody\" cols=\"70\" rows=\"15\" wrap=\"virtual\">".$aClean['sBody']."</textarea></td></tr>\n";
  echo "<tr class=\"color1\"><td colspan=2 align=center>\n";
  echo "  <input type=\"submit\" value=\"Post Comment\" class=\"button\" />\n";
  echo "  <input type=\"reset\" value=\"Reset\" class=\"button\" />\n";
  echo "</td></tr>\n";
  echo "</table>\n";

  echo html_frame_end();

  echo "<input type=\"hidden\" name=\"iThread\" value=\"".$aClean['iThread']."\" />\n";
  echo "<input type=\"hidden\" name=\"iAppId\" value=\"".$aClean['iAppId']."\" />\n";
  echo "<input type=\"hidden\" name=\"iVersionId\" value=\"".$aClean['iVersionId']."\" />\n";
  echo "</form>";
}

apidb_footer();
?>
