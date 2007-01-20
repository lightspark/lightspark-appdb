<?php

require_once("path.php");
require_once(BASE.'include/maintainer.php');

/* unit tests for maintainer class */

// test that the maintainer count for a given user is accurate for both
//   maintainers and super maintainers when the user is either a maintainer
//   or a super maintainer
function test_maintainer_getMaintainerCountForUser()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    /* login the user */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /**
      * make the user a super maintatiner
      */
    $iAppId = 655000;
    $iVersionId = 655200;

    /* queue up this maintainer */
    $oMaintainer = new Maintainer();
    $oMaintainer->iAppId = $iAppId;
    $oMaintainer->iVersionId = $iVersionId;
    $oMaintainer->iUserId = $_SESSION['current']->iUserId;
    $oMaintainer->sMaintainReason = "Some crazy reason";
    $oMaintainer->bSuperMaintainer = TRUE;
    $oMaintainer->create();

    /* and unqueue it to accept the user as a maintainer */
    $oMaintainer->unQueue("Some reply text");
    
    /* see that the user is a super maintainer of the one application we added them to be */
    $iExpected = 1; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* maintainer count should be zero */
    $iExpected = 0;
    $iMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* remove maintainership for this user */
    Maintainer::deleteMaintainer($oUser, $iAppId);

    /**
     * make the user a maintainer
     */

    /* queue up this maintainer */
    $oMaintainer = new Maintainer();
    $oMaintainer->iAppId = $iAppId;
    $oMaintainer->iVersionId = $iVersionId;
    $oMaintainer->iUserId = $_SESSION['current']->iUserId;
    $oMaintainer->sMaintainReason = "Some crazy reason";
    $oMaintainer->bSuperMaintainer = FALSE;
    $oMaintainer->create();

    /* and unqueue it to accept the user as a maintainer */
    $oMaintainer->unQueue("Some reply text");

    /* see that the user is a super maintainer of no applications */
    $iExpected = 0; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* maintainer count should be one */
    $iExpected = 1;
    $iMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* remove maintainership for this user */
    Maintainer::deleteMaintainer($oUser, $iAppId, $iVersionId);

    return true;
}

// test that applications a user maintains are accurately reported by
//  maintainer::GetAppsMaintained()
function test_maintainer_getAppsMaintained()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    /* login the user */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* make this user an admin so we can create applications without having them queued */
    $hResult = query_parameters("INSERT into user_privs values ('?', '?')",
                                $oUser->iUserId, "admin");

    /* create a application so we have a valid appFamily for the call to user::getAppsMaintained() */
    $oApp = new Application();
    $oApp->sName = "Some application";
    $oApp->sDescription = "some description";
    $oApp->submitterId = $oUser->iUserId;
    if(!$oApp->create())
    {
        echo "Failed to create application!\n";
        return false;
    }

    /**
      * make the user a super maintatiner
      */
    $iAppId = $oApp->iAppId; /* use the iAppId of the application we just created */
    $iVersionId = 655200;
    $bSuperMaintainer = TRUE;

    /* queue up the maintainership */
    // add to queue
    $oMaintainer = new Maintainer();
    $oMaintainer->iAppId = $iAppId;
    $oMaintainer->iVersionId = $iVersionId;
    $oMaintainer->iUserId = $oUser->iUserId;
    $oMaintainer->sMaintainReason = "Some crazy reason";
    $oMaintainer->bSuperMaintainer = $bSuperMaintainer;
    $oMaintainer->create();

    $statusMessage = $oMaintainer->unQueue("Some reply text"); /* accept the maintainership */

    /* get an array of the apps maintained */
    $aAppsMaintained = maintainer::getAppsMaintained($oUser);

    if(!$aAppsMaintained)
    {
        echo "aAppsMaintained is null, we expected a non-null return value!\n";
        return false;
    }

    /* get only the first entry from the array of applications maintained */
    /* we only added the user as a maintainer of a single application */
    list($iAppId1, $iVersionId1, $bSuperMaintainer1) = $aAppsMaintained[0];

    /* make sure all parameters match what we added as maintainer information */
    if($iAppId1 != $iAppId)
    {
        echo "Expected iAppid of ".$iAppId." but got ".$iAppId1."\n";
        return false;
    }

    if($iVersionId1 != $iVersionId)
    {
        echo "Expected iVersionId of ".$iVersionId." but got ".$iVersionId1."\n";
        return false;
    }

    if($bSuperMaintainer1 != $bSuperMaintainer)
    {
        echo "Expected bSuperMaintainer of ".$bSuperMaintainer." but got ".$bSuperMaintainer1."\n";
        return false;
    }

    /* remove maintainership for this user */
    Maintainer::deleteMaintainer($oUser, $iAppId);

    /* remove this application */
    $oApp = new Application($iAppId);
    $oApp->delete();

    return true;
}

// test that unQueueing a queued maintainer request twice is ignored
function test_maintainer_unQueue()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    /* login the user */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /**
      * make the user a super maintatiner
      */
    $iAppId = 655000;
    $iVersionId = 655200;

    /* queue up this maintainer */
    $oMaintainer = new Maintainer();
    $oMaintainer->iAppId = $iAppId;
    $oMaintainer->iVersionId = $iVersionId;
    $oMaintainer->iUserId = $_SESSION['current']->iUserId;
    $oMaintainer->sMaintainReason = "Some crazy reason";
    $oMaintainer->bSuperMaintainer = TRUE;
    $oMaintainer->create();

    /* and unqueue it to accept the user as a maintainer */
    $oMaintainer->unQueue("Some reply text");

    /* unqueue it again to ensure that unQueueing a maintainer request twice works properly */
    $oMaintainer->unQueue("Some other reply text");


    /* see that the user is a super maintainer of the one application we added them to be */
    $iExpected = 1; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* maintainer count should be zero */
    $iExpected = 0;
    $iMaintainerCount = Maintainer::getMaintainerCountForUser($oUser, FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* remove maintainership for this user */
    Maintainer::deleteMaintainer($oUser, $iAppId);

    return true;
}

/* Test whether a super maintainer request submitted along with an application is also accepted when the application is accepted */
function test_superMaintainerOnAppSubmit()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    /* Log in */
    $oUser = new User();
    if($retval = $oUser->login($test_email, $test_password) != SUCCESS)
    {
        echo "Received '$retval' instead of SUCCESS('".SUCCESS."').";
        return FALSE;
    }

    $iAppId = 655000;
    $iVersionId = 655200;

    $oApp = new Application($iAppId);

    /* The user wants to be a super maintainer */
    $oApp->iMaintainerRequest = SUPERMAINTAINER_REQUEST;

    /* Make sure the user is not an admin, so the app will be queued */
    $oUser->delPriv("admin");

    $oApp->create();

    /* Make the user an admin so the app can be unqueued */
    $oUser->addPriv("admin");

    $oApp->unQueue();

    /* The user should now be a super maintainer */
    $iExpected = 1;
    $iGot = Maintainer::getMaintainerCountForUser($oUser, TRUE);

    if($iGot != $iExpected)
    {
        echo "Got maintainer count of '$iGot' instead of '$iExpected'";
        return false;
    }

    Maintainer::deleteMaintainer($oUser, $iAppId);

    return true;
}

if(!test_maintainer_getMaintainerCountForUser())
    echo "test_maintainer_getMaintainerCountForUser() failed!\n";
else
    echo "test_maintainer_getMaintainerCountForUser() passed\n";


if(!test_maintainer_getAppsMaintained())
    echo "test_maintainer_getAppsMaintained() failed!\n";
else
    echo "test_maintainer_getAppsMaintained() passed\n";


if(!test_maintainer_unQueue())
    echo "test_maintainer_unQueue() failed!\n";
else
    echo "test_maintainer_unQueue() passed\n";

if(!test_superMaintainerOnAppSubmit())
    echo "test_superMaintainerOnAppSubmit() failed!\n";
else
    echo "test_superMaintainerOnAppSubmit() passed\n";

?>
