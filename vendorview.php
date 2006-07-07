<?php
/*************************************/
/* code to view vendors & their apps */
/*************************************/

/*
 * application environment
 */ 
include("path.php");
require_once(BASE."include/incl.php");
require_once(BASE."include/filter.php");
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

    echo 'Vendor Name: '.$oVendor->sName,"\n";
    if($_SESSION['current']->hasPriv("admin"))
    {
        echo ' [<a href="'.apidb_fullurl("admin/editVendor.php").'?iVendorId='.$oVendor->iVendorId.'">edit</a>]',"\n";
    }

    echo '<br />',"\n";
    if ($oVendor->sWebpage)
        echo 'Vendor URL:  <a href="'.$oVendor->sWebpage.'">'.$oVendor->sWebpage.'</a> <br />',"\n";


    if($oVendor->aApplicationsIds)
    {
        echo '<br />Applications by '.$oVendor->sName.'<br /><ol>',"\n";
        foreach($oVendor->aApplicationsIds as $iAppId)
        {
            $oApp  = new application($iAppId);
            echo '<li> <a href="appview.php?iAppId='.$oApp->iAppId.'">'.$oApp->sName.'</a> </li>',"\n";
        }
        echo '</ol>',"\n";
    }


    echo html_frame_end();
    echo html_back_link(1);
    apidb_footer();

}
else
{
    apidb_header("View Vendors");

    //get available vendors
    $hResult = query_parameters("SELECT vendorId FROM vendor ORDER BY vendorName, vendorId;");

    // show vendorlist
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

    echo '<tr class="color4">',"\n";
    echo '<td>Vendor name</td>',"\n";
    echo '<td>Vendor\'s Web Page</td>',"\n";
    echo '<td align="right">linked Apps</td>',"\n";
    if ($_SESSION['current']->hasPriv("admin"))
    {
        echo '<td align="center">Action</td>',"\n";
    }
    echo '</tr>',"\n";
        
    $c = 1;
    while($oRow = mysql_fetch_object($hResult))
    {
        if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
        $oVendor = new Vendor($oRow->vendorId);
        echo '<tr class="'.$bgcolor.'">',"\n";
        echo '<td><a href="'.BASE.'vendorview.php?iVendorId='.$oVendor->iVendorId.'">'.$oVendor->sName.'</a></td>',"\n";
        echo '<td><a href="'.$oVendor->sWebpage.'">'.substr($oVendor->sWebpage,0,30).'</a></td>',"\n";
        echo '<td align="right">'.sizeof($oVendor->aApplicationsIds).'</td>',"\n";
        if ($_SESSION['current']->hasPriv("admin"))
        {
            echo '<td align="center">',"\n";
            echo '[<a href="'.BASE.'admin/editVendor.php?iVendorId='.$oVendor->iVendorId.'">edit</a>]',"\n";
            if(!sizeof($oVendor->aApplicationsIds)) 
                echo '&nbsp[<a href="'.$_SERVER['PHP_SELF'].'?sSub=delete&iVendorId='.$oVendor->iVendorId.'">delete</a>]',"\n";
            echo '</td>',"\n";
        }
        echo '</tr>',"\n";
        $c++;
    }

    echo '<tr><td>',"\n";
    echo html_back_link(1);
    echo '</td></tr></table>',"\n";
    apidb_footer();

}

?>
