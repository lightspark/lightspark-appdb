<?php
/********************************************/
/* Account Login / Logout Handler for AppDB */
/********************************************/

include("path.php");
include(BASE."include/"."incl.php");

// set http header to not cache
header("Pragma: no-cache");
header("Cache-control: no-cache");

// check command and process
if(isset($_POST['cmd']))
    do_account($_POST['cmd']);
else
    do_account($_GET['cmd']);


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
    
    if(!ereg("^.+@.+\\..+$", $_POST['ext_email']))
    {
        $_POST['ext_email'] = "";
        retry("new", "Invalid email address");
        return;
    }
    if(strlen($_POST['ext_password']) < 5)
    {
        retry("new", "Password must be at least 5 characters");
        return;
    }
    if($_POST['ext_password'] != $_POST['ext_password2'])
    {
        retry("new", "Passwords don't match");
        return;
    }
    if(!isset($_POST['ext_realname']))
    {
        retry("new", "You don't have a Real name?");
        return;
    }
   
    $user = new User();

    if($user->exists($_POST['ext_email']))
    {
        $_POST['ext_email'] = "";
        retry("new", "An account with this e-mail is already in use");
        return;
    }

    $result = $user->create($_POST['ext_email'], $_POST['ext_password'], $_POST['ext_realname'], $_POST['CVSrelease'] );

    if($result == null)
    {
        $user->login($_POST['ext_email'], $_POST['ext_password']);
        addmsg("Account created! (".$_POST['ext_email'].")", "green");
        redirect(apidb_fullurl());
    }
    else
        retry("new", "Failed to create account: $result");
}


/**
 * email lost password
 */
function cmd_send_passwd()
{
    $user = new User();
    
    $userid = $user->lookup_userid($_POST['ext_email']);
    $passwd = generate_passwd();
    
    if ($userid)
    {
        if ($user->update($userid, $passwd))
        {
            $msg =  "Application DB Lost Password\n";
            $msg .= "----------------------------\n";
            $msg .= "We have received a request that you lost your password.\n";
            $msg .= "We will create a new password for you. You can then change\n";
            $msg .= "your password at the Preferences screen.\n\n";
            $msg .= "Your new password is: ".$passwd."\n\n";
            
            if (mail($user->lookup_email($userid), '[AppDB] Lost Password', $msg))
            {
                addmsg("Your new password has been emailed to you.", "green");
            }
            else
            {
                addmsg("Your password has changed, but we could not email it to you. Contact Support!", "red");
            }
        }
        else
        {
            addmsg("Internal Error, we could not update your password.", "red");
        }
    }
    else
    {
        addmsg("Sorry, that user (". urlencode($_POST['ext_email']) .") does not exist.", "red");
    }
    
    redirect(apidb_fullurl("account.php?cmd=login"));
}


/**
 * on login handler
 */
function cmd_do_login()
{
    $user = new User();
    $result = $user->login($_POST['ext_email'], $_POST['ext_password']);

    if($result == null)
    {
        $_SESSION['current'] = $user;
        addmsg("You are successfully logged in as '$user->realname'.", "green");
        redirect(apidb_fullurl("index.php"));    	    
    } else
    {
        retry("login","Login failed ($result)");
        $_SESSION['current'] = "";
    }
}

?>
