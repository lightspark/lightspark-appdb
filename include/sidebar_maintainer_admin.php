<?php
/*****************/
/* sidebar_admin */
/*****************/

function global_maintainer_admin_menu() {

    $g = new htmlmenu("Maintainer Admin");

    $g->add("View App Queue (".$_SESSION['current']->getQueuedVersionCount().")",
            BASE."admin/adminAppQueue.php");
    $g->add("View Screenshot Queue (".appData::objectGetEntriesCount("true",
            "screenshot").")",
            BASE."objectManager.php?sClass=screenshot&bIsQueue=true&sTitle=".
            "Screenshot%20Queue");
    $g->done();
}

?>
