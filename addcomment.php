<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

$aClean = array(); //array of filtered user input

$aClean['versionId'] = makeSafe($_REQUEST['versionId']);
$aClean['thread'] = makeSafe($_REQUEST['thread']);
$aClean['body'] = makeSafe($_REQUEST['body']);
$aClean['subject'] = makeSafe($_REQUEST['subject']);

/********************************/
/* code to submit a new comment */
/********************************/
    
/*
 * application environment
 */
// you must be logged in to submit comments
if(!$_SESSION['current']->isLoggedIn())
{
  apidb_header("Please login");
  echo "To submit a comment for an application you must be logged in. Please <a href=\"account.php?cmd=login\">login now</a> or create a <a href=\"account.php?cmd=new\">new account</a>.","\n";
  exit;
}

if( !is_numeric($aClean['versionId']) )
{
  util_show_error_page('Internal Database Access Error');
  exit;
}

if(!is_numeric($aClean['thread']))
{
  $aClean['thread'] = 0;
}

############################
# ADDS COMMENT TO DATABASE #
############################
if(!empty($aClean['body']))
{
    $oComment = new Comment();
    $oComment->create($aClean['subject'], $aClean['body'], $aClean['thread'], $aClean['versionId']);
    redirect(apidb_fullurl("appview.php?versionId=".$oComment->iVersionId));
}

################################
# USER WANTS TO SUBMIT COMMENT #
################################
else
{
  apidb_header("Add Comment");

  $mesTitle = "<b>Post New Comment</b>";

  if($aClean['thread'] > 0)
  {
    $hResult = query_parameters("SELECT * FROM appComments WHERE commentId = '?'",
                            $aClean['thread']);
    $oRow = mysql_fetch_object($hResult);
    if($oRow)
    {
      $mesTitle = "<b>Replying To ...</b> $oRow->subject\n";
      $originator = $oRow->userId;
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
  echo "	<td>&nbsp;<input type=\"text\" size=\"35\" name=\"subject\" value=\"".$aClean['subject']."\" /> </td></tr>\n";
  echo "<tr class=\"color1\"><td colspan=2><textarea name=\"body\" cols=\"70\" rows=\"15\" wrap=\"virtual\">".$aClean['body']."</textarea></td></tr>\n";
  echo "<tr class=\"color1\"><td colspan=2 align=center>\n";
  echo "  <input type=\"submit\" value=\"Post Comment\" class=\"button\" />\n";
  echo "  <input type=\"reset\" value=\"Reset\" class=\"button\" />\n";
  echo "</td></tr>\n";
  echo "</table>\n";

  echo html_frame_end();

  echo "<input type=\"hidden\" name=\"thread\" value=\"".$aClean['thread']."\" />\n";
  echo "<input type=\"hidden\" name=\"appId\" value=\"".$aClean['appId']."\" />\n";
  echo "<input type=\"hidden\" name=\"versionId\" value=\"".$aClean['versionId']."\" />\n";
  if (!empty($aClean['thread']))
  {
    echo "<input type=\"hidden\" name=\"originator\" value=\"$originator\" />\n";
  }
  echo "</form>";
}

apidb_footer();
?>
