<?php
/*************************************************************/
/* code to view and maintain the list of application vendors */
/*************************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/vendor.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}
if ($_REQUEST['sub'])
{
    if($_REQUEST['sub'] == 'delete')
    {
        $oVendor = new Vendor($_REQUEST['vendorId']);
        $oVendor->delete();
        redirect(apidb_fullurl("admin/adminVendors.php"));
    }
} else
{
    apidb_header("Admin Vendors");

    //get available vendors
    $sQuery = "SELECT vendorId FROM vendor ORDER BY vendorName, vendorId;";
    $hResult = query_appdb($sQuery);

    // show vendorlist
    echo html_frame_start("","90%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    echo "<tr class=color4>\n";
    echo "    <td>Vendor name</td>\n";
    echo "    <td>Vendor url</td>\n";
    echo "    <td>Linked apps</td>\n";
    echo "    <td align=\"center\">Action</td>\n";
    echo "</tr>\n\n";
        
    $c = 1;
    while($ob = mysql_fetch_object($hResult))
    {
        if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
        $oVendor = new Vendor($ob->vendorId);
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td><a href=\"".BASE."vendorview.php?vendorId=".$oVendor->iVendorId."\">".$oVendor->sName."</a></td>\n";
        echo "    <td><a href=\"".$oVendor->sWebpage."\">".$oVendor->sWebpage."</a></td>\n";
        echo "    <td>".sizeof($oVendor->aApplicationsIds)."</td>\n";
        echo "    <td align=\"center\">[<a href='addVendor.php?vendorId=".$oVendor->iVendorId."'>edit</a>] &nbsp; [<a href='adminVendors.php?sub=delete&vendorId=".$oVendor->iVendorId."'>delete</a>]";
        echo "        </td>\n";
        echo "</tr>\n\n";
        $c++;
    }
    echo "</table>\n\n";
    echo html_frame_end("&nbsp;");
}

apidb_footer();
?>
