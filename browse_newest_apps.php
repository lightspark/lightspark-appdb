<?php
/**
 * Browse newest versions
 *
 */

require("path.php");
require(BASE."include/incl.php");
require(BASE."include/filter.php");

// set default value
if(empty($aClean['iNumVersions']) || $aClean['iNumVersions'] > 200 || $aClean['iNumVersions'] < 0)
    $aClean['iNumVersions'] = 25;

apidb_header("Browse Newest Applications");

/* selector for how many versions to view */
echo "<form method=\"post\" name=\"sMessage\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>How many versions to display:</b>";
echo "<select name='iNumVersions'>";

$numVersionsArray = array(25, 50, 100, 200);

foreach($numVersionsArray as $i => $value)
{
    if($numVersionsArray[$i] == $aClean['iNumVersions'])
        echo "<option selected=\"selected\">$numVersionsArray[$i]</option>";
    else
        echo "<option>$numVersionsArray[$i]</option>";
}
echo "</select>";

echo ' <input type="submit" value="Refresh" />';
echo '</form>';
echo '<br />';

/* Query the database for the n newest versions */
$hResult = query_parameters("SELECT appId, appName, description, submitTime FROM appFamily WHERE
                            queued = 'false' ORDER BY appId DESC LIMIT ?",
                            $aClean['iNumVersions']);

if($hResult)
{
    echo html_frame_start("", "90%", '', 0);
    echo html_table_begin("width=\"100%\" align=\"center\"");
    echo "<tr class=\"color4\">\n";
    echo "<td><font color=\"white\">Submission Date</font></td>\n";
    echo "<td><font color=\"white\">Application</font></td>\n";
    echo "<td><font color=\"white\">Description</font></td></td>\n";
    
    $c = 1;
    while($row = mysql_fetch_object($hResult))
    {
        $bgcolor = ($c % 2) ? "color0" : "color1";
        $link = "<a href=\"appview.php?iAppId=$row->appId\"> $row->appName </a>";
        echo "<tr class=\"$bgcolor\">";
        echo "<td width=\"20%\">".print_short_date(mysqltimestamp_to_unixtimestamp($oApp->sSubmitTime))."</td>\n";
        echo "<td>$link </td>\n";
        echo "<td>$row->description </td></tr>\n";
        $c++;
    }

    echo html_table_end();
    echo html_frame_end();
}

apidb_footer();

?>
