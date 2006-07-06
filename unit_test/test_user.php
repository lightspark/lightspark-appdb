<?php

/* unit tests for user class */

require_once("path.php");
require_once("test_common.php");
require_once(BASE."include/incl.php");
require_once(BASE."include/user.php");
require_once(BASE."include/application.php");

/* TODO: check permissions functions */

$test_email = "testemail@somesite.com";
$test_password = "password";

/* NOTE: test_user_login() relies on this function leaving the test user */
/* in the database */
function test_user_create()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    $oUser = new User();

    /* delete the user if they already exist */
    if($oUser->login($test_email, $test_password) == SUCCESS)
    {
        $oUser->delete();
        $oUser = new User();
    }

    /* create the user */
    $retval = $oUser->create("testemail@somesite.com", "password", "Test user", "20051020");
    if($retval != SUCCESS)
    {
        if($retval == USER_CREATE_EXISTS)
            echo "The user already exists!\n";
        else if($retval == USER_LOGIN_FAILED)
            echo "User login failed!\n";
        else
            echo "ERROR: UNKNOWN ERROR!!\n";
            
        return false;
    }

    /* try creating the user again, see that we get USER_CREATE_EXISTS */
    $retval = $oUser->create("testemail@somesite.com", "password", "Test user", "20051020");
    if($retval != USER_CREATE_EXISTS)
    {
        echo "Got '".$retval."' instead of USER_CREATE_EXISTS(".USER_CREATE_EXISTS.")\n";
        return false;
    }

    return true;
}

/* NOTE: relies on test_create_user() being run first and leaving a user */
/*  created in the db */
function test_user_login()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    /* test that correct information results in a correct login */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* test that incorrect user results in a login failed */
    $oUser = new User();
    $retval = $oUser->login("some nutty username", $testpassword);
    if($retval != USER_LOGIN_FAILED)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* test that incorrect password results in a login failed */
    $oUser = new User();
    $retval = $oUser->login($test_email, "some password");
    if($retval != USER_LOGIN_FAILED)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    return true;
}


function test_user_update_set_test($realname, $winerelease)
{
    global $test_email, $test_password;

    /* log the user in */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* modify the users realname and wine release */
    $oUser->sRealname = $realname;
    $oUser->sWineRelease = $winerelease;
    $oUser->update(); /* save the changes */

    /* log the user in again */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != SUCCESS)
    {
        echo "Got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* make sure the realname and wine release match */
    if($oUser->sRealname != $realname)
    {
        echo "Realname of '".$oUser->sRealname."' doesn't match expected realname of '".$realname."'\n";
        return false;
    }

    if($oUser->sWineRelease != $winerelease)
    {
        echo "Wine release of '".$oUser->sWineRelease."' doesn't match expected wine release of '".$winerelease."'\n";
        return false;
    }

    return true;
}

/* test that we can set values and call user::update() and have the values be saved */
function test_user_update()
{
    test_start(__FUNCTION__);

    global $test_email, $test_password;

    if(!test_user_update_set_test("some bogus realname", "some crazy wine release"))
    {
        return false;
    }

    if(!test_user_update_set_test("some new bogus realname", "some new crazy wine release"))
    {
        return false;
    }

    return true;
}

function test_user_delete()
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

    /* delete the user */
    $oUser->delete();

    /* try to log in again */
    $oUser = new User();
    $retval = $oUser->login($test_email, $test_password);
    if($retval != USER_LOGIN_FAILED)
    {
        echo "Got '".$retval."' instead of USER_LOGIN_FAILED(".USER_LOGIN_FAILED.")\n";
        return false;
    }


    /* now create the user again and see that it is created successfully */

    /* create the user */
    $oUser = new User();
    $retval = $oUser->create($test_email, $test_password, "Test user", "20051020");
    if($retval != SUCCESS)
    {
        if($retval == USER_CREATE_EXISTS)
            echo "The user already exists!\n";
        else if($retval == USER_LOGIN_FAILED)
            echo "User login failed!\n";
        else
            echo "ERROR: UNKNOWN ERROR!!\n";
            
        return false;
    }

    return true;
}

function test_user_getpref_setpref()
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

    /* set a preference and retrieve it */
    $pref_key = "testpreference";
    $pref_value = "test value";
    $oUser->setPref($pref_key, $pref_value);

    $got_pref = $oUser->getPref($pref_key);
    if($got_pref != $pref_value)
    {
        echo "Expected preference value of '".$pref_value."' got preference value of '".$got_pref."'\n";
        return false;
    }
    
    return true;
}

function test_user_update_password()
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

    /* change the users password to something new */
    $sNewPassword = $test_password.$test_password;
    if(!$oUser->update_password($sNewPassword))
    {
        echo "user::update_password() failed to update password to '".$sNewPassword."'\n";
        return false;
    }

    /* log the user in again, using the new password this time */
    $oUser = new User();
    $retval = $oUser->login($test_email, $sNewPassword);
    if($retval != SUCCESS)
    {
        echo "Failed to login with new password, got '".$retval."' instead of SUCCESS(".SUCCESS.")\n";
        return false;
    }

    /* change the password back to the original one */
    if(!$oUser->update_password($test_password))
    {
        echo "user::update_password() failed, unable to restore password to .".$test_password."'\n";
        return false;
    }

    return true;
}

function test_user_getMaintainerCount()
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
    $iQueueId = 655300;
    $statusMessage = $oUser->addAsMaintainer($iAppId, $iVersionId, TRUE, $iQueueId);

    /* see that the user is a super maintainer of the one application we added them to be */
    $iExpected = 1; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = $oUser->getMaintainerCount(TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* maintainer count should be zero */
    $iExpected = 0;
    $iMaintainerCount = $oUser->getMaintainerCount(FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* remove maintainership for this user */
    $oUser->deleteMaintainer($iAppId);




    /**
     * make the user a maintainer
     */
    $statusMessage = $oUser->addAsMaintainer($iAppId, $iVersionId, FALSE, $iQueueId);
   
    /* see that the user is a super maintainer of no applications */
    $iExpected = 0; /* we expect 1 super maintainer for this user */
    $iSuperMaintainerCount = $oUser->getMaintainerCount(TRUE);
    if($iSuperMaintainerCount != $iExpected)
    {
        echo "Got super maintainer count of '".$iSuperMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* maintainer count should be one */
    $iExpected = 1;
    $iMaintainerCount = $oUser->getMaintainerCount(FALSE);
    if($iMaintainerCount != $iExpected)
    {
        echo "Got maintainer count of '".$iMaintainerCount."' instead of '".$iExpected."'\n";
        return false;
    }

    /* remove maintainership for this user */
    $oUser->deleteMaintainer($iAppId, $iVersionId);

    return true;
}

function test_user_getAppsMaintained()
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
    $iQueueId = 655300;
    $bSuperMaintainer = TRUE;
    $statusMessage = $oUser->addAsMaintainer($iAppId, $iVersionId, $bSuperMaintainer, $iQueueId);

    /* get an array of the apps maintained */
    $aAppsMaintained = $oUser->getAppsMaintained();

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
    $oUser->deleteMaintainer($iAppId);

    return true;
}


/*************************/
/* Main testing routines */

if(!test_user_create())
    echo "test_user_create() failed!\n";
else
    echo "test_user_create() passed\n";

if(!test_user_login())
    echo "test_user_login() failed!\n";
else
    echo "test_user_login() passed\n";

if(!test_user_update())
    echo "test_user_update() failed!\n";
else
    echo "test_user_update() passed\n";

if(!test_user_delete())
    echo "test_user_delete() failed!\n";
else
    echo "test_user_delete() passed\n";

if(!test_user_getpref_setpref())
    echo "test_user_getpref_setpref() failed!\n";
else
    echo "test_user_getpref_setpref() passed\n";

if(!test_user_update_password())
    echo "test_user_update_password() failed!\n";
else
    echo "test_user_update_password() passed\n";

if(!test_user_getMaintainerCount())
    echo "test_user_getMaintainerCount() failed!\n";
else
    echo "test_user_getMaintainerCount() passed\n";

if(!test_user_getAppsMaintained())
    echo "test_user_getAppsMaintained() failed!\n";
else
    echo "test_user_getAppsMaintained() passed\n";

/* TODO: the rest of the user member functions we don't currently test */



/* clean up the user we created during testing */
/* so the unit test leaves no trace that it ran */
$oUser = new User();

/* delete the user if they already exist */
if($oUser->login($test_email, $test_password) == SUCCESS)
{
    $oUser->delete();
    $oUser = new User();
}

?>