<?php

/*
 * session.php - session handler functions
 * sessions are stored in a mysql table
 */

class session
{
    // create session object
    function session ($name)
    {
        // set name for this session
        $this->name = $name;

        // define options for sessions
        ini_set('session.name', $this->name);
		ini_set('session.use_cookies', true);
		ini_set('session.use_only_cookies', true);	

        // setup session object
        session_set_save_handler(
                                 array(&$this, "_open"), 
                                 array(&$this, "_close"), 
                                 array(&$this, "_read"),
                                 array(&$this, "_write"), 
                                 array(&$this, "_destroy"), 
                                 array(&$this, "_gc")
                                );
        
        // default lifetime on session cookie (90 days)
        session_set_cookie_params(
                                  (60*60*24*90),
                                  '/'
                                 );
        
        // start the loaded session
        session_start();   
    }

    // register variables into session (dynamic load and save of vars)
    function register ($var)
    {
        global $$var;
        
        // load $var into memory
        if (isset($_SESSION[$var]))
            $$var = $_SESSION[$var];
        
        // store var into session
        $_SESSION[$var] =& $$var;
    }

    // destroy session
    function destroy ()
    {
        session_destroy();
    }
    
    // open session file (not needed for DB access)        
    function _open ($save_path, $session_name) { return true; }

    // close session file (not needed for DB access)
    function _close () { return true; }
    
    // read session
    function _read ($key)
    {
        $result = query_appdb("SELECT data FROM session_list WHERE session_id = '".$key."'");
        if (!$result) { return null; }
        $r = mysql_fetch_object($result);
        return $r->data; 
    }
    
    // write session to DB
    function _write ($key, $value)
    {
        $messages = "";
        if(isset($GLOBALS['msg_buffer']))
            $messages = implode("|", $GLOBALS['msg_buffer']);

        query_appdb("REPLACE session_list VALUES ('$key',  '".$_SESSION['current']->userid."', '".get_remote()."', '".addslashes($value)."', '$messages', NOW())");
        return true;
    }
    
    // delete current session
    function _destroy ($key)
    {
        query_appdb("DELETE FROM session_list WHERE session_id = '$key'");
        return true;
    }
    
    // clear old sessions (moved into a separate cron process)
    function _gc ($maxlifetime)
    {
        query_appdb("DELETE FROM session_list WHERE to_days(now()) - to_days(stamp) >= 7");
        return true;
    }

}
// end session

?>
