<?php

/* Unit tests for functions in include/url.php */

require_once("path.php");
require_once("test_common.php");

function test_url_update()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    $bSuccess = true; // default to success until we detect failure

    /* Log in */
    if(!$oUser = create_and_login_user())
    {
        echo "Received '$retval' instead of SUCCESS('".SUCCESS."').";
        return FALSE;
    }

    $iAppId = 655000;
    $iVersionId = 655000;

    $oUser->addPriv("admin");

    $oUrl = new Url();
    $oUrl->create("Bad description", "http://www.badurl.com/", $iVersionId, $iAppId, TRUE);

    $sDescriptionNew = "Good description";
    $sUrlNew = "http://www.goodurl.com/";
    $iAppIdNew = $iAppId + 1;
    $iVersionIdNew = $iVersionId + 1;

    $oUrl->update($sDescriptionNew, $sUrlNew, $iVersionIdNew, $iAppIdNew, TRUE);

    if($oUrl->sDescription != $sDescriptionNew)
    {
        echo "Description is '$oUrl->sDescription' instead of '$sDescriptionNew'\n";
        $bSuccess = false;
    }

    if($oUrl->sUrl != $sUrlNew)
    {
        echo "Url is '$oUrl->sUrl' instead of '$sUrlNew'\n";
        $bSuccess = false;
    }

    if($oUrl->iVersionId != $iVersionIdNew)
    {
        echo "VersionId is '$oUrl->iVersionId' instead of '$iVersionIdNew'\n";
        $bSuccess = false;
    }

    if($oUrl->iAppId != $iAppIdNew)
    {
        echo "AppId is '$oUrl->iAppId' instead of '$iAppIdNew'\n";
        $bSuccess = false;
    }

    $oUrl->delete(TRUE);
    $oUser->delete();

    return $bSuccess;
}

if(!test_url_update())
{
    echo "test_url_update() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_url_update() passed\n";
}

?>
