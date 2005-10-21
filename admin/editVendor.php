<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/vendor.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage();
    exit;
}
$oVendor = new Vendor($_REQUEST['vendorId']);
if($_REQUEST['submit'])
{
    $oVendor->update($_REQUEST['name'],$_REQUEST['webpage']);
    redirect(apidb_fullurl("admin/adminVendors.php"));
}
else
{
    apidb_header("Edit Vendor");
    echo "<form method=\"post\" action=\"addVendor.php\">
          <input type=\"hidden\" name=\"vendorId\" value=\"".$oVendor->iVendorId."\" /> 
          <table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">
            <tr>
              <td width=\"15%\" class=\"box-label\"><b>Vendor name</b></td>
              <td class=\"box-body\">
                <input type=\"text\" size=\"50\" name=\"name\" value=\"".$oVendor->sName."\" /> 
              </td>
            </tr>
            <tr>
              <td width=\"15%\" class=\"box-label\"><b>Vendor URL</b></td>
              <td class=\"box-body\">
                <input type=\"text\" size=\"50\" name=\"webpage\" value=\"".$oVendor->sWebpage."\" /> 
              </td>
            </tr>
            <tr>
              <td colspan=\"2\" class=\"box-body\">
                <input type=\"submit\" name=\"submit\" value=\"Submit\" />
              </td>
            </tr>
          </table>
          </form>";
    apidb_footer();
}
?>
