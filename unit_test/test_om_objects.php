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
    if(!$oObject->checkMethods($aTestMethods, false))
    {
        echo $oObject->sClass." class does not have valid methods for use with".
             " the object manager\n";
        return false;
    } else
    {
        echo "PASSED:\t\t".$oObject->sClass."\n";
    }
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
                          "getOutputEditorValues", "objectGetEntries",
                          "objectMakeUrl", "objectMakeLink");

    test_class("distribution", $aTestMethods);
    test_class("vendor", $aTestMethods);
/*    test_class("maintainer", $aTestMethods);
    test_class("screenshot", $aTestMethods); */

    return true;
}

if(!test_object_methods())
    echo "test_object_methods() failed!\n";
else
    echo "test_object_methods() passed\n";

?>
