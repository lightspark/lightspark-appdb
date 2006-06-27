<?php
/************************************************************/
/* Page for managing all of the comments in the apidb       */
/* Without having go into each application version to do so */
/************************************************************/

include("path.php");
include(BASE."include/incl.php");

apidb_header("Comments");

/* display a range of 10 pages */
$pageRange = 10;

$ItemsPerPage = 10;
$currentPage = 1;

$aClean = array(); //array of filtered user input

$aClean['ItemsPerPage'] = makeSafe($_REQUEST['ItemsPerPage']);
$aClean['page'] = makeSafe($_REQUEST['page']);

if($aClean['ItemsPerPage'])
    $ItemsPerPage = $aClean['ItemsPerPage'];
if($aClean['page'])
    $currentPage = $aClean['page'];

$totalPages = ceil(getNumberOfComments()/$ItemsPerPage);

if($ItemsPerPage > 100) $ItemsPerPage = 100;


/* display page selection links */
echo "<center>";
echo "<b>Page $currentPage of $totalPages</b><br />";
display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?ItemsPerPage=".$ItemsPerPage);
echo "<br />";
echo "<br />";

/* display the option to choose how many comments per-page to display */
echo "<form method=\"get\" name=\"message\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>Number of comments per page:</b>";
echo "&nbsp<select name='ItemsPerPage'>";

$ItemsPerPageArray = array(10, 20, 50, 100, 500);
foreach($ItemsPerPageArray as $i => $value)
{
    if($ItemsPerPageArray[$i] == $ItemsPerPage)
        echo "<option value='$ItemsPerPageArray[$i]' SELECTED>$ItemsPerPageArray[$i]";
    else
        echo "<option value='$ItemsPerPageArray[$i]'>$ItemsPerPageArray[$i]";
}
echo "</select>";

echo "<input type=hidden name=page value=$currentPage>";
echo "&nbsp<input type=submit value='Refresh'>";
echo "</form>";

echo "</center>";

/* query for all of the commentId's, ordering by their time in reverse order */
$offset = (($currentPage-1) * $ItemsPerPage);
$commentIds = query_parameters("SELECT commentId from appComments ORDER BY ".
                           "appComments.time ASC LIMIT ?, ?", $offset, $ItemsPerPage);
while ($ob = mysql_fetch_object($commentIds))
{
    $sQuery = "SELECT from_unixtime(unix_timestamp(time), \"%W %M %D %Y, %k:%i\") as time, ".
        "commentId, parentId, versionId, userid, subject, body ".
        "FROM appComments WHERE commentId = '?'";
    $hResult = query_parameters($sQuery, $ob->commentId);
    /* call view_app_comment to display the comment */
    $comment_ob = mysql_fetch_object($hResult);
    view_app_comment($comment_ob);
}

/* display page selection links */

echo "<center>";
display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?ItemsPerPage=".$ItemsPerPage);
echo "</center>";

apidb_footer();
?>
