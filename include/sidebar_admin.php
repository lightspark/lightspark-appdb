<?php
/*****************/
/* sidebar_admin */
/*****************/

function global_admin_menu() {

    $g = new htmlmenu("Global Admin");
    
    $g->add("Add Category", BASE."admin/addCategory.php");
    $g->add("Add Application", BASE."admin/addAppFamily.php?catId=0");
    $g->add("Add Vendor", BASE."admin/addVendor.php");
    
    $g->addmisc("&nbsp;");
    $g->add("List Users", BASE."admin/");
    $g->add("View App Queue (".getQueuedAppCount().")", BASE."admin/adminAppQueue.php");
    $g->add("View App Data Queue (".getQueuedAppDataCount().")", BASE."admin/adminAppDataQueue.php");
    $g->add("View Maintainer Queue (".getQueuedMaintainerCount().")", BASE."admin/adminMaintainerQueue.php");
    $g->add("View Maintainers (".getMaintainerCount().")", BASE."admin/adminMaintainers.php");

    $g->addmisc("&nbsp;");
    $g->add("Comment manager", BASE."admin/adminCommentView.php");
    $g->done();

}

?>
