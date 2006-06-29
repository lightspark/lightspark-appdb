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
 * process according to $cmd from URL
 */
function do_account($cmd = null)
{
    if (!$cmd) return 0;
    switch($cmd)
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
            $GLOBALS['session']->destroy();
            addmsg("You are successfully logged out.", "green");
            redirect(apidb_fullurl("index.php"));
            exit;
    }
    //not valid command, display error page
    errorpage("Internal Error","This module was called with incorrect parameters");
    exit;
}

/**
 * retry
 */
function retry($cmd, $msg)
{
    addmsg($msg, "red");
    do_account($cmd);
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
   
    $user = new User();

    $result = $user->create($aClean['ext_email'], $aClean['ext_password'], $aClean['ext_realname'], $aClean['CVSrelease'] );

    if($result == SUCCESS)
    {
        /* if we can log the user in, log them in automatically */
        if($user->login($aClean['ext_email'], $aClean['ext_password']) == SUCCESS)
            $_SESSION['current'] = $user;

        addmsg("Account created! (".$aClean['ext_email'].")", "green");
        redirect(apidb_fullurl());
    }
    else if($result == USER_CREATE_EXISTS)
    {
        addmsg("An account with this e-mail exists already.", "red");
        retry("new", "Failed to create account");
    } else if($result = USER_CREATE_FAILED)
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

    $note = '(<b>Note</b>: accounts for <b>appdb</b>.winehq.org and <b>bugs</b>.winehq.org '
           .'are separated, so You might need to <b>create second</b> account for appdb.)';
		
    $userid = User::exists($aClean['ext_email']);
    $passwd = User::generate_passwd();
    $user = new User($userid);
    if ($userid)
    {
        if ($user->update(null, $passwd))
        {
            $sSubject =  "Application DB Lost Password";
            $sMsg  = "We have received a request that you lost your password.\r\n";
            $sMsg .= "We will create a new password for you. You can then change\r\n";
            $sMsg .= "your password at the Preferences screen.\r\n";
            $sMsg .= "Your new password is: ".$passwd."\r\n";
            

            if (mail_appdb($user->sEmail, $sSubject ,$sMsg))
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
               .$note, "red");
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

    $user = new User();
    $result = $user->login($aClean['ext_email'], $aClean['ext_password']);

    if($result == SUCCESS)
    {
        $_SESSION['current'] = $user;
        addmsg("You are successfully logged in as '$user->sRealname'.", "green");
        redirect(apidb_fullurl("index.php"));    	    
    } else
    {
        retry("login","Login failed ".$note);
        $_SESSION['current'] = "";
    }
}

?>
