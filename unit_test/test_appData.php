<?php

require_once("path.php");
require_once("test_common.php");
require_once(BASE."include/appData.php");
require_once(BASE."include/downloadurl.php");

function test_appData_listSubmittedBy()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;
    if(!$oUser = create_and_login_user())
    {
        echo "Failed to create and log in user\n";
        return FALSE;
    }

    /* Create a queued appData entry */
    $oDownloadUrl = new downloadurl;

    $oDownloadUrl->sUrl = "http://www.microsoft.com/windowsmedia";
    $oDownloadUrl->sDescription = "Download Meida Player";
    $oDownloadUrl->iVersionId = 1;

    $oDownloadUrl->create();

    $shReturn = appData::listSubmittedBy($oUser->iUserId, true);

    /* There should be two lines; one header and one for the downloadurl */
    $iExpected = 2;
    $iReceived = substr_count($shReturn, "</tr>");
    if($iExpected != $iReceived)
    {
        echo "Got $iReceived rows instead of $iExpected.\n";
        return FALSE;
    }

    /* Clean up */
    $oDownloadUrl->delete();
    $oUser->delete();

    return TRUE;
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
