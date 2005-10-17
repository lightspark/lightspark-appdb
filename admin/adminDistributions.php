<?php
/*******************************************************/
/* code to view and maintain the list of Distributions */
/*******************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/distributions.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}
if ($_REQUEST['sub'])
{
    if($_REQUEST['sub'] == 'delete')
    {
        $oDistribution = new distribution($_REQUEST['iDistributionId']);
        $oDistribution->delete();
        redirect(apidb_fullurl("admin/adminDistributions.php"));
    }
} else
{
    apidb_header("Admin Distributions");

    //get available Distributions
    $sQuery = "SELECT distributionId FROM distributions ORDER BY name, distributionId;";
    $hResult = query_appdb($sQuery);

    // show Distribution list
    echo html_frame_start("","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    echo "<tr class=color4>\n";
    echo "    <td>Distribution name</td>\n";
    echo "    <td>Distribution url</td>\n";
    echo "    <td>Linked Tests</td>\n";
    echo "    <td align=\"center\">Action</td>\n";
    echo "</tr>\n\n";
       
    $c = 1;
    while($ob = mysql_fetch_object($hResult))
    {
        if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
        $oDistribution = new distribution($ob->distributionId);
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td><a href=\"".BASE."distributionView.php?iDistributionId=".$oDistribution->iDistributionId."\">","\n";
        echo $oDistribution->sName."</a></td>\n";
        echo "    <td><a href=\"".$oDistribution->sUrl."\">".$oDistribution->sUrl."</a></td>\n";
        echo "    <td>".sizeof($oDistribution->aTestingIds)."</td>\n";
        echo "    <td align=\"center\">";
        echo "[<a href='editDistribution.php?iDistributionId=".$oDistribution->iDistributionId."'>edit</a>]";
        if(!sizeof($oDistribution->aTestingIds))
            echo " &nbsp; [<a href='adminDistributions.php?sub=delete&iDistributionId=".$oDistribution->iDistributionId."'>delete</a>]";
        echo "        </td>\n";
        echo "</tr>\n\n";
        $c++;
    }
    echo "</table>\n\n";
    echo html_frame_end("&nbsp;");

}

apidb_footer();
?>
