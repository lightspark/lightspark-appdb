<?php
/*************************************/
/* code to view vendors & their apps */
/*************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");

$vendorId = $_REQUEST['vendorId'];

//exit with error if no vendor
if(!$vendorId) {
    errorpage("No vendor ID specified!");
    exit;
}

//get vendor, die with error if no match
$result = query_appdb("SELECT * FROM vendor WHERE vendorId = $vendorId");
if(!$result || mysql_num_rows($result) != 1) {
    errorpage("Invalid vendor ID!");
    exit;
}

//show admin sidebar if user is admin
if($_SESSION['current']->hasPriv("admin")) {
    apidb_sidebar_add("admin_menu");
}

//get data
$vendor = mysql_fetch_object($result);

//display page
apidb_header("View Vendor");
echo html_frame_start("Vendor Information",500);

echo "Vendor Name: $vendor->vendorName <br />\n";

if ($vendor->vendorURL) {
	echo "Vendor URL:  <a href='$vendor->vendorURL'>$vendor->vendorURL</a> <br />\n";
}

$result = query_appdb("SELECT * FROM appFamily WHERE vendorId = $vendorId ORDER BY appName");
if($result)
{
    echo "<br />Applications by $vendor->vendorName<br /><ol>\n";
    while($app = mysql_fetch_object($result))
	{
	    echo "<li> <a href='appview.php?appId=$app->appId'> $app->appName </a> </li>\n";
	}
    echo "</ol>\n";
}

echo html_frame_end();
echo html_back_link(1);
apidb_footer();



// SUBS //

//admin menu for sidebar
function admin_menu()
{
    global $vendorId;

    $m = new htmlmenu("Admin");
    $m->add("Edit this vendor", "admin/editVendor.php?vendorId=$vendorId");
    $m->done();
}

?>
