<?php
/********************************************/
/* Account Login / Logout Handler for AppDB */
/********************************************/
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/mail.php");

// set http header to not cache
header("Pragma: no-cache");
header("Cache-control: no-cache");

$aClean = array(); //array of filtered user input

// check command and process
if(!empty($_POST['cmd']))
    $aClean['cmd'] = makeSafe( $_POST['cmd'] );
else
    $aClean['cmd'] = makeSafe( $_GET['cmd'] );

do_account($aClean['cmd']);


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

            /* destroy all session variables */
            $GLOBALS['session']->destroy();

            addmsg("You are successfully logged out.", "green");
            redirect(apidb_fullurl("index.php"));
            exit;
    }
    //not valid command, display error page
    util_show_error_page("Internal Error","This module was called with incorrect parameters");
    exit;
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

    $aClean['ext_email'] = makeSafe($_POST['ext_email']);
    $aClean['ext_password'] = makeSafe($_POST['ext_password']);
    $aClean['ext_password2'] = makeSafe($_POST['ext_password2']);
    $aClean['CVSrelease'] = makeSafe($_POST['CVSrelease']);
    $aClean['ext_realname']= makeSafe($_POST['ext_realname']);

    if(!ereg("^.+@.+\\..+$", $aClean['ext_email']))
    {
        $aClean['ext_email'] = "";
        retry("new", "Invalid email address");
        return;
    }
    if(strlen($aClean['ext_password']) < 5)
    {
        retry("new", "Password must be at least 5 characters");
        return;
    }
    if($aClean['ext_password'] != $aClean['ext_password2'])
    {
        retry("new", "Passwords don't match");
        return;
    }
    if(empty($aClean['ext_realname']))
    {
        retry("new", "You don't have a Real name?");
        return;
    }
   
    $oUser = new User();

    $iResult = $oUser->create($aClean['ext_email'], $aClean['ext_password'], $aClean['ext_realname'], $aClean['CVSrelease'] );

    if($iResult == SUCCESS)
    {
        /* if we can log the user in, log them in automatically */
        $oUser->login($aClean['ext_email'], $aClean['ext_password']);

        addmsg("Account created! (".$aClean['ext_email'].")", "green");
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

    $aClean['ext_email'] = makeSafe($_POST['ext_email']);

    /* if the user didn't enter any email address we should */
    /* ask them to */
    if($aClean['ext_email'] == "")
    {
        addmsg("Please enter your email address in the 'E-mail' field and re-request a new password",
               "green");
        redirect(apidb_fullurl("account.php?cmd=login"));
    }

    $shNote = '(<b>Note</b>: accounts for <b>appdb</b>.winehq.org and <b>bugs</b>.winehq.org '
           .'are separated, so You might need to <b>create second</b> account for appdb.)';
		
    $iUserId = User::exists($aClean['ext_email']);
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
        addmsg("Sorry, that user (".$aClean['ext_email'].") does not exist.<br><br>"
               .$shNote, "red");
    }
    
    redirect(apidb_fullurl("account.php?cmd=login"));
}

/**
 * on login handler
 */
function cmd_do_login()
{
    $aClean = array(); //array of filtered user input

    $aClean['ext_email'] = makeSafe($_POST['ext_email']);
    $aClean['ext_password'] = makeSafe($_POST['ext_password']);

    $oUser = new User();
    $iResult = $oUser->login($aClean['ext_email'], $aClean['ext_password']);

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
