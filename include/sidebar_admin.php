<?php
/*****************/
/* sidebar_admin */
/*****************/

function global_admin_menu() {

    $g = new htmlmenu("Global Admin");
    
    $g->add("Add Category", BASE."admin/addCategory.php");
    $g->add("Add Vendor", BASE."admin/addVendor.php");
    
    $g->addmisc("&nbsp;");
    $g->add("View App Queue (".$_SESSION['current']->getQueuedAppCount()."/".$_SESSION['current']->getQueuedVersionCount().")", BASE."admin/adminAppQueue.php");
    $g->add("View App Data Queue (".$_SESSION['current']->getQueuedAppDataCount().")", BASE."admin/adminAppDataQueue.php");
    $g->add("View Maintainer Queue (".getQueuedMaintainerCount().")", BASE."admin/adminMaintainerQueue.php");
    $g->add("View Maintainer Entries (".getMaintainerCount().")", BASE."admin/adminMaintainers.php");
    $g->add("View Vendors (".getVendorCount().")", BASE."admin/adminVendors.php");
    $g->add("View Bug Links (".getNumberOfQueuedBugLinks()."/".getNumberOfBugLinks().")", BASE."admin/adminBugs.php");

    $g->addmisc("&nbsp;");
    $g->add("Users Management", BASE."admin/adminUsers.php");
    $g->add("Comments Management", BASE."admin/adminCommentView.php");
    $g->add("Screenshots Management", BASE."admin/adminScreenshots.php");
    $g->done();
}

?>
