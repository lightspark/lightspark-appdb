<?php
/*****************/
/* sidebar_admin */
/*****************/
require_once(BASE."include/testData.php");
require_once(BASE."include/distribution.php");

function global_admin_menu() {

    $g = new htmlmenu("Global Admin");

    $g->add("View App Queue (".application::objectGetEntriesCount(true, false).")",
            BASE."objectManager.php?sClass=application&bIsQueue=true&sTitle=".
            "Application%20Queue");
    $g->add("View Version Queue (".version::objectGetEntriesCount(true, false).")",
            BASE."objectManager.php?sClass=version_queue&bIsQueue=true&sTitle=".
            "Version%20Queue");
    $g->add("View Screenshot Queue (".appData::objectGetEntriesCount("true",
            false, "screenshot").")",
            BASE."objectManager.php?sClass=screenshot&bIsQueue=true&sTitle=".
            "Screenshot%20Queue");
    $g->add("View Maintainer Queue (".Maintainer::objectGetEntriesCount(true, false).")",
            BASE."objectManager.php?sClass=maintainer&bIsQueue=true&sTitle=".
            "Maintainer%20Queue");
    $g->add("View Test Results Queue (".testData::objectGetEntriesCount(true, false).")",
            BASE."objectManager.php?sClass=testData&bIsQueue=true&sTitle=".
            "Test%20Results%20Queue");
    $g->add("View Distribution Queue (".distribution::objectGetEntriesCount(true, 
            false).")",
            BASE."objectManager.php?sClass=distribution&bIsQueue=true&sTitle=".
            "Distribution%20Queue");

    $g->addmisc("&nbsp;");

    $g->add("View Maintainer Entries (".Maintainer::getMaintainerCount().")",
            BASE."admin/adminMaintainers.php");
    $g->add("View Bug Links (".getNumberOfQueuedBugLinks()."/".getNumberOfBugLinks().")",
            BASE."admin/adminBugs.php");
    $g->add("Users Management", BASE."admin/adminUsers.php");
    $g->add("Comments Management", BASE."admin/adminCommentView.php");
    $g->add("Screenshots Management", BASE."admin/adminScreenshots.php");

    $g->addmisc("&nbsp;");

    $g->add("View Rejected Applications (".application::objectGetEntriesCount(true,
            true).")",
            BASE."objectManager.php?sClass=application&bIsQueue=true&bIsRejected=true&".
            "sTitle=Rejected%20Applications");
    $g->add("View Rejected Versions (".version::objectGetEntriesCount(true, true).")",
            BASE."objectManager.php?sClass=version&bIsQueue=true&bIsRejected=true&".
            "sTitle=Rejected%20Versions");
    $g->add("View Rejected Test Results (".testData::objectGetEntriesCount(true,
            true).")",
            BASE."objectManager.php?sClass=testData&bIsQueue=true&bIsRejected=true&".
            "sTitle=Rejected%20Test%20Results");

    $g->addmisc("&nbsp;");

    $g->add("Add Category", BASE."admin/addCategory.php");
    $g->add("Add Vendor", BASE."objectManager.php?sClass=vendor&bQueue=".
    "false&sAction=add&sTitle=Add%20Vendor");

    $g->done();
}

?>
