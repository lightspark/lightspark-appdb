<?php
/************************************************************/
/* Page for managing all of the screenshots in the AppDB    */
/* Without having go into each application version to do so */
/************************************************************/

include("path.php");
include(BASE."include/incl.php");
include(BASE."include/filter.php");
require_once(BASE."include/screenshot.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

apidb_header("View Screenshots");
/* display a range of 10 pages */
$pageRange = 10;

$ItemsPerPage = 6;
$currentPage = 1;

if($aClean['iItemsPerPage'])
    $ItemsPerPage = $aClean['iItemsPerPage'];
if($aClean['iPage'])
    $currentPage = $aClean['iPage'];

$ItemsPerPage = min($ItemsPerPage,100);
$totalPages = ceil(getNumberOfImages()/$ItemsPerPage);
$currentPage = min($currentPage,$totalPages);
$offset = (($currentPage-1) * $ItemsPerPage);


/* display page selection links */
echo "<center>";
echo "<b>Page $currentPage of $totalPages</b><br />";
display_page_range($currentPage, $pageRange, $totalPages,
                  $_SERVER['PHP_SELF']."?iItemsPerPage=".$ItemsPerPage);
echo "<br />";
echo "<br />";

/* display the option to choose how many screenshots per-page to display */
echo '<form method="get" name="message" action="'.$_SERVER['PHP_SELF'].'">';
echo '<b>Number of Screenshots per page:</b>';
echo "&nbsp<select name='iItemsPerPage'>";

$ItemsPerPageArray = array(6, 9, 12, 15, 18, 21, 24);
foreach($ItemsPerPageArray as $i => $value)
{
    if($ItemsPerPageArray[$i] == $ItemsPerPage)
        echo "<option value='$ItemsPerPageArray[$i]' SELECTED>$ItemsPerPageArray[$i]";
    else
        echo "<option value='$ItemsPerPageArray[$i]'>$ItemsPerPageArray[$i]";
}
echo "</select>";

echo "<input type=hidden name=iPage value=$currentPage>";
echo "&nbsp<input type=submit value='Refresh'>";
echo "</form>";

echo "</center>";

/* query for all of the Screenshots in assending order */
$Ids = query_parameters("SELECT * from appData 
                     WHERE type = 'image' 
                     ORDER BY id ASC LIMIT ?, ?",
                    $offset, $ItemsPerPage);
$c = 1;
echo "<div align=center><table><tr>\n";
while ($oRow = mysql_fetch_object($Ids))
{
    // display thumbnail
    $oVersion = new version($oRow->versionId);
    $oApp = new Application($oVersion->iAppId);
    $img = get_thumbnail($oRow->id);
    echo "<td align=center>\n";
    echo $img;
    echo "<div align=center>". substr($oRow->description,0,20). "\n";

    echo "<br />[<a href='".apidb_fullurl("appview.php");
    echo "?iAppId=".$oApp->iAppId."'>";
    echo $oApp->sName."</a>]";    

    echo "<br />[<a href='".apidb_fullurl("appview.php");
    echo "?iVersionId=".$oVersion->iVersionId."'>";
    echo "Version: ".$oVersion->sName."</a>]";
    
    echo "</div></td>\n";
   // end row if counter of 3
   if ($c % 3 == 0) echo "</tr><tr>\n";
   $c++;

}
echo "</tr></table></div><br />\n";

/* display page selection links */
echo "<center>";
display_page_range($currentPage, $pageRange, $totalPages,
                   $_SERVER['PHP_SELF']."?iItemsPerPage=".$ItemsPerPage);
echo "</center>";

apidb_footer();

?>
