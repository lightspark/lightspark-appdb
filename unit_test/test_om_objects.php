<?php

/* unit tests to make sure objects we want to use with the object manager are valid */

require_once("path.php");
require_once("test_common.php");
require_once(BASE.'include/objectManager.php');
require_once(BASE.'include/application.php');
require_once(BASE.'include/maintainer.php');
require_once(BASE.'include/testData_queue.php');
require_once(BASE.'include/version_queue.php');
require_once(BASE.'include/application_queue.php');

/* internal function */
function test_class($sClassName, $aTestMethods)
{
    $oObject = new ObjectManager("");
    $oObject->sClass = $sClassName;

    /* Check whether the required methods are present */
    if(!$oObject->checkMethods($aTestMethods, false))
    {
        echo "FAILED\t\t".$oObject->sClass." does not have valid methods for use with".
             " the object manager\n";
        return false;
    }

    /* Set up test user */
    global $test_email, $test_password;
    if(!$oUser = create_and_login_user())
    {
        echo "Failed to create and log in user.\n";
        return FALSE;
    }
    /* Test the methods' functionality */
    foreach($aTestMethods as $sMethod)
    {
        switch($sMethod)
        {
            /* Should also test for queued entries, but vendor does not support
               queueing yet */
            case "objectGetEntries":
                $oUser->addPriv("admin");
                $oTestObject = new $sClassName();
                /* Set up one test entry, depending on class */
                switch($sClassName)
                {
                    case "distribution":
                        $oTestObject->sName = "Silly test distribution";
                        $oTestObject->sUrl = "http://appdb.winehq.org/";
                    break;
                    case "maintainer":
                        $iAppId = 65555;
                        $oApp = new application();

                        if(!$oApp->create())
                        {
                            echo "Failed to create application";
                            return FALSE;
                        }
                        $oApp->iAppId = $iAppId;
                        $oApp->update();
                        $oTestObject->iUserId = $oUser->iUserId;
                        $oTestObject->iAppId = $iAppId;
                        $oTestObject->sMaintainReason = "I need it";
                    break;
                }

                /* We cannot use screenshot::create() because it requires an image */
                if($sClassName != "screenshot")
                {
                    if(!$oTestObject->create())
                    {
                        echo "FAILED\t\t$sClassName::create()\n";
                        return FALSE;
                    }
                } else
                {
                    $sQuery = "INSERT INTO appData
                            (versionId, type, description, queued, submitterId)
                            VALUES('?','?','?','?','?')";
                    $hResult = query_parameters($sQuery, 0, "screenshot", "", "false",
                                                $oUser->iUserId);
                    if(!$hResult)
                    {
                        echo "FAILED\t\t$sClassName to create screenshot entry";
                        returN FALSE;
                    }
                    $oTestObject->iScreenshotId = mysql_insert_id();
                }

                /* Should return 1 or more, since there may be entries present already */
                $iExpected = 1;
                $hResult = $oTestObject->objectGetEntries(false, false);
                $iReceived = mysql_num_rows($hResult);
                $oTestObject->delete();
                if($iExpected > $iReceived)
                {
                    echo "Got $iReceived instead of >= $iExpected\n";
                    echo "FAILED\t\t$sClassName::$sMethod\n";
                    return FALSE;
                }
                /* Class specific clean-up */
                switch($sClassName)
                {
                    case "maintainer":
                        $oApp->delete();
                    break;
                }
                echo "PASSED\t\t$sClassName::$sMethod\n";
            break;
        }
    }

    $oUser->delete();

    echo "PASSED\t\t".$oObject->sClass."\n";
    return TRUE;
}

function test_object_methods()
{
    test_start(__FUNCTION__);

    $aTestMethods = array("canEdit",
                          "display",
                          "getOutputEditorValues",
                          "objectGetEntries",
                          "objectGetHeader",
                          "objectGetInstanceFromRow",
                          "objectOutputTableRow",
                          "objectMakeLink",
                          "objectMakeUrl",
                          "outputEditor",
                          "mustBeQueued"
                         );

    $aTestClasses = array("application",
                          "application_queue",
                          "distribution",
                          "maintainer",
                          "screenshot",
                          "testData",
                          "testData_queue",
                          "vendor",
                          "version",
                          "version_queue"
                         );

    foreach($aTestClasses as $sTestClass)
    {
        if(!test_class($sTestClass, $aTestMethods))
            return FALSE;
    }

    return true;
}

if(!test_object_methods())
{
    echo "test_object_methods() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_object_methods() passed\n";
}

?>
