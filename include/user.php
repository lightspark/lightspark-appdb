<?php
/******************************************/ 
/* This class represents a logged in user */
/******************************************/

class User {

    var $stamp;
    var $userid;
    var $username;
    var $realname;
    var $created;
    var $status;
    var $perm;
    var $CVSrelease;

    /**
     * constructor
     * opens a connection to the user database
     */
    function User()
    {

    }

    /**
     * check if a user exists
     * returns TRUE if the user exists
     */
    function exists($username)
    {
        $result = mysql_query("SELECT * FROM user_list WHERE username = '$username'");
        if(!$result || mysql_num_rows($result) != 1)
            return 0;
         return 1;
    }


    function lookup_username($userid)
    {
        return lookupUsername($userId);
    }

    function lookup_userid($username)
    {
        $result = mysql_query("SELECT userid FROM user_list WHERE username = '$username'");
        if(!$result || mysql_num_rows($result) != 1)
            return null;
        $ob = mysql_fetch_object($result);
        return $ob->userid;
    }


    function lookup_realname($userid)
    {
        $result = mysql_query("SELECT realname FROM user_list WHERE userid = $userid");
        if(!$result || mysql_num_rows($result) != 1)
            return null;
        $ob = mysql_fetch_object($result);
        return $ob->realname;
    }


    function lookup_email($userid)
    {
        return lookupEmail($userid);
    }

    function lookup_CVSrelease($userId)
    {
        $result = mysql_query("SELECT CVSrelease FROM user_list WHERE userId = '$userId'");
        if(!$result || mysql_num_rows($result) != 1)
            return null;
        $ob = mysql_fetch_object($result);
        return $ob->CVSrelease;
    }

    /**
     * restore a user from the database
     * returns 0 on success and an error msg on failure
     */
    function restore($username, $password)
    {
        $result = mysql_query("SELECT stamp, userid, username, realname, ".
                              "created, status, perm FROM user_list WHERE ".
                              "username = '$username' AND ".
                              "password = password('$password')");
        if(!$result)
            return "Error: ".mysql_error();

        if(mysql_num_rows($result) == 0)
            return "Invalid username or password";

        list($this->stamp, $this->userid, $this->username, $this->realname, 
             $this->created, $status, $perm) = mysql_fetch_row($result);

        return 0;
    }


    function login($username, $password)
    {
        $result = $this->restore($username, $password);

        /* if our result is non-null then we must have had an error */
        if($result != null)
            return $result;

        /* update the 'stamp' field in the users account to reflect the last time */
        /* they logged in */
        $myUserId = $this->lookup_userid($username);
        $result = mysql_query("UPDATE user_list SET stamp=null WHERE userid=$myUserId;");
        return 0;
    }


    /*
     * create a new user
     * returns 0 on success and an error msg on failure
     */
    function create($username, $password, $realname, $email)
    {
        $result = mysql_query("INSERT INTO user_list VALUES ( NOW(), 0, ".
                              "'$username', password('$password'), ".
                              "'$realname', '$email', NOW(), 0, 0)");
        if(!$result)
            return mysql_error();
        return $this->restore($username, $password);
    }


    /**
     * Update User Account;
     */
    function update($userid = 0, $password = null, $realname = null, $email = null, $CVSrelease = null)
    {
        if (!$userid)
            return 0;
        if ($password)
        {
            if (!mysql_query("UPDATE user_list SET password = password('$password') WHERE userid = $userid"))
                return 0;
        }

        if ($realname)
        {
            if (!mysql_query("UPDATE user_list SET realname = '".addslashes($realname)."' WHERE userid = $userid"))
                return 0;
        }

        if ($email)
        {
            if (!mysql_query("UPDATE user_list SET email = '".addslashes($email)."' WHERE userid = $userid"))
                return 0;
        }

        if ($CVSrelease)
        {
            if (!mysql_query("UPDATE user_list SET CVSrelease = '".addslashes($CVSrelease)."' WHERE userid = $userid"))
                return 0;
        }

        return 1;
    }

    /**
     * remove the current, or specified user from the database
     * returns 0 on success and an error msg on failure
     */
    function remove($username = 0)
    {
        if($username == 0)
            $username = $this->username;

        $result = mysql_query("DELETE FROM user_list WHERE username = '$username'");

        if(!$result)
            return mysql_error();
        if(mysql_affected_rows($result) == 0)
            return "No such user.";
        return 0;
    }


    function done()
    {
    
    }


    function getpref($key, $def = null)
    {
        if(!$this->userid || !$key)
            return $def;

        $result = mysql_query("SELECT * FROM user_prefs WHERE userid = $this->userid AND name = '$key'");
        if(!$result || mysql_num_rows($result) == 0)
            return $def;
        $ob = mysql_fetch_object($result);
        return $ob->value; 
    }


    function setpref($key, $value)
    {
        if(!$this->userid || !$key || !$value)
            return null;

        $result = mysql_query("DELETE FROM user_prefs WHERE userid = $this->userid AND name = '$key'");
        $result = mysql_query("INSERT INTO user_prefs VALUES($this->userid, '$key', '$value')");
        echo mysql_error();
        return $result ? true : false;
    }


    /**
     * check if this user has $priv
     */
    function checkpriv($priv)
    {
        if(!$this->userid || !$priv)
            return 0;

        $result = mysql_query("SELECT * FROM user_privs WHERE userid = $this->userid AND priv  = '$priv'");
        if(!$result)
            return 0;
        return mysql_num_rows($result);
    }


    /**
     * check if this user is an maintainer of a given appId/versionId
     */
    function is_maintainer($appId, $versionId)
    {
        if(!$this->userid)
            return false;

        /* if this user is a super maintainer of this appid then they */
        /* are a maintainer of all of the versionId's of it as well */
        if($this->is_super_maintainer($appId))
        {
            return true;
        }

        $query = "SELECT * FROM appMaintainers WHERE userid = '$this->userid' AND appId = '$appId' AND versionId = '$versionId'";
        $result = mysql_query($query);
        if(!$result)
            return 0;
        return mysql_num_rows($result);
    }


    /*
     * check if this user is an maintainer of a given appId/versionId
     */
    function is_super_maintainer($appId)
    {
        if(!$this->userid)
            return false;

        $query = "SELECT * FROM appMaintainers WHERE userid = '$this->userid' AND appId = '$appId' AND superMaintainer = '1'";
        $result = mysql_query($query);
        if(!$result)
            return 0;
        return mysql_num_rows($result);
    }


    function addpriv($priv)
    {
        if(!$this->userid || !$priv)
            return 0;

        if($this->checkpriv($priv))
            return 1;

        $result = mysql_query("INSERT INTO user_privs VALUES ($this->userid, '$priv')");
        return $result;
    }


    function delpriv($priv)
    {
        if(!$this->userid || !$priv)
            return 0;

        $result = mysql_query("DELETE FROM user_privs WHERE userid = $this->userid AND priv = '$priv'");
        return $result;
    }
}


function loggedin()
{
    if(isset($_SESSION['current']) && $_SESSION['current']->userid)
        return true;
    return false;
}


function havepriv($priv)
{
    if(!loggedin())
        return false;
    return $_SESSION['current']->checkpriv($priv);
}

function debugging()
{
    return ((loggedin() && $_SESSION['current']->getpref("debug") == "yes") || APPDB_DEBUG == 1);
}


function makeurl($text, $url, $pref = null)
{
    if(loggedin())
    {
        if($_SESSION['current']->getpref($pref) == "yes")
            $extra = "window='new'";
    }
    return "<a href='$url' $extra> $text </a>\n";
}


/**
 * create a new random password
 */
function generate_passwd($pass_len = 10)
{
    $nps = "";
    mt_srand ((double) microtime() * 1000000);
    while (strlen($nps)<$pass_len)
    {
        $c = chr(mt_rand (0,255));
        if (eregi("^[a-z0-9]$", $c)) $nps = $nps.$c;
    }
    return ($nps);
}


function lookupUsername($userid)
{
    $result = mysql_query("SELECT username FROM user_list WHERE userid = $userid");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->username;
}


function lookupEmail($userid)
{
    $result = mysql_query("SELECT email FROM user_list WHERE userid = $userid");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->email;
}


function UserWantsEmail($userid)
{
    $result = mysql_query("SELECT * FROM user_prefs WHERE userid = $userid AND name = 'send_email'");
    if(!$result || mysql_num_rows($result) == 0)
    {
        return true;
    }
    $ob = mysql_fetch_object($result);
    return ($ob->value == 'no' ? false : true); 
}


/**
 * get the email address of people to notify for this appId and versionId
 */
function getNotifyEmailAddressList($appId, $versionId)
{
    $aUserId = array();
    $c = 0;
    $retval = "";

    $query = "SELECT userId FROM ".
                          "appMaintainers WHERE appId = '$appId' " .
                          "AND versionId = '$versionId';";
    $result = mysql_query($query);
    if(mysql_num_rows($result) > 0)
    {
        while($row = mysql_fetch_object($result))
        {
            $aUserId[$c] = array($row->userId);
            $c++;
        }
    }
    $result = mysql_query("SELECT * FROM user_privs WHERE priv  = 'admin'");
    if(mysql_num_rows($result) > 0)
    {
        while($row = mysql_fetch_object($result))
        {
            $i = array_search($row->userid, $aUserId);
            if ($aUserId[$i] != array($row->userid))
            {
                $aUserId[$c] = array($row->userid);
                $c++;
            }
        }

    }
    if ($c > 0)
    {
        while(list($index, list($userIdValue)) = each($aUserId))
        {
            if (UserWantsEmail($userIdValue))
                $retval .= lookupEmail($userIdValue)." ";
        }
    }
    return $retval;
}


/**
 * Get the number of users in the database 
 */
function getNumberOfUsers()
{
    $result = mysql_query("SELECT count(*) as num_users FROM user_list;");
    $row = mysql_fetch_object($result);
    return $row->num_users;
}


/**
 * Get the number of active users within $days of the current day
 */
function getActiveUsersWithinDays($days)
{
    $result = mysql_query("SELECT count(*) as num_users FROM user_list WHERE stamp >= DATE_SUB(CURDATE(), interval $days day);");
    $row = mysql_fetch_object($result);
    return $row->num_users;
}

?>
