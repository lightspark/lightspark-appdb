<?php
/************************************************************/
/* Page for managing all of the comments in the apidb       */
/* Without having go into each application version to do so */
/************************************************************/

include("path.php");
include(BASE."include/incl.php");
require(BASE."include/comment.php");

apidb_header("Comments");

function display_range($currentPage, $pageRange, $totalPages, $commentsPerPage)
{
    /* display the links to each of these pages */
    if($currentPage != 0)
    {
        $previousPage = $currentPage - 1;
        echo "<a href='adminCommentView.php?page=$previousPage&commentsPerPage=$commentsPerPage'>Previous</a> ";
    } else
        echo "Previous ";

    /* display the next 10 and previous 10 pages */
    $pageRange = 10;

    if($currentPage > $pageRange)
        $startPage = $currentPage - $pageRange;
    else
        $startPage = 0;

    if($currentPage + $pageRange < $totalPages)
        $endPage = $currentPage + $pageRange;
    else
        $endPage = $totalPages;

    /* display the desired range */
    for($x = $startPage; $x <= $endPage; $x++)
    {
        if($x != $currentPage)
            echo "<a href='adminCommentView.php?page=$x&commentsPerPage=$commentsPerPage'>$x</a> ";
        else
            echo "$x ";
    }

    if($currentPage < $totalPages)
    {
        $nextPage = $currentPage + 1;
        echo "<a href='adminCommentView.php?page=$nextPage&commentsPerPage=$commentsPerPage'>Next</a> ";
    } else
        echo "Next ";
}

$commentsPerPage = 10;
$currentPage = 0;

if($_REQUEST['page'])
    $currentPage = $_REQUEST['page'];

if($_REQUEST['commentsPerPage'])
    $commentsPerPage = $_REQUEST['commentsPerPage'];

$totalPages = floor(getNumberOfComments()/$commentsPerPage);

if($commentsPerPage > 100) $commentsPerPage = 100;

/* display page selection links */
echo "<center>";
echo "<b>Page $currentPage of $totalPages</b><br />";
display_range($currentPage, $pageRange, $totalPages, $commentsPerPage);
echo "<br />";
echo "<br />";

/* display the option to choose how many comments per-page to display */
echo "<form method=\"get\" name=\"message\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>Number of comments per page:</b>";
echo "<select name='commentsPerPage'>";

$commentsPerPageArray = array(10, 20, 50, 100);
foreach($commentsPerPageArray as $i => $value)
{
    if($commentsPerPageArray[$i] == $commentsPerPage)
        echo "<option value='$commentsPerPageArray[$i]' SELECTED>$commentsPerPageArray[$i]";
    else
        echo "<option value='$commentsPerPageArray[$i]'>$commentsPerPageArray[$i]";
}
echo "</select>";

echo "<input type=hidden name=page value=$currentPage>";
echo "<input type=submit value='Refresh'>";
echo "</form>";

echo "</center>";

/* query for all of the commentId's, ordering by their time in reverse order */
$offset = $currentPage * $commentsPerPage;
$commentIds = query_appdb("SELECT commentId from appComments ORDER BY ".
                          "appComments.time ASC LIMIT $offset, $commentsPerPage;");
while ($ob = mysql_fetch_object($commentIds))
{
    $qstring = "SELECT from_unixtime(unix_timestamp(time), \"%W %M %D %Y, %k:%i\") as time, ".
        "commentId, parentId, versionId, userid, subject, body ".
        "FROM appComments WHERE commentId = $ob->commentId;";
    $result = query_appdb($qstring);
    /* call view_app_comment to display the comment */
    $comment_ob = mysql_fetch_object($result);
    view_app_comment($comment_ob);
}

/* display page selection links */
echo "<center>";
display_range($currentPage, $pageRange, $totalPages, $commentsPerPage);
echo "</center>";

apidb_footer();
?>
