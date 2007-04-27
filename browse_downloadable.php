<?php

/**
*  Browse downloadable applications
*/

require("path.php");
require(BASE."include/incl.php");

apidb_header("Browse Downloadable Applications");

/* Match specific license? */
$sLicense = version::checkLicense($aClean['sLicense']);

/* Set default values */
if(!$aClean['iNumVersions'] || $aClean['iNumVersions'] > 200 || $aClean['iNumVersions'] < 0)
    $aClean['iNumVersions'] = 25;

if(!$aClean['iPage'])
    $aClean['iPage'] = 1;

/* Count the possible matches */
$sQuery = "SELECT DISTINCT appFamily.appName,
    appVersion.versionName, appVersion.versionId, appFamily.description
        FROM appFamily, appVersion, appData
            WHERE appData.type = '?' AND appData.versionId = appVersion.versionId
            AND appFamily.appId = appVersion.appId AND appVersion.queued = 'false'";

if(!$sLicense)
    $hResult = query_parameters($sQuery, "downloadurl");
else
{
    $sQuery .= " AND license = '?'";
    $hResult = query_parameters($sQuery, "downloadurl", $sLicense);
}

if($hResult && mysql_num_rows($hResult))
    $num = mysql_num_rows($hResult);

$iNumPages = ceil($num / $aClean['iNumVersions']);

/* Check page logic */
$aClean['iPage'] = min($aClean['iPage'], $iNumPages);

/* Calculate values for the LIMIT clause */
$iLimitLower = ($aClean['iPage'] - 1) * $aClean['iNumVersions'];

/* Page selection */
echo "<div align=\"center\">\n";
echo "<b>Page ".$aClean['iPage']." of $iNumPages</b><br />\n";
display_page_range($aClean['iPage'], $iPageRange, $iNumPages,
    $_SERVER['PHP_SELF']."?iNumVersions=".$aClean['iNumVersions']."&sLicense=".
    $aClean['sLicense']);

/* Selector for how many versions to display */
echo "<form method=\"get\" action=\"".$_SERVER['PHP_SELF']."\">\n";
echo "<b>How many versions to display:</b>\n";
echo "<select name=\"iNumVersions\">\n";

$numVersionsArray = array(1, 25, 50, 100, 200);

foreach($numVersionsArray as $i)
{
    if($i == $aClean['iNumVersions'])
        echo "<option selected=\"selected\">$i</option>\n";
    else
        echo "<option>$i</option>\n";
}

echo "</select>\n";

echo "<b>Filter by license</b>\n";
$oVersion = new Version();
echo $oVersion->makeLicenseList($sLicense);

echo " <input type=\"submit\" value=\"Refresh\" />\n";
echo "</form></div>\n<br />\n";

if(!$num)
{
    echo "<div align=\"center\"><font color=\"red\">No matches found</font></div>\n";
    echo html_frame_end("&nbsp;");
    exit;
}

$sQuery = "SELECT DISTINCT appFamily.appName,
        appVersion.versionName, appVersion.versionId, appFamily.description
            FROM appFamily, appVersion, appData
                WHERE appData.type = '?' AND appData.versionId = appVersion.versionId
                AND appFamily.appId = appVersion.appId 
                AND appVersion.queued = 'false' ";

if(!$sLicense)
{
    $sQuery .= "ORDER BY appFamily.appName LIMIT ?, ?";
    $hResult = query_parameters($sQuery, "downloadurl", $iLimitLower,
                                $aClean['iNumVersions']);
} else
{
    $sQuery .= "AND license = '?' ORDER BY appFamily.appName LIMIT ?, ?";
    $hResult = query_parameters($sQuery,
                        "downloadurl", $sLicense, $iLimitLower,
                        $aClean['iNumVersions']);
}

if($hResult && mysql_num_rows($hResult))
{
    echo html_frame_start("", "90%");
    echo html_table_begin("width=\"100%\" align=\"center\"");
    echo html_tr(array(
        "<b>Name</b>",
        "<b>Description</b>"),
        "color4");

    for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
    {
        $oVersion = new version($oRow->versionId);
        echo html_tr_highlight_clickable(
            $oVersion->objectMakeUrl(),
            ($i % 2) ? "color1" : "color0",
            ($i % 2) ? "color1" : "color0",
            ($i % 2) ? "color1" : "color0");
        echo "<td>".version::fullNameLink($oVersion->iVersionId)."</td>\n";
        echo "<td>$oRow->description</td>\n";
        echo "</tr>\n";
    }

    echo html_table_end();
    echo html_frame_end("&nbsp;");
}

?>
