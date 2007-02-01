<?php
/*************************************/
/* code to view vendors & their apps */
/*************************************/

/*
 * application environment
 */ 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/application.php");
require_once(BASE."include/vendor.php");

$oVendor = new Vendor($aClean['iVendorId']);

if ($aClean['sSub'])
{
    if(!$_SESSION['current']->hasPriv("admin"))
        util_show_error_page_and_exit("Insufficient privileges.");

    if($aClean['sSub'] == 'delete')
    {
        $oVendor->delete();
        util_redirect_and_exit($_SERVER['PHP_SELF']);
   }

} 


if($oVendor->iVendorId)
{
    //display page
    apidb_header("View Vendor");
    echo html_frame_start("Vendor Information",500);

    $oVendor->display();

    echo html_frame_end();
    echo html_back_link(1);
    apidb_footer();

}
else
{
    apidb_header("View Vendors");

    //get available vendors
    $hResult = vendor::objectGetEntries(false);

    // show vendorlist
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    vendor::objectOutputHeader("color4");

    for($c = 0; $oRow = mysql_fetch_object($hResult); $c++)
    {
        $oVendor = vendor::objectGetInstanceFromRow($oRow);
        $oVendor->objectOutputTableRow(($c % 2) ? "color0" : "color1");
    }

    echo '<tr><td>',"\n";
    echo html_back_link(1);
    echo '</td></tr></table>',"\n";
    apidb_footer();

}

?>
