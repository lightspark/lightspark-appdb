<?php

/* unit tests to make sure objects we want to use with the object manager are valid */

require_once("path.php");
require_once("test_common.php");
//require_once(BASE."include/incl.php");
require_once(BASE.'include/objectManager.php');
require_once(BASE.'include/application.php');
//require_once(BASE.'include/application_queue.php');
require_once(BASE.'include/maintainer.php');
//require_once(BASE.'include/version_queue.php');

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
                /* Should return 1 */
                if(!$oTestObject->create())
                {
                    echo "FAILED\t\t$sClassName::create()\n";
                    return FALSE;
                }
                $iExpected = 1;
                $hResult = $oTestObject->objectGetEntries(false);
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

/*    $sClassName = 'application';
    if(!test_class($sClassName))
    {
        echo $sClassName." class does not have valid methods for use with the object manager\n";
        return false;
    } else
    {
        echo "PASSED:\t\t".$sClassName."\n";
    }

    $sClassName = 'application_queue';
    if(!test_class($sClassName))
    {
        echo $sClassName." class does not have valid methods for use with the object manager\n";
        return false;
    } else
    {
        echo "PASSED:\t\t".$sClassName."\n";
    }

    $sClassName = 'version';
    if(!test_class($sClassName))
    {
        echo $sClassName." class does not have valid methods for use with the object manager\n";
        return false;
    } else
    {
        echo "PASSED:\t\t".$sClassName."\n";
    }

    $sClassName = 'version_queue';
    if(!test_class($sClassName))
    {
        echo $sClassName." class does not have valid methods for use with the object manager\n";
        return false;
    } else
    {
        echo "PASSED:\t\t".$sClassName."\n";
    }*/

    $aTestMethods = array("objectOutputHeader", "objectOutputTableRow",
                          "objectGetEntries", "display",
                          "objectGetInstanceFromRow", "outputEditor", "canEdit",
                          "getOutputEditorValues", "objectMakeUrl", "objectMakeLink");

    if(!test_class("distribution", $aTestMethods))
        return FALSE;

    if(!test_class("vendor", $aTestMethods))
        return FALSE;

    if(!test_class("maintainer", $aTestMethods))
        return FALSE;

/*    if(!test_class("screenshot", $aTestMethods))
        return FALSE;  */
    return true;
}

if(!test_object_methods())
    echo "test_object_methods() failed!\n";
else
    echo "test_object_methods() passed\n";

?>
