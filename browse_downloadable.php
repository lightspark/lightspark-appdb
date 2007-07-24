<?php

/**
*  Browse downloadable applications
*/

require("path.php");
require(BASE."include/incl.php");

apidb_header("Browse Downloadable Applications");

echo "<div class='default_container'>\n";

/* Match specific license? */
$sLicenseString = isset($aClean['sLicense']) ? $aClean['sLicense'] : '';
$sLicense = version::checkLicense( $sLicenseString );

/* Set default values */
if(!isset($aClean['iNumVersions']) || $aClean['iNumVersions'] > 200 || $aClean['iNumVersions'] < 0)
    $aClean['iNumVersions'] = 25;

if( !isset($aClean['iPage']) )
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

$iNumPages = isset($num) ? ceil($num/$aClean['iNumVersions']) : 0;

/* Check page logic */
$aClean['iPage'] = min($aClean['iPage'], $iNumPages);

/* Calculate values for the LIMIT clause */
$iLimitLower = ($aClean['iPage'] - 1) * $aClean['iNumVersions'];

/* Page selection */
echo "<div align=\"center\">\n";
echo "<b>Page ".$aClean['iPage']." of $iNumPages</b><br />\n";
// $iPageRange is non-existent here? creating it
$iPageRange = 10;
display_page_range($aClean['iPage'], $iPageRange, $iNumPages,
    $_SERVER['PHP_SELF']."?iNumVersions=".$aClean['iNumVersions']."&sLicense=".
    $sLicenseString);

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

if(!isset($num))
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
    
    $oTable = new Table();
    $oTable->SetWidth("100%");
    $oTable->SetAlign("center");

    $oTableRow = new TableRow();
    $oTableCell = new TableCell("Name");
    $oTableCell->SetBold(true);
    $oTableRow->AddCell($oTableCell);
    $oTableCell = new TableCell("Description");
    $oTableCell->SetBold(true);
    $oTableRow->AddCell($oTableCell);

    $oTableRow->SetClass("color4");
    $oTable->AddRow($oTableRow);

    for($iIndex = 1; $oRow = mysql_fetch_object($hResult); $iIndex++)
    {
        $oVersion = new version($oRow->versionId);

        $oTableRow = new TableRow();
        if($iIndex % 2)
          $sColor = "color1";
        else
          $sColor = "color0";

        $oTableRow->SetClass($sColor);

        $oTableRowHighlight = GetStandardRowHighlight($iIndex);

        $oTableRowClick = new TableRowClick($oVersion->objectMakeUrl());
        $oTableRowClick->SetHighlight($oTableRowHighlight);

        $oTableRow->SetRowClick($oTableRowClick);

        $oTableRow->AddCell(new TableCell(version::fullNameLink($oVersion->iVersionId)));
        $oTableRow->AddCell(new TableCell($oRow->description));
        $oTable->AddRow($oTableRow);
    }

    echo $oTable->GetString();

    echo html_frame_end("&nbsp;");
}

echo "</div>\n";

apidb_footer();

?>
