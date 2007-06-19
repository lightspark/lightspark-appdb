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

    /* Test the class constructor */
    if(!$oTestObject = create_object($sClassName, $oUser))
        return FALSE;

    /* Should return 1 or more, since there may be entries present already */
    $hResult = $oTestObject->objectGetEntries(false, false);

    if(!$hResult)
    {
        echo "Got '$hResult' instead of a valid MySQL handle\n";
        echo "FAILED\t\t$sClassName::$sClassName\n";
        $oTestObject->delete();
        return FALSE;
    }

    if(!($oRow = mysql_fetch_object($hResult)))
    {
        echo "Failed to fetch MySQL object\n";
        echo "FAILED\t\t$sClassName::$sClassName\n";
        $oTestObject->delete();
        return FALSE;
    }

    $oNewTestObject = new $sClassName(null, $oRow);
    switch($sClassName)
    {
        case "application":
            $iReceived = $oNewTestObject->iAppId;
        break;
        case "application_queue":
            $iReceived = $oNewTestObject->oApp->iAppId;
        break;
        case "distribution":
            $iReceived = $oNewTestObject->iDistributionId;
        break;
        case "downloadurl":
            $iReceived = $oNewTestObject->iId;
        break;
        case "maintainer":
            $iReceived = $oNewTestObject->iMaintainerId;
        break;
        case "testData":
            $iReceived = $oNewTestObject->iTestingId;
        break;
        case "testData_queue":
            $iReceived = $oNewTestObject->oTestData->iTestingId;
        break;
        case "vendor":
            $iReceived = $oNewTestObject->iVendorId;
        break;
        case "version":
            $iReceived = $oNewTestObject->iVersionId;
        break;
        case "version_queue":
            $iReceived = $oNewTestObject->oVersion->iVersionId;
        break;
        case "screenshot":
            $iReceived = $oNewTestObject->iScreenshotId;
        break;
    }

    if(!$iReceived || !is_numeric($iReceived))
    {
        echo "Got '$iReceived' instead of a valid id\n";
        echo "FAILED\t\t$sClassName::$sClassName()\n";
        $oTestObject->delete();
        return FALSE;
    }

    echo "PASSED\t\t$sClassName::$sClassName\n";

    cleanup($oTestObject);
    $oTestObject->delete();

    /* Test the methods' functionality */
    foreach($aTestMethods as $sMethod)
    {
        switch($sMethod)
        {
            /* Should also test for queued entries, but vendor does not support
               queueing yet */
            case "objectGetEntries":
                if(!$oTestObject = create_object($sClassName, $oUser))
                    return FALSE;

                /* Should return 1 or more, since there may be entries present already */
                $iExpected = 1;
                $hResult = $oTestObject->objectGetEntries(false, false);
                $iReceived = mysql_num_rows($hResult);
                if($iExpected > $iReceived)
                {
                    echo "Got $iReceived instead of >= $iExpected\n";
                    echo "FAILED\t\t$sClassName::$sMethod\n";
                    $oTestObject->delete();
                    return FALSE;
                }

                /* Class specific clean-up */
                cleanup($oTestObject);
                $oTestObject->delete();

                echo "PASSED\t\t$sClassName::$sMethod\n";
            break;
        }
    }

    $oUser->delete();

    echo "PASSED\t\t".$oObject->sClass."\n";
    return TRUE;
}

function cleanup($oObject)
{
    switch(get_class($oObject))
    {
        case "downloadurl":
        case "maintainer":
        case "screenshot":
        case "testData":
            delete_parent($oObject->iVersionId);
        break;
        case "testData_queue":
            delete_parent($oObject->oTestData->iVersionId);
        break;
        case "version":
            $oApp = new application($oObject->iAppId);
            $oApp->delete();
        break;
        case "version_queue":
            $oApp = new application($oObject->oVersion->iAppId);
            $oApp->delete();
        break;
    }
}

function create_version()
{
    $oApp = new application();
    $oApp->sName = "OM App";
    $oApp->create();
    $oVersion = new version();
    $oVersion->sName = "OM version";
    $oVersion->iAppId = $oApp->iAppId;
    $oVersion->create();
    return $oVersion->iVersionId;
}

function delete_parent($iVersionId)
{
    $oVersion = new version($iVersionId);
    $oApp = new application($oVersion->iAppId);
    $oApp->delete();
}

function create_object($sClassName, $oUser)
{
    $oUser->addPriv("admin");
    $oTestObject = new $sClassName();
    /* Set up one test entry, depending on class */
    switch($sClassName)
    {
        case "distribution":
            $oTestObject->sName = "Silly test distribution";
            $oTestObject->sUrl = "http://appdb.winehq.org/";
        break;
        case "downloadurl":
            $oTestObject->sUrl = "http://appdb.winehq.org/";
            $oTestObject->sDescription = "DANGER";
            $oTestObject->iVersionId = create_version();
        break;
        case "maintainer":
            $oVersion = new version(create_version());
            $oTestObject->iUserId = $oUser->iUserId;
            $oTestObject->iAppId = $oVersion->iAppId;
            $oTestObject->iVersionId = $oVersion->iVersionId;
            $oTestObject->sMaintainReason = "I need it";
        break;
        case "screenshot":
        case "testData":
            $oTestObject->iVersionId = create_version();
        break;
        case "testData_queue":
            $oTestObject->oTestData->iVersionId = create_version();
        break;
        case "version":
            $oApp = new application();
            $oApp->create();
            $oTestObject->iAppId = $oApp->iAppId;
            $oTestObject->sName = "OM Version";
        break;
        case "version_queue":
            $oApp = new application();
            $oApp->create();
            $oTestObject->oVersion->iAppId = $oApp->iAppId;
            $oTestObject->oVersion->sName = "OM Version";
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
        $hResult = query_parameters($sQuery, $oTestObject->iVersionId, "screenshot", "", "false",
                                    $oUser->iUserId);
        if(!$hResult)
        {
            echo "FAILED\t\t$sClassName to create screenshot entry";
            return FALSE;
        }
        $oTestObject->iScreenshotId = mysql_insert_id();
    }

    return $oTestObject;
}

function test_object_methods()
{
    test_start(__FUNCTION__);

    $aTestMethods = array("allowAnonymousSubmissions",
                          "canEdit",
                          "display",
                          "getOutputEditorValues",
                          "objectGetEntries",
                          "objectGetHeader",
                          "objectGetId",
                          "objectGetTableRow",
                          "objectMakeLink",
                          "objectMakeUrl",
                          "outputEditor",
                          "mustBeQueued"
                         );

    $aTestClasses = array("application",
                          "application_queue",
                          "distribution",
                          "downloadurl",
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
