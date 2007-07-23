<?php
/**
 * Browse Applications by their respective ratings
 *
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/application.php");

apidb_header("Browse Applications by Rating");

echo "<div class='default_container'>\n";

$sPathtrail = "<a href=\"browse_by_rating.php\">Main</a>";

echo html_frame_start("", '98%', '', 2);

if (empty($aClean['sRating']))
{
    echo "<b>Rating: $sPathtrail</b>";
    echo html_frame_end();
    echo html_frame_start("", '98%', '', 2);

    // create the table
    $oTable = new Table();
    $oTable->SetCellSpacing(1);
    $oTable->SetCellPadding(3);
    $oTable->SetBorder(0);
    $oTable->SetWidth("100%");

    // create the header row
    $aHeaderCells = array();
    $oTableCell = new TableCell("Rating");
    $oTableCell->SetBold(true);
    $aHeaderCells[] = $oTableCell;

    $oTableCell = new TableCell("Description");
    $oTableCell->SetBold(true);
    $aHeaderCells[] = $oTableCell;

    $oTableCell = new TableCell("No. Apps");
    $oTableCell->SetBold(true);
    $aHeaderCells[] = $oTableCell;

    $oTableRowHeader = new TableRow();
    $oTableRowHeader->AddCells($aHeaderCells);
    $oTableRowHeader->SetClass("color4");

    $oTable->SetHeader($oTableRowHeader);

    // setup arrays for processing in the below loop
    $aColorName = array("Platinum", "Gold", "Silver", "Bronze", "Garbage");
    $aRating = array(PLATINUM_RATING, GOLD_RATING, SILVER_RATING,
                     BRONZE_RATING, GARBAGE_RATING);
    $aRatingText = array("Applications that install and run out of the box",
                         "Applications that work flawlessly with some DLL overrides or other settings, crack etc.",
                         "Applications that work excellently for 'normal use'",
                         "Applications that work but have some issues, even for 'normal use'",
                         "Applications that don't work as intended, there should be at least one bug report if an app gets this rating");
    
    $iIndex = 0;
    foreach($aColorName as $sColor)
    {
      $oTableRow = new TableRow();
      $oTableRow->SetClass($aRating[$iIndex]);

      $sUrl = "browse_by_rating.php?sRating=".$aRating[$iIndex];

      $oTableCell = new TableCell($aColorName[$iIndex]);
      $oTableCell->SetCellLink($sUrl);
      $oTableRow->AddCell($oTableCell);
      $oTableRow->AddTextCell($aRatingText[$iIndex]);
      $oTableRow->AddTextCell(Application::countWithRating($aRating[$iIndex]));

      // click entry for the row
      $oInactiveColor = new color();
      $oInactiveColor->SetColorByName($aColorName[$iIndex]);
      $oHighlightColor = GetHighlightColorFromInactiveColor($oInactiveColor);
      $oTableRowHighlight = new TableRowHighlight($oHighlightColor, $oInactiveColor);
      $oTableRowClick = new TableRowClick($sUrl);
      $oTableRowClick->SetHighlight($oTableRowHighlight);

      // set the clickable row
      $oTableRow->SetRowClick($oTableRowClick);

      $oTable->AddRow($oTableRow);

      $iIndex++;
    }

    echo $oTable->GetString();

    echo html_frame_end();
} else
{
    /* display a range of 10 pages */
    $iPageRange = 10;
    $iItemsPerPage = 50;
    $iCurrentPage = 1;

    if($aClean['iItemsPerPage'])
        $iItemsPerPage = $aClean['iItemsPerPage'];
    if($aClean['iPage'])
        $iCurrentPage = $aClean['iPage'];

    $iItemsPerPage = min($iItemsPerPage,500);

    switch($aClean['sRating'])
    {
        case PLATINUM_RATING:
            $sPathtrail.=" > <a href=\"browse_by_rating.php?sRating=".PLATINUM_RATING."\">Platinum</a>";
            $iTotalPages = ceil(Application::countWithRating(PLATINUM_RATING)/$iItemsPerPage);
	    $sRating = PLATINUM_RATING;
        break;

        case GOLD_RATING:
            $sPathtrail.=" > <a href=\"browse_by_rating.php?sRating=".GOLD_RATING."\">Gold</a>";
            $iTotalPages = ceil(Application::countWithRating(GOLD_RATING)/$iItemsPerPage);
            $sRating = GOLD_RATING;
        break;

        case SILVER_RATING:
            $sPathtrail.=" > <a href=\"browse_by_rating.php?sRating=".SILVER_RATING."\">Silver</a>";
            $iTotalPages = ceil(Application::countWithRating(SILVER_RATING)/$iItemsPerPage);
            $sRating = SILVER_RATING;
        break;
        case BRONZE_RATING:
            $sPathtrail.=" > <a href=\"browse_by_rating.php?sRating=".BRONZE_RATING."\">Bronze</a>";
            $iTotalPages = ceil(Application::countWithRating(BRONZE_RATING)/$iItemsPerPage);
            $sRating = BRONZE_RATING;
        break;
        case GARBAGE_RATING:
        $sPathtrail.=" > <a href=\"browse_by_rating.php?sRating=".GARBAGE_RATING."\">Garbage</a>";
            $iTotalPages = ceil(Application::countWithRating(GARBAGE_RATING)/$iItemsPerPage);
            $sRating=GARBAGE_RATING;
        break;

    }
    
    $iCurrentPage = min($iCurrentPage,$iTotalPages);
    $iOffset = (($iCurrentPage-1) * $iItemsPerPage);
    $apps=Application::getWithRating($sRating, $iOffset, $iItemsPerPage);
    
    echo "<b>Rating: $sPathtrail</b><p>";
    echo html_frame_end();

    
    

    /* display page selection links */
    echo "<center>";
    echo "<b>Page $iCurrentPage of $iTotalPages</b><br />";
        display_page_range($iCurrentPage, $iPageRange, $iTotalPages,
                  $_SERVER['PHP_SELF']."?sRating=".$aClean['sRating']."&iItemsPerPage=".$iItemsPerPage);
    echo "<br />";
    echo "<br />";
    
    /* display the option to choose how many applications per-page to display */
    echo '<form method="get" name="message" action="'.$_SERVER['PHP_SELF'].'">';
    echo '<b>Number of Applications per page:</b>';
    echo "&nbsp<select name='iItemsPerPage'>";

    $iItemsPerPageArray = array(50, 100, 150, 200, 250, 300, 350, 400, 450, 500);
    foreach($iItemsPerPageArray as $i => $value)
    {
        if($iItemsPerPageArray[$i] == $iItemsPerPage)
            echo "<option value='$iItemsPerPageArray[$i]' SELECTED>$iItemsPerPageArray[$i]";
        else
            echo "<option value='$iItemsPerPageArray[$i]'>$iItemsPerPageArray[$i]";
    }
    echo "</select>";

    echo "<input type=hidden name=iPage value=$iCurrentPage>";
    echo "<input type=hidden name=sRating value=".$aClean['sRating'].">";
    echo "&nbsp<input type=submit value='Refresh'>";
    echo "</form>";
    echo "</center>";

    echo html_frame_start("","98%","",0);

    $oTable = new Table();
    $oTable->SetWidth("100%");
    $oTable->SetBorder(0);
    $oTable->SetCellPadding(3);
    $oTable->SetCellSpacing(1);

    $oTableRow = new TableRow();
    $oTableRow->SetClass("color4");

    $oTableCell = new TableCell("Application Name");
    $oTableCell->SetBold(true);
    $oTableRow->AddCell($oTableCell);

    $oTableCell = new TableCell("Description");
    $oTableCell->SetBold(true);
    $oTableRow->AddCell($oTableCell);
    
    $oTableCell = new TableCell("No. Versions");
    $oTableCell->SetBold(true);
    $oTableRow->AddCell($oTableCell);

    $oTable->AddRow($oTableRow);
	    
    while(list($iIndex, $iAppId) = each($apps))
    {
        $oApp = new Application($iAppId);

        $oTableRowHighlight = GetStandardRowHighlight($iIndex);

        $sUrl = $oApp->objectMakeUrl();

        $sColor = ($iIndex % 2) ? "color0" : "color1";

        $oTableRowClick = new TableRowClick($sUrl);
        $oTableRowClick->SetHighlight($oTableRowHighlight);

        //format desc
        $sDesc = util_trim_description($oApp->sDescription);
	
        //display row
        $oTableRow = new TableRow();
        $oTableRow->SetRowClick($oTableRowClick);
        $oTableRow->SetClass($sColor);

        $oTableRow->AddTextCell($oApp->objectMakeLink());
        $oTableRow->AddTextCell("$sDesc &nbsp;");
        $oTableRow->AddTextCell(sizeof($oApp->aVersionsIds));

        $oTable->AddRow($oTableRow);
    }
    
    // output the table
    echo $oTable->GetString();

    echo html_frame_end();
    echo "<center>";
    display_page_range($iCurrentPage, $iPageRange, $iTotalPages,
                  $_SERVER['PHP_SELF']."?sRating=".$aClean['sRating']."&iItemsPerPage=".$iItemsPerPage);    
    echo "</center>";
}

echo "</div>\n";

apidb_footer();

?>
