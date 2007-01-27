<?php
/*************************************/
/* code to view distributions        */
/*************************************/

/*
 * application environment
 */ 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/distribution.php");
require_once(BASE."include/testData.php");

if ($aClean['sSub'])
{
    if(!$_SESSION['current']->hasPriv("admin"))
        util_show_error_page_and_exit("Insufficient privileges.");

    if($aClean['sSub'] == 'delete')
    {
        $oDistribution = new distribution($aClean['iDistributionId']);
        $oDistribution->delete();
        util_redirect_and_exit($_SERVER['PHP_SELF']);
    }
} 
$oDistribution = new distribution($aClean['iDistributionId']);

/* Display distribution list if no id given */
if(!$oDistribution->iDistributionId) 
{
    apidb_header("View Distributions");

    //get available Distributions
    $hResult = distribution::ObjectGetEntries(false);

    // show Distribution list
    echo html_frame_start("","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    distribution::ObjectOutputHeader("color4");

    for($c = 1; $oRow = mysql_fetch_object($hResult); $c++)
    {
        $oDistribution = distribution::ObjectGetInstanceFromRow($oRow);

        $oDistribution->display(($c % 2) ? "color0" : "color1");
    }
    echo "</table>\n\n";
    echo html_frame_end("&nbsp;");
    if ($_SESSION['current']->hasPriv("admin"))
        echo "[<a href='".BASE."admin/editDistribution.php'>Add New Distribution</a>]";
    apidb_footer();
} 
else
{
    //display page
    apidb_header("View Distribution");
    echo html_frame_start("Distribution Information",500);

    echo "Distribution Name:";

    if($oDistribution->sUrl)
        echo "<a href='".$oDistribution->sUrl."'>";

    echo $oDistribution->sName;

    if ($oDistribution->sUrl) 
    {
        echo " (".$oDistribution->sUrl.")";
        echo "</a> <br />\n";
    } else 
    {
        echo "<br />\n";
    }

    if($oDistribution->aTestingIds)
    {
        echo '<p><span class="title">Testing Results for '.$oDistribution->sName.'</span><br />',"\n";
        echo '<table width="100%" border="1">',"\n";
        echo '<thead class="historyHeader">',"\n";
        echo '<tr>',"\n";
        echo '<td>Application Version</td>',"\n";
        echo '<td>Submitter</td>',"\n";
        echo '<td>Date Submitted</td>',"\n";
        echo '<td>Wine version</td>',"\n";
        echo '<td>Installs?</td>',"\n";
        echo '<td>Runs?</td>',"\n";
        echo '<td>Rating</td>',"\n";
        echo '</tr></thead>',"\n";
        foreach($oDistribution->aTestingIds as $iTestingId)
        {
            $oTest = new testData($iTestingId);
            $oVersion = new Version($oTest->iVersionId);
            $oApp  = new Application($oVersion->iAppId);
            $oSubmitter = new User($oTest->iSubmitterId);
            $bgcolor = $oTest->sTestedRating;

            /* make sure the user can view the versions we list in the table */
            /* otherwise skip over displaying the entries in this table */
            if(!$_SESSION[current]->canViewApplication($oApp))
                continue;
            if(!$_SESSION[current]->canViewVersion($oVersion))
                continue;

            echo '<tr class='.$bgcolor.'>',"\n";
            echo '<td><a href="'.BASE.'appview.php?iVersionId='.$oTest->iVersionId.'&iTestingId='.$oTest->iTestingId.'">',"\n";
            echo $oApp->sName.' '.$oVersion->sName.'</a></td>',"\n";
            echo '<td>',"\n";
            if($_SESSION['current']->isLoggedIn())
            {
                echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
                echo $oSubmitter->sRealname;
                echo $oSubmitter->sEmail ? "</a>":"";
            }
            else
                echo $oSubmitter->sRealname;
            echo '</td>',"\n";
            echo '<td>'.date("M d Y", mysqltimestamp_to_unixtimestamp($oTest->sSubmitTime)).'</td>',"\n";
            echo '<td>'.$oTest->sTestedRelease.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sInstalls.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sRuns.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sTestedRating.'&nbsp</td>',"\n";
            if ($_SESSION['current']->hasAppVersionModifyPermission($oVersion))
            {
                echo '<td><a href="'.BASE.'admin/adminTestResults.php?sSub=view&iTestingId='.$oTest->iTestingId.'">',"\n";
                echo 'Edit</a></td>',"\n";
            }
            echo '</tr>',"\n";
        }
        echo '</table>',"\n";
    }

    echo html_frame_end();
    echo html_back_link(1);
    apidb_footer();
}

?>
