<?php
/*************************************/
/* code to view distributions        */
/*************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/distributions.php");
require(BASE."include/testResults.php");

$aClean = array(); //array of filtered user input

$aClean['sSub'] = makeSafe($_REQUEST['sSub']);
$aClean['iDistributionId'] = makeSafe( $_REQUEST['iDistributionId']);


if ($aClean['sSub'])
{
    if(!$_SESSION['current']->hasPriv("admin"))
        util_show_error_page("Insufficient privileges.");

    if($aClean['sSub'] == 'delete')
    {
        $oDistribution = new distribution($aClean['iDistributionId']);
        $oDistribution->delete();
        redirect($_SERVER['PHP_SELF']);
    }
} 
$oDistribution = new distribution($aClean['iDistributionId']);

//exit with error if no vendor
if(!$oDistribution->iDistributionId) 
{
    apidb_header("View Distributions");

    //get available Distributions
    $hResult = query_parameters("SELECT distributionId FROM distributions ORDER BY name, distributionId;");

    // show Distribution list
    echo html_frame_start("","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    echo "<tr class=color4>\n";
    echo "    <td>Distribution name</td>\n";
    echo "    <td>Distribution url</td>\n";
    echo "    <td align=\"right\">Linked Tests</td>\n";
    if ($_SESSION['current']->hasPriv("admin"))
        echo "    <td align=\"center\">Action</td>\n";
    echo "</tr>\n\n";
       
    $c = 1;
    while($oRow = mysql_fetch_object($hResult))
    {
        if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
        $oDistribution = new distribution($oRow->distributionId);
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td><a href=\"".BASE."distributionView.php?iDistributionId=".$oDistribution->iDistributionId."\">","\n";
        echo $oDistribution->sName."</a></td>\n";
        echo "    <td><a href=\"".$oDistribution->sUrl."\">".$oDistribution->sUrl."</a></td>\n";
        echo "    <td align=\"right\">".sizeof($oDistribution->aTestingIds)."</td>\n";
        if ($_SESSION['current']->hasPriv("admin"))
        {
            echo "    <td align=\"center\">";
            echo "[<a href='".BASE."admin/editDistribution.php?iDistributionId=".$oDistribution->iDistributionId."'>edit</a>]";
            if(!sizeof($oDistribution->aTestingIds))
                echo " &nbsp; [<a href='".$_SERVER['PHP_SELF']."?sSub=delete&iDistributionId=".$oDistribution->iDistributionId."'>delete</a>]";
            echo "        </td>\n";
        }
        echo "</tr>\n";
         $c++;
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
            $oVersion = new version($oTest->iVersionId);
            $oApp  = new application($oVersion->iAppId);
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
