<?php

$dbcon = null;
$dbref = 0;

function opendb()
{
    global $apidb_dbuser, $apidb_dbpass, $apidb_dbhost, $apidb_db;
    global $dbcon, $dbref;

    $dbref++;

    if($dbcon)
	return $dbcon;

    $dbcon = mysql_connect($apidb_dbhost, $apidb_dbuser, $apidb_dbpass);
    if(!$dbcon) 
	{
	    echo "An error occurred: ".mysql_error()."<p>\n";
	    exit;
	}
    mysql_select_db($apidb_db);
    return $dbcon;
}

function closedb()
{
    global $dbcon, $dbref;

    if(--$dbref)
	return;

    mysql_close($dbcon);
}

function querydb($query)
{
	$result = mysql_query($query);
	if(!$result)
	{
		echo "<br><font color=green> $query </font> <br><br>\n";
		echo "<font color=red>A QUERY error occurred:</font> ".
			"<font color=blue>".mysql_error()."</font><p>\n";
	}
	return $result;
}

function mysql_field_is_null($result, $row, $field)
{
	if(mysql_result($result, $row, $field) == null)
		return 1;
	return 0;
}


function read_string($filename)
{
    return join("", file($filename));
}


function build_urlarg($vars)
{
	$arr = array();
	while(list($key, $val) = each($vars))
	    {
		if(is_array($val))
		    {
			while(list($idx, $value) = each($val))
			    {
				//echo "Encoding $key / $value<br>";
				$arr[] = rawurlencode($key."[]")."=".rawurlencode($value);
			    }
		    }
		else
		    $arr[] = $key."=".rawurlencode($val);
	    }
	return implode("&", $arr);
}


function add_option_menu($options, $label, $id)
{
	echo "<form action='project.php' method='get'>\n";
	echo "<select name='prj_id'>\n";
	while(list($idx, $val) = each($options))
		     echo "<option>$val</option>\n";
	echo "</select>\n";
	echo "<input type='submit' value='$label'>\n";
	echo "</form> <br>\n";
}


/*
 * return all keys of a mapping as an array
 */
function keys($arr)
{
    $res = array();
    while(list($k, $v) = each($arr))
        $res[] = $k;
    return $res;
}

/*
 * return all values of a mapping as an array
 */
function values($arr)
{
    $res = array();
    while(list($k, $v) = each($arr))
        $res[] = $v;
    return $res;
}


/*
 * format date
 */
function makedate($time)
{
    return date("F d, Y  H:i:s", $time);
}


function get_remote()
{
    global $REMOTE_HOST, $REMOTE_ADDR;

    if($REMOTE_HOST)
	$ip = $REMOTE_HOST;
    else
	$ip = $REMOTE_ADDR;

    return $ip;
}

function htmlify_urls($text)
{
    //FIXME: wonder what the syntax is, this doesn't seem to work
    //    $text = strip_tags($text, "<a>,<b>,<i>,<ul>,<li>");

    // html-ify urls
    $urlreg = "([a-zA-Z]+://([^\t\r\n ]+))";
    $text = ereg_replace($urlreg, "<a href=\"\\1\"> \\2 </a>", $text);

    $emailreg = "([a-zA-Z0-9_%+.-]+@[^\t\r\n ]+)";
    $text = ereg_replace($emailreg, " <a href='mailto:\\1'>\\1</a>", $text);

    $text = str_replace("\n", "<br>", $text);

    return $text;
}

// open file and display contents of selected tag
function get_xml_tag ($file, $mode = null)
{
    if ($mode and file_exists($file))
    {
        $fp = @fopen($file, "r");
	$data = fread($fp, filesize($file));
	@fclose($fp);
	if (eregi("<" . $mode . ">(.*)</" . $mode . ">", $data, $out))
	{
	    return $out[1];
	}
    }
    else
    {
        return null;
    }
}

/* get the number of applications in the appQueue table */
function getQueuedAppCount()
{
    $qstring = "SELECT count(*) as queued_apps FROM appQueue";
    $result = mysql_query($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_apps;
}

/* get the number of applications in the appQueue table */
function getQueuedMaintainerCount()
{
    $qstring = "SELECT count(*) as queued_maintainers FROM appMaintainerQueue";
    $result = mysql_query($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_maintainers;
}

?>
