<?

function apidb_session_start()
{

    session_set_cookie_params(time() + 3600 * 48);
    session_start();

    if(isset($_SESSION['current']))
      $_SESSION['current']->connect();
}


function apidb_session_destroy()
{
    session_destroy();
}



/**
 * session handler functions
 * sessions are stored in a mysql table
 */
function _session_open($save_path, $session_name)
{
    opendb();
    //mysql_query("CREATE TABLE IF NOT EXISTS session_list (session_id varchar(64) not null, ".
    //		"userid int, ip varchar(64), data text, messages text, stamp timestamp, primary key(session_id))");
    return true;
}

function _session_close()
{
    return true;
}

function _session_read($key)
{
    global $msg_buffer;

    opendb();
    $result = mysql_query("SELECT data, messages FROM session_list WHERE session_id = '$key'");
    
    if(!$result)
	return null;
    $r = mysql_fetch_object($result);

    if($r->messages)
	$msg_buffer = explode("|", $r->messages);

    return $r->data;
}

function _session_write($key, $value)
{
    global $msg_buffer;
    global $apidb_debug;

    opendb();


    if($msg_buffer)
	$messages = implode("|", $msg_buffer);
    else
	$messages = "";


    // remove single quotes
    $value = str_replace("'", "", $value);


    //DEBUGGING
    if ($apidb_debug)
        mysql_query("INSERT INTO debug VALUES(null, '$key = $messages')");


    if(isset($_SESSION['current']))
       mysql_query("REPLACE session_list VALUES ('$key', ".$_SESSION['current']->userid.", '".get_remote()."', '$value', '$messages', NOW())");
    else
	mysql_query("REPLACE session_list VALUES ('$key', 0, '".get_remote()."', null, '$messages', NOW())");

    return true;

}

function _session_destroy($key)
{
    mysql_query("DELETE FROM session_list WHERE session_id = '$key'");
    return true;
}

function _session_gc($maxlifetime)
{
    // delete sessions older than 2 days
    mysql_query("DELETE FROM session_list WHERE to_days(now()) - to_days(stamp) >= 2");
    return true;
}

session_set_save_handler("_session_open", 
			 "_session_close", 
			 "_session_read",
			 "_session_write", 
			 "_session_destroy", 
			 "_session_gc");

session_register("current");

?>
