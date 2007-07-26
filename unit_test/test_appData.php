<?php

require_once("path.php");
require_once("test_common.php");
require_once(BASE."include/appData.php");
require_once(BASE."include/downloadurl.php");

function test_appData_listSubmittedBy()
{
    $bSuccess = true;

    test_start(__FUNCTION__);

    $sTestEmail = __FUNCTION__."@localhost.com";
    $sTestPassword = "password";
    if(!$oUser = create_and_login_user($sTestEmail, $sTestPassword))
    {
        error("Failed to create and log in user");
        return FALSE;
    }

    /* Create a queued appData entry */
    $oDownloadUrl = new downloadurl;

    $oDownloadUrl->sUrl = "http://www.microsoft.com/windowsmedia";
    $oDownloadUrl->sDescription = "Download Meida Player";
    $oDownloadUrl->iVersionId = 1;

    $oDownloadUrl->create();

    $shReturn = appData::listSubmittedBy($oUser->iUserId, true);

    /* This is needed for deleting the entry */
    $oUser->addPriv("admin");

    /* There should be two lines; one header and one for the downloadurl */
    $iExpected = 2;
    $iReceived = substr_count($shReturn, "</tr>");
    if($iExpected != $iReceived)
    {
        error("Got $iReceived rows instead of $iExpected.");
        $bSuccess = false;
    }

    /* Clean up */
    if(!$oDownloadUrl->delete())
    {
        $bSuccess = false;
        error("Failed to delete oDownloadUrl!");
    }

    $oUser->delete();

    return $bSuccess;
}

if(!test_appData_listSubmittedBy())
{
    echo "test_appData_listSubmittedBy() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_appData_listSubmittedBy() passed\n";
}

?>
