<?php
/**
 * Shows all versions that have the same bug link.
 *
 * Mandatory parameters:
 *  - iBugId, bug identifier
 * 
 * TODO:
 *  - replace the check is_numeric($aClean['iBugId']) with an is_empty check when filtering is in place
 */

// application environment
require("path.php");
require(BASE."include/incl.php");

/* code to View versions affected by a Bug */

if(!is_numeric($aClean['iBugId']))
    util_show_error_page_and_exit("Something went wrong with the bug ID");

    apidb_header("Applications affected by Bug #".$aClean['iBugId']);
    echo '<form method=post action="viewbugs.php?iBugId='.$aClean['iBugId'].'">',"\n";

    echo '<table width=100% border=0 cellpadding=3 cellspacing=1>',"\n";
    echo '<tr class=color4>',"\n";
    echo '    <td width=80>Application Name</td>',"\n";
    echo '    <td>Description</td>',"\n";
    echo '    <td width=80>version</td>',"\n";
    echo '    <td>Downloads</td>',"\n";
    echo '</tr>',"\n";


    $hResult = query_parameters("SELECT appFamily.description as appDescription,
                           appFamily.appName as appName,
                           appVersion.*, buglinks.versionId as versionId
                           FROM appFamily, appVersion, buglinks
                           WHERE appFamily.appId = appVersion.appId 
                           and buglinks.versionId = appVersion.versionId
                           AND buglinks.bug_id = '?'
                           ORDER BY versionName", $aClean['iBugId']);
    $c = 0;

    if($hResult)
    {
        while($oRow = query_fetch_object($hResult))
        {
            $oApp = new application($oRow->appId);
            $oVersion = new version($oRow->versionId);
            $sDownloadUrls = "";
            if($hDownloadUrls = appData::getData($oRow->versionId, "downloadurl"))
            {
                while($oDownloadUrl = query_fetch_object($hDownloadUrls))
                    $sDownloadUrls .= "<a href=\"$oDownloadUrl->url\">".
                            "$oDownloadUrl->description</a><br>";
            }

            // set row color
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";
            echo '<tr class='.$bgcolor.'>',"\n";
            echo '    <td>',"\n";
            echo "    ".$oApp->objectMakeLink()."\n";
            echo '    </td>',"\n";
            echo '    <td>'.$oRow->appDescription.'</td>',"\n";
            echo '    <td>',"\n";
            echo "    ".$oVersion->objectMakeLink()."\n";
            echo '    </td>',"\n";
            echo "    <td>$sDownloadUrls</td>\n";
            echo '</tr>',"\n";
        }
    }

    // allow users to search for other apps
    echo '<tr class=color2>',"\n";
    echo '    <td align=center colspan=5>&nbsp;</td>',"\n";
    echo '</tr>',"\n";

    echo '<tr class=color4>',"\n";
    echo '    <td colspan=4 >&nbsp; Bug #</td>',"\n";
    echo '</tr>',"\n";

    echo '<tr class=color3>',"\n";
    echo '    <td align=center>',"\n";
    echo '    <input type="text" name="iBugId" value="'.$aClean['iBugId'].'" size="8"></td>',"\n";
    echo '    <td colspan=3><input type="submit" name="sSub" value="Search"></td>',"\n";
    echo '</tr>',"\n";

    echo '</table>',"\n";
    apidb_footer();

?>
