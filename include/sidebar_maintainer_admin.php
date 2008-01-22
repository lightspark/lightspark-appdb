<?php
/*****************/
/* sidebar_admin */
/*****************/

function global_maintainer_admin_menu() {

    $g = new htmlmenu("Maintainer Admin");

    $g->add('View Version Queue ('.version::objectGetEntriesCount('queued').')',
            BASE.'objectManager.php?sClass=version_queue&sState=queued&sTitle='.
            'Version%20Queue');
    $g->add('View Screenshot Queue ('.screenshot::objectGetEntriesCount('queued').')',
            BASE.'objectManager.php?sClass=screenshot&sState=queued&sTitle='.
            'Screenshot%20Queue');
    $g->add('View Test Results Queue ('.testData::objectGetEntriesCount('queued').')',
            BASE.'objectManager.php?sClass=testData_queue&sState=queued&sTitle='.
            'Test%20Results%20Queue');
    $g->done();
}

?>
