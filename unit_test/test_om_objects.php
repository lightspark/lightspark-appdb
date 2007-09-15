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
require_once(BASE.'include/browse_newest_apps.php');
require_once(BASE.'include/monitor.php');
require_once(BASE.'include/bugs.php');


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

    // TODO: work around for 'browse_newest_apps' class
    // since we can't create a new database object of browse_newest_apps
    if($sClassName == "browse_newest_apps")
      return true;

    /* Set up test user */
    $sTestEmail = __FUNCTION__."@localhost.com";
    $sTestPassword = "password";
    if(!$oUser = create_and_login_user($sTestEmail, $sTestPassword))
    {
        error("Failed to create and log in user.");
        return FALSE;
    } else
    {
      // assign the session variable that makes this user the current user
      $_SESSION['current'] = $oUser;
    }

    /* Test the class constructor */
    if(!$oTestObject = create_object($sClassName, $oUser))
        return FALSE;

    /* Should return 1 or more, since there may be entries present already */
    $hResult = $oTestObject->objectGetEntries(false, false);

    if(!$hResult)
    {
        error("Got '$hResult' instead of a valid query handle");
        error("FAILED\t\t$sClassName::$sClassName");
        $oTestObject->delete();
        return FALSE;
    }

    if(!($oRow = query_fetch_object($hResult)))
    {
        error("Failed to fetch query object");
        error("FAILED\t\t$sClassName::$sClassName");
        $oTestObject->delete();
        return FALSE;
    }

    $oNewTestObject = new $sClassName(null, $oRow);
    $iReceived = $oNewTestObject->objectGetId();

    if(!$iReceived || !is_numeric($iReceived))
    {
        error("Got '$iReceived' instead of a valid id");
        error("FAILED\t\t$sClassName::$sClassName()");
        $oTestObject->delete();
        return FALSE;
    }

    echo "PASSED\t\t$sClassName::$sClassName\n";

    //////////////////////////
    // cleanup after the test
    cleanup($oTestObject);

    if(!$oUser->addPriv("admin"))
        error("oUser->addPriv('admin') failed");

    if(!$oTestObject->delete())
    {
        error("sClassName of '".$sClassName."' oTestObject->delete() failed!");
        $oUser->delete();
        return false;
    }

    ////////////////////////////////

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
                $iReceived = query_num_rows($hResult);
                if($iExpected > $iReceived)
                {
                    error("Got $iReceived instead of >= $iExpected");
                    error("FAILED\t\t$sClassName::$sMethod");
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
        case "bug":
          // remove the bug row we created for the bug in create_object()
          $sQuery = "delete from bugs where bug_id = '?'";
          $hResult = query_bugzilladb($sQuery, $oObject->iBug_id);
        break;
        case "downloadurl":
        case "maintainer":
        case "screenshot":
        case "testData":
            delete_version_and_parent_app($oObject->iVersionId);
        break;
        case "testData_queue":
            delete_version_and_parent_app($oObject->oTestData->iVersionId);
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

function create_object($sClassName, $oUser)
{
    $oUser->addPriv("admin");
    $oTestObject = new $sClassName();
    /* Set up one test entry, depending on class */
    switch($sClassName)
    {
        case "bug":
          // create a bug in the bugzilla database, we need a valid
          // bug id to create a bug entry
          $sQuery = "insert into bugs (short_desc, bug_status, resolution)".
            " values ('?', '?', '?')";
          $hResult = query_bugzilladb($sQuery, "test_om_objects", "VERIFIED",
                                      '');

          // retrieve the bug id and assign that to our
          // bug class
          $oTestObject->iBug_id = query_bugzilla_insert_id();
        break;
        case "distribution":
            $oTestObject->sName = "Silly test distribution";
            $oTestObject->sUrl = "http://appdb.winehq.org/";
        break;
        case "downloadurl":
            $oTestObject->sUrl = "http://appdb.winehq.org/";
            $oTestObject->sDescription = "DANGER";
            $oTestObject->iVersionId = create_version_and_parent_app("create_object_downloadurl");
        break;
        case "maintainer":
            $iVersionId = create_version_and_parent_app("create_object_maintainer");
            $oVersion = new version($iVersionId);
            $oTestObject->iUserId = $oUser->iUserId;
            $oTestObject->iAppId = $oVersion->iAppId;
            $oTestObject->iVersionId = $oVersion->iVersionId;
            $oTestObject->sMaintainReason = "I need it";
        break;
        case "screenshot":
        case "testData":
            $oTestObject->iVersionId = create_version_and_parent_app("create_object_testData");
        break;
        case "testData_queue":
            $oTestObject->oTestData->iVersionId = create_version_and_parent_app("create_object_testData_queue");
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
        case "vendor":
            $oTestObject->sName = "OM vendor";
        break;
    }

    /* We cannot use screenshot::create() because it requires an image */
    if($sClassName != "screenshot")
    {
        if(!$oTestObject->create())
        {
            error("FAILED\t\t$sClassName::create()");
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
            error("FAILED\t\t$sClassName to create screenshot entry");
            return FALSE;
        }
        $oTestObject->iScreenshotId = query_appdb_insert_id();
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
                          "objectGetChildren",
                          "objectGetEntries",
                          "objectGetHeader",
                          "objectGetId",
                          "objectGetMail",
                          "objectGetMailOptions",
                          "objectGetSubmitterId",
                          "objectGetTableRow",
                          "objectMakeLink",
                          "objectMakeUrl",
                          "outputEditor",
                          "mustBeQueued"
                         );

    $aTestClasses = array("application",
                          "application_queue",
                          "browse_newest_apps",
                          "bug",
                          "distribution",
                          "downloadurl",
                          "maintainer",
                          "monitor",
                          "note",
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
