<?php

require_once("path.php");
require_once(BASE."include/version.php");

function test_testData_getNewestTestidFromVersionId()
{
    test_start(__FUNCTION__);
    global $test_email, $test_password;
    if(!$oUser = create_and_login_user())
    {
        echo "Failed to create and log in user\n";
        return FALSE;
    }

    $iVersionId = 65555;

    $oOldTestData = new testData();
    $oOldTestData->iVersionId = $iVersionId;
    $oOldTestData->create();
    $oNewTestData = new testData();
    $oNewTestData->iVersionId = $iVersionId;
    $oNewTestData->create();

    $oUser->addPriv("admin");
    $oOldTestData->unQueue();

    /* Now the oldTestData should be listed as current, because the new one is queued */
    $iExpected = $oOldTestData->iTestingId;
    $iReceived = testData::getNewestTestidFromVersionId($iVersionId);
    if($iExpected != $iReceived)
    {
        echo "Got testData id of $iReceived instead of $iExpected!\n";
        $oOldTestData->delete();
        $oNewTestData->delete();
        return FALSE;
    }

    $oOldTestData->delete();
    $oNewTestData->delete();

    return TRUE;
}

if(!test_testData_getNewestTestidFromVersionId())
{
    echo "test_testData_getNewestTestidFromVersionId() failed!\n";
    $bTestSuccess = false;
} else
    echo "test_testData_getNewestTestidFromVersionId() passed\n";
?>
