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

$sPathtrail = "<a href=\"browse_by_rating.php\">Main</a>";

echo html_frame_start("", '98%', '', 2);

if (empty($aClean['sRating']))
{
    echo "<b>Rating: $sPathtrail</b>";
    echo html_frame_end();
    echo html_frame_start("", '98%', '', 2);
    echo "<table width=100% border=0 cellspacing=1 cellpadding=3\n";
    echo "  <tr class=color4>\n";
    echo "    <td><b>Rating</b></td>\n";
    echo "    <td><b>Description</b></td>\n";
    echo "    <td><b>No. Apps</b></td>\n";
    echo "  </tr>\n";
    html_tr_highlight_clickable("browse_by_rating.php?sRating=".PLATINUM_RATING, "platinum", "platinum", "platinum");
    echo "    <td><a href=\"browse_by_rating.php?sRating=".PLATINUM_RATING."\">Platinum</a></td>";
    echo "    <td>Applications that install and run out of the box</td>\n";
    echo "    <td>".Application::countWithRating(PLATINUM_RATING)."</td>\n";
    echo "  </tr>\n";
    html_tr_highlight_clickable("browse_by_rating.php?sRating=".GOLD_RATING, "gold", "gold", "gold");
    echo "    <td><a href=\"browse_by_rating.php?sRating=".GOLD_RATING."\">Gold</a></td>";
    echo "    <td>Applications that work flawlessly with some DLL overrides or other settings, crack etc.</td>\n";
    echo "    <td>".Application::countWithRating(GOLD_RATING)."</td>\n";
    echo "  </tr>\n";
    html_tr_highlight_clickable("browse_by_rating.php?sRating=".SILVER_RATING, "silver", "silver", "silver");
    echo "    <td><a href=\"browse_by_rating.php?sRating=".SILVER_RATING."\">Silver</a></td>";
    echo "    <td>Applications that work excellently for 'normal use'</td>\n";
    echo "    <td>".Application::countWithRating(SILVER_RATING)."</td>\n";
    echo "  </tr>\n";
    html_tr_highlight_clickable("browse_by_rating.php?sRating=".BRONZE_RATING, "bronze", "bronze", "bronze");
    echo "    <td><a href=\"browse_by_rating.php?sRating=".BRONZE_RATING."\">Bronze</a></td>";
    echo "    <td>Applications that work but have some issues, even for 'normal use'</td>\n";
    echo "    <td>".Application::countWithRating(BRONZE_RATING)."</td>\n";
    echo "  </tr>\n";
    html_tr_highlight_clickable("browse_by_rating.php?sRating=".GARBAGE_RATING, "garbage", "garbage", "garbage");
    echo "    <td><a href=\"browse_by_rating.php?sRating=".GARBAGE_RATING."\">Garbage</a></td>";
    echo "    <td>Applications that don't work as intended, there should be at least one bug report if an app gets this rating</td>\n";
    echo "    <td>".Application::countWithRating(GARBAGE_RATING)."</td>\n";
    echo "  </tr>\n";
    echo "</table>\n";

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
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";
    
    echo "<tr class=color4>\n";
    echo "    <td><b>Application Name</b></td>\n";
    echo "    <td><b>Description</b></td>\n";
    echo "    <td><b>No. Versions</b></td>\n";
    echo "</tr>\n\n";
	    
    while(list($i, $iAppId) = each($apps))
    {
        $oApp = new Application($iAppId);

        //set row color
        $bgcolor = ($i % 2) ? "color0" : "color1";
        
        //format desc
        $desc = util_trim_description($oApp->sDescription);
	
        //display row
        echo "<tr class=$bgcolor>\n";
        echo "    <td>".$oApp->objectMakeLink()."</td>\n";
        echo "    <td>$desc &nbsp;</td>\n";
        echo "    <td>".sizeof($oApp->aVersionsIds)."</td>\n";
        echo "</tr>\n\n";
    }
    
    echo "</table>\n\n";

    echo html_frame_end();
    echo "<center>";
    display_page_range($iCurrentPage, $iPageRange, $iTotalPages,
                  $_SERVER['PHP_SELF']."?sRating=".$aClean['sRating']."&iItemsPerPage=".$iItemsPerPage);    
    echo "</center>";
}


apidb_footer();

?>
