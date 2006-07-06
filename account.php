<?php
/**
 * Account login/logout handler.
 *
 * Mandatory parameters:
 *  - sCmd, action to perform ("new", "do_new", "login", "do_login", "send_passwd", "logout")
 * 
 * TODO:
 *  - replace sCmd with iAction and replace "new", "login", etc. with integer constants NEW, LOGIN, etc.
 *  - move functions into their respective modules (probably static methods of user class)
 */

// application environment
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/mail.php");

// set http header to not cache
header("Pragma: no-cache");
header("Cache-control: no-cache");

$aClean = array(); //array of filtered user input

// check command and process
if(!empty($_POST['sCmd']))
    $aClean['sCmd'] = makeSafe( $_POST['sCmd'] );
else
    $aClean['sCmd'] = makeSafe( $_GET['sCmd'] );

do_account($aClean['sCmd']);


/**
 * process according to $sCmd from URL
 */
function do_account($sCmd = null)
{
    if (!$sCmd) return 0;
    switch($sCmd)
    {
        case "new":
            apidb_header("New Account");
            include(BASE."include/"."form_new.php");
            apidb_footer();
            exit;

        case "do_new":
            cmd_do_new();
            exit;

        case "login":
            apidb_header("Login");
            include(BASE."include/"."form_login.php");
            apidb_footer();
            exit;

        case "do_login":
            cmd_do_login();
            exit;

        case "send_passwd":
            cmd_send_passwd();
            exit;

        case "logout":
            /* if we are logged in, log us out */
            if($_SESSION['current'])
                $_SESSION['current']->logout();

            redirect(apidb_fullurl("index.php"));
    }
    // not valid command, display error page
    util_show_error_page("Internal Error","This module was called with incorrect parameters");
}

/**
 * retry
 */
function retry($sCmd, $sMsg)
{
    addmsg($sMsg, "red");
    do_account($sCmd);
}


/**
 * create new account
 */
function cmd_do_new()
{
    $aClean = array(); //array of filtered user input

    $aClean['sUserEmail'] = makeSafe($_POST['sUserEmail']);
    $aClean['sUserPassword'] = makeSafe($_POST['sUserPassword']);
    $aClean['sUserPassword2'] = makeSafe($_POST['sUserPassword2']);
    $aClean['sWineRelease'] = makeSafe($_POST['sWineRelease']);
    $aClean['sUserRealname']= makeSafe($_POST['sUserRealname']);

    if(!ereg("^.+@.+\\..+$", $aClean['sUserEmail']))
    {
        $aClean['sUserEmail'] = "";
        retry("new", "Invalid email address");
        return;
    }
    if(strlen($aClean['sUserPassword']) < 5)
    {
        retry("new", "Password must be at least 5 characters");
        return;
    }
    if($aClean['sUserPassword'] != $aClean['sUserPassword2'])
    {
        retry("new", "Passwords don't match");
        return;
    }
    if(empty($aClean['sUserRealname']))
    {
        retry("new", "You don't have a Real name?");
        return;
    }
   
    $oUser = new User();

    $iResult = $oUser->create($aClean['sUserEmail'], $aClean['sUserPassword'],
                              $aClean['sUserRealname'], $aClean['sWineRelease'] );

    if($iResult == SUCCESS)
    {
        /* if we can log the user in, log them in automatically */
        $oUser->login($aClean['sUserEmail'], $aClean['sUserPassword']);

        addmsg("Account created! (".$aClean['sUserEmail'].")", "green");
        redirect(apidb_fullurl());
    }
    else if($iResult == USER_CREATE_EXISTS)
    {
        addmsg("An account with this e-mail exists already.", "red");
        retry("new", "Failed to create account");
    } else if($iResult = USER_CREATE_FAILED)
    {
        addmsg("Error while creating a new user.", "red");
        retry("new", "Failed to create account");
    } else
    {
        addmsg("Unknown failure while creating new user.  Please report this problem to appdb admins.", "red");
        retry("new", "Failed to create account");
    }
}


/**
 * email lost password
 */
function cmd_send_passwd()
{
    $aClean = array(); //array of filtered user input

    $aClean['sUserEmail'] = makeSafe($_POST['sUserEmail']);

    /* if the user didn't enter any email address we should */
    /* ask them to */
    if($aClean['sUserEmail'] == "")
    {
        addmsg("Please enter your email address in the 'E-mail' field and re-request a new password",
               "green");
        redirect(apidb_fullurl("account.php?cmd=login"));
    }

    $shNote = '(<b>Note</b>: accounts for <b>appdb</b>.winehq.org and <b>bugs</b>.winehq.org '
           .'are separated, so You might need to <b>create second</b> account for appdb.)';
		
    $iUserId = User::exists($aClean['sUserEmail']);
    $sPasswd = User::generate_passwd();
    $oUser = new User($iUserId);
    if ($iUserId)
    {
        if ($oUser->update_password($sPasswd))
        {
            $sSubject =  "Application DB Lost Password";
            $sMsg  = "We have received a request that you lost your password.\r\n";
            $sMsg .= "We will create a new password for you. You can then change\r\n";
            $sMsg .= "your password at the Preferences screen.\r\n";
            $sMsg .= "Your new password is: ".$sPasswd."\r\n";
            

            if (mail_appdb($oUser->sEmail, $sSubject ,$sMsg))
            {
                addmsg("Your new password has been emailed to you.", "green");
            }
            else
            {
                addmsg("Your password has changed, but we could not email it to you. Contact Support (".APPDB_OWNER_EMAIL.") !", "red");
            }
        }
        else
        {
            addmsg("Internal Error, we could not update your password.", "red");
        }
    }
    else
    {
        addmsg("Sorry, that user (".$aClean['sUserEmail'].") does not exist.<br><br>"
               .$shNote, "red");
    }
    
    redirect(apidb_fullurl("account.php?sCmd=login"));
}

/**
 * on login handler
 */
function cmd_do_login()
{
    $aClean = array(); //array of filtered user input

    $aClean['sUserEmail'] = makeSafe($_POST['sUserEmail']);
    $aClean['sUserPassword'] = makeSafe($_POST['sUserPassword']);

    $oUser = new User();
    $iResult = $oUser->login($aClean['sUserEmail'], $aClean['sUserPassword']);

    if($iResult == SUCCESS)
    {
        addmsg("You are successfully logged in as '$oUser->sRealname'.", "green");
        redirect(apidb_fullurl("index.php"));    	    
    } else
    {
        retry("login","Login failed ".$shNote);
    }
}

?>
