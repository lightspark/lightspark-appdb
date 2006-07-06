<?php
include("path.php");
require_once(BASE."include/incl.php");
require_once(BASE."include/vendor.php");

$aClean = array(); //array of filtered user input
$aClean['iVendorId'] = makeSafe($_REQUEST['iVendorId']);
$aClean['sSubmit'] = makeSafe($_REQUEST['sSubmit']);
$aClean['sName'] = makeSafe($_REQUEST['sName']);
$aClean['sWebpage'] = makeSafe($_REQUEST['sWebpage']);

if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page();

$oVendor = new Vendor($aClean['iVendorId']);
if($aClean['sSubmit'])
{
    $oVendor->update($aClean['sName'],$aClean['sWebpage']);
    redirect(apidb_fullurl("vendorview.php"));
}
else
{
    if($oVendor->iVendorId)
        apidb_header("Edit Vendor");
    else
        apidb_header("Add Vendor");

    // Show the form
    echo '<form name="sQform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";

    $oVendor->OutputEditor();

    echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
    echo '<input name="sSubmit" type="submit" value="Submit" class="button" >&nbsp',"\n";
    echo '</td></tr>',"\n";

    echo "</form>";
    echo html_frame_end("&nbsp;");
    apidb_footer();

}
?>
