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

$oDistribution = new distribution($_REQUEST['iDistributionId']);

//exit with error if no vendor
if(!$oDistribution->iDistributionId) 
{
    errorpage("No Distribution ID specified!");
    exit;
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
    }
    else 
        echo "<br />\n";

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
            echo '<tr class='.$bgcolor.'>',"\n";
            echo '<td><a href="'.BASE.'appview.php?versionId='.$oTest->iVersionId.'&iTestingId='.$oTest->iTestingId.'">',"\n";
            echo $oApp->sName.' '.$oVersion->sName.'</a></td>',"\n";
            echo '<td>',"\n";
            echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
            echo $oSubmitter->sRealname;
            echo $oSubmitter->sEmail ? "</a>":"";
            echo '</td>',"\n";
            echo '<td>'.date("M d Y", mysqltimestamp_to_unixtimestamp($oTest->sSubmitTime)).'</td>',"\n";
            echo '<td>'.$oTest->sTestedRelease.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sInstalls.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sRuns.'&nbsp</td>',"\n";
            echo '<td>'.$oTest->sTestedRating.'&nbsp</td>',"\n";
            echo '</tr>',"\n";
        }
        echo '</table>',"\n";
    }

    echo html_frame_end();
    echo html_back_link(1);
    apidb_footer();
}

?>
