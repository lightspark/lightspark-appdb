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

        $oDistribution->objectOutputTableRow(($c % 2) ? "color0" : "color1");
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

    $oDistribution->display();

    echo html_frame_end();
    echo html_back_link(1);
    apidb_footer();
}

?>
