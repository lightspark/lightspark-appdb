<?php
/************************************************************/
/* Page for managing all of the screenshots in the AppDB    */
/* Without having go into each application version to do so */
/************************************************************/

include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."screenshot.php");

apidb_header("Screenshots");

// deny access if not admin
if(!havepriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}


// regenerate all screenshots
if($_REQUEST['regenerate'])
{
    $sQuery = "SELECT id FROM appData";
    $hResult = query_appdb($sQuery);
    while($oRow = mysql_fetch_object($hResult))
    {
        echo "REGENERATING IMAGE ".$oRow->id."<br/>";
        $screenshot = new Screenshot($oRow->id);
        $screenshot->generate();
        $screenshot->free();
        set_time_limit(60);
    }
}

echo "<a href=\"".$_SERVER['PHP_SELF']."?regenerate=true\">Regenerate all screenshots ! (use only if you know what you are doing)</a><br />";

function display_range($currentPage, $pageRange, $totalPages, $screenshotsPerPage)
{
    /* display the links to each of these pages */
    if($currentPage != 0)
    {
        $previousPage = $currentPage - 1;
        echo "<a href='adminScreenshots.php?page=$previousPage&screenshotsPerPage=$screenshotsPerPage'>Previous</a> ";
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
            echo "<a href='adminScreenshots.php?page=$x&screenshotsPerPage=$screenshotsPerPage'>$x</a> ";
        else
            echo "$x ";
    }

    if($currentPage < $totalPages)
    {
        $nextPage = $currentPage + 1;
        echo "<a href='adminScreenshots.php?page=$nextPage&screenshotsPerPage=$screenshotsPerPage'>Next</a> ";
    } else
        echo "Next ";
}

$screenshotsPerPage = 10;
$currentPage = 0;

if($_REQUEST['page'])
    $currentPage = $_REQUEST['page'];

if($_REQUEST['screenshotsPerPage'])
    $screenshotsPerPage = $_REQUEST['screenshotsPerPage'];

$totalPages = floor(getNumberOfComments()/$screenshotsPerPage);

if($screenshotsPerPage > 100) $screenshotsPerPage = 100;

/* display page selection links */
echo "<center>";
echo "<b>Page $currentPage of $totalPages</b><br />";
display_range($currentPage, $pageRange, $totalPages, $screenshotsPerPage);
echo "<br />";
echo "<br />";

/* display the option to choose how many comments per-page to disable */
echo "<form method=\"get\" name=\"message\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>Number of comments per page:</b>";
echo "<select name='screenshotsPerPage'>";

$screenshotsPerPageArray = array(10, 20, 50, 100);
foreach($screenshotsPerPageArray as $i => $value)
{
    if($screenshotsPerPageArray[$i] == $screenshotsPerPage)
        echo "<option value='$screenshotsPerPageArray[$i]' SELECTED>$screenshotsPerPageArray[$i]";
    else
        echo "<option value='$screenshotsPerPageArray[$i]'>$screenshotsPerPageArray[$i]";
}
echo "</select>";

echo "<input type=hidden name=page value=$currentPage>";
echo "<input type=submit value='Refresh'>";
echo "</form>";

echo "</center>";

/* query for all of the commentId's, ordering by their time in reverse order */
$offset = $currentPage * $screenshotsPerPage;
$commentIds = query_appdb("SELECT id from appData ORDER BY ".
                          "id ASC LIMIT $offset, $screenshotsPerPage;");
while ($ob = mysql_fetch_object($commentIds))
{
    $qstring = "SELECT id, appId, versionId, type, description ".
        "FROM appData WHERE id = $ob->id;";
    $result = query_appdb($qstring);

    /* call view_app_comment to display the comment */
    $comment_ob = mysql_fetch_object($result);
    // TODO: display the thumbnail with link to screenshot
}

/* display page selection links */
echo "<center>";
display_range($currentPage, $pageRange, $totalPages, $screenshotsPerPage);
echo "</center>";

apidb_footer();

?>
