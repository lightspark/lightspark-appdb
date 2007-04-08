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
    $oFirstUser = new User();
    $retval = $oFirstUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    $iAppId = 655000;
    $iVersionId = 655200;

    $oApp = new Application();
    $oVersion = new Version();
    $oApp->iAppId = $iAppId;
    $oVersion->iVersionId = $iVersionId;
    $oSecondUser = new User();
    $oSecondUser->iUserId = $_SESSION['current']->iUserId + 1;
    /* Create a non-super maintainer for a different userId; it should not be affected
       by the other user first becoming a maintainer and then a super maintainer of
       the same application */
    $oSecondUserMaintainer = new Maintainer();
    $oSecondUserMaintainer->iAppId = $iAppId;
    $oSecondUserMaintainer->iVersionId = $iVersionId;
    $oSecondUserMaintainer->iUserId = $oSecondUser->iUserId;
    $oSecondUserMaintainer->sMaintainReason = "I need it";
    $oSecondUserMaintainer->bSuperMaintainer = FALSE;
    $oSecondUserMaintainer->create();

    /* Create a super maintainer for a different userId; it should not be affected
       by the other user first becoming a maintainer and then a super maintainer of
       the same application */
    $oSecondUserSuperMaintainer = new Maintainer();
    $oSecondUserSuperMaintainer->iAppId = $iAppId;
    $oSecondUserSuperMaintainer->iVersionId = $iVersionId;
    $oSecondUserSuperMaintainer->iUserId = $oSecondUser->iUserId;
    $oSecondUserSuperMaintainer->sMaintainReason = "I need it";
    $oSecondUserSuperMaintainer->bSuperMaintainer = TRUE;

    $oFirstUser->delPriv("admin");
    $oSecondUserSuperMaintainer->create();
    $oFirstUser->addPriv("admin");

    /* Create a non-super maintainer
       It should be removed later because a super maintainer entry for the same
       application is added */
    $oFirstUserMaintainer = new Maintainer();
    $oFirstUserMaintainer->iAppId = $iAppId;
    $oFirstUserMaintainer->iVersionId = $iVersionId;
    $oFirstUserMaintainer->iUserId = $_SESSION['current']->iUserId;
    $oFirstUserMaintainer->sMaintainReason = "The most stupid reason";
    $oFirstUserMaintainer->bSuperMaintainer = FALSE;
    $oFirstUserMaintainer->create();

    $oFirstUserMaintainer->unQueue("");

    /* There should now be 1 maintainer and 0 super maintainers */
    $iExpected = 1;
    $iReceived = maintainer::getMaintainerCountForUser($oFirstUser, FALSE);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return FALSE;
    }

    $iExpected = 0;
    $iReceived = maintainer::getMaintainerCountForUser($oFirstUser, TRUE);
    if($iExpected != $iReceived)
    {
        echo "Got super maintainer count of $iReceived instead of $iExpected\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return FALSE;
    }

    /**
      * make the user a super maintatiner
      */
    /* queue up this maintainer */
    $oFirstUserSuperMaintainer = new Maintainer();
    $oFirstUserSuperMaintainer->iAppId = $iAppId;
    $oFirstUserSuperMaintainer->iVersionId = $iVersionId;
    $oFirstUserSuperMaintainer->iUserId = $_SESSION['current']->iUserId;
    $oFirstUserSuperMaintainer->sMaintainReason = "Some crazy reason";
    $oFirstUserSuperMaintainer->bSuperMaintainer = TRUE;
    $oFirstUserSuperMaintainer->create();

    /* and unqueue it to accept the user as a maintainer */
    $oFirstUserSuperMaintainer->unQueue("Some reply text");

    /* unqueue it again to ensure that unQueueing a maintainer request twice works properly */
    $oFirstUserSuperMaintainer->unQueue("Some other reply text");


    /* see that the user is a super maintainer of the one application we added them to be */
    $iExpected = 1; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = maintainer::getMaintainerCountForUser($oFirstUser, TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return false;
    }

    /* maintainer count should be zero, because unQueue should have removed the
       previous non-super maintainer entry */
    $iExpected = 0;
    $iMaintainerCount = maintainer::getMaintainerCountForUser($oFirstUser, FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return false;
    }

    /* Now the maintainer request for the other user should still be present */
    $iExpected = 1;
    $iReceived = maintainer::getmaintainerCountForUser($oSecondUser, FALSE);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return FALSE;
    }

    /* Now the super maintainer request for the other user should still be present */
    $oSecondUserSuperMaintainer->unQueue();
    $iExpected = 1;
    $iReceived = maintainer::getmaintainerCountForUser($oSecondUser, TRUE);
    if($iExpected != $iReceived)
    {
        echo "Got super maintainer count of $iReceived instead of $iExpected\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return FALSE;
    }

    /* Now the maintainer request of the other user should be gone */
    $oSecondUserMaintainer->unQueue();
    $iExpected = 0;
    $iReceived = maintainer::getmaintainerCountForUser($oSecondUser, FALSE);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected\n";
        maintainer::deleteMaintainersForApplication($oApp);
        maintainer::deleteMaintainersForVersion($oVersion);
        return FALSE;
    }

    /* remove maintainership for this user */
    maintainer::deleteMaintainersForApplication($oApp);
    maintainer::deleteMaintainersForVersion($oVersion);

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

    $oApp->delete();

    return true;
}

/* deleteMaintainersForVersion() should fail if versionId = 0
   Otherwise it will delete all super maintainers */
function test_maintainer_deleteMaintainersForVersion()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    $oUser = new user();
    $oUser->login($test_email, $test_password);

    $oMaintainer = new maintainer();
    $oMaintainer->iAppId = 655000;
    $oMaintainer->iVersionId = 0;
    $oMaintainer->iUserId = 655000;
    $oMaintainer->sMaintainReason = "Silly reason";
    $oMaintainer->bSuperMaintainer = 1;

    $oMaintainer->create();

    $oVersion = new version();
    $oVersion->iVersionId = 0;

    if(maintainer::deleteMaintainersForVersion($oVersion) !== FALSE)
    {
        echo "Got success, but this should fail.\n";
        return FALSE;
    }

    $oMaintainer->delete();

    return TRUE;
}

function test_maintainer_getMaintainersForAppIdVersionId()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;
    $oUser = new user();
    if($oUser->login($test_email, $test_password) != SUCCESS)
    {
        echo "Failed to create and log in user!\n";
        return FALSE;
    }

    $oUser->addPriv("admin");

    $oApp = new application();
    $oApp->create();
    $oFirstVersion = new version();
    $oFirstVersion->iAppId = $oApp->iAppId;
    $oFirstVersion->create();
    $oSecondVersion = new version();
    $oSecondVersion->iAppid = $oApp->iAppId;
    $oSecondVersion->create();

    $oSuperMaintainer = new maintainer();
    $oSuperMaintainer->bSuperMaintainer = TRUE;
    $oSuperMaintainer->sMaintainReason = "Because";
    $oSuperMaintainer->iAppId = $oApp->iAppId;
    $oSuperMaintainer->iUserId = $oUser->iUserId;
    $oSuperMaintainer->create();

    if(!$hResult = maintainer::getMaintainersForAppIdVersionId($oApp->iAppId))
    {
        echo "Failed to get list of maintainers!\n";
        return FALSE;
    }

    /* The application should have one maintainer */
    $iExpected = 1;
    $iReceived = mysql_num_rows($hResult);
    if($iExpected != $iReceived)
    {
        echo "Got super maintainer count of $iReceived instead of $iExpected!\n";
        return FALSE;
    }

    if(!$hResult = maintainer::getMaintainersForAppIdVersionId(null,
       $oFirstVersion->iVersionId))
    {
        echo "Failed to get list of maintainers!\n";
        return FALSE;
    }

    /* The version should have one maintainer */
    $iExpected = 1;
    $iReceived = mysql_num_rows($hResult);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected!\n";
        return FALSE;
    }

    $oSuperMaintainer->delete();

    /* Become a maintainer for one of the versions only */
    $oFirstVersionMaintainer = new maintainer();
    $oFirstVersionMaintainer->sMaintainReason = "I need it";
    $oFirstVersionMaintainer->iVersionId = $oFirstVersion->iVersionId;
    $oFirstVersionMaintainer->iAppId = $oFirstVersion->iAppId;
    $oFisrtVersionMaintainer->bSuperMaintainer = FALSE;
    $oFirstVersionMaintainer->iUserId = $oUser->iUserId;
    $oFirstVersionMaintainer->create();

    if(!$hResult = maintainer::getMaintainersForAppIdVersionId(null,
       $oFirstVersion->iVersionId))
    {
        echo "Failed to get list of maintainers!\n";
        return FALSE;
    }

    /* The first version should have one maintainer */
    $iExpected = 1;
    $iReceived = mysql_num_rows($hResult);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected!\n";
        return FALSE;
    }

    if(!$hResult = maintainer::getMaintainersForAppIdVersionId(null,
           $oSecondVersion->iVersionId))
    {
        echo "Failed to get list of maintainers!\n";
        return FALSE;
    }

    /* The second version should not have any maintainers */
    $iExpected = 0;
    $iReceived = mysql_num_rows($hResult);
    if($iExpected != $iReceived)
    {
        echo "Got maintainer count of $iReceived instead of $iExpected!\n";
        return FALSE;
    }

    $oApp->delete();
    $oUser->delete();

    return TRUE;
}
if(!test_maintainer_getMaintainerCountForUser())
{
    echo "test_maintainer_getMaintainerCountForUser() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_maintainer_getMaintainerCountForUser() passed\n";
}


if(!test_maintainer_getAppsMaintained())
{
    echo "test_maintainer_getAppsMaintained() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_maintainer_getAppsMaintained() passed\n";
}


if(!test_maintainer_unQueue())
{
    echo "test_maintainer_unQueue() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_maintainer_unQueue() passed\n";
}

if(!test_superMaintainerOnAppSubmit())
{
    echo "test_superMaintainerOnAppSubmit() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_superMaintainerOnAppSubmit() passed\n";
}

if(!test_maintainer_deleteMaintainersForVersion())
{
    echo "test_maintainer_deleteMaintainersForVersion() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_maintainer_deleteMaintianersForVersion() passed\n";
}

if(!test_maintainer_getMaintainersForAppIdVersionId())
{
    echo "test_maintainer_getMaintainersForAppIdVersionId() failed!\n";
    $bTestSuccess = false;
} else
{
    echo "test_maintainer_getMaintainersForAppIdVersionId() passed\n";
}

?>
