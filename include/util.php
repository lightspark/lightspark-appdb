<?php

$dbcon = null;
$dbref = 0;

function opendb()
{
    global $dbcon, $dbref;

    $dbref++;

    if($dbcon)
	return $dbcon;

    $dbcon = mysql_connect(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS);
    if(!$dbcon) 
	{
	    echo "An error occurred: ".mysql_error()."<p>\n";
	    exit;
	}
    mysql_select_db(APPS_DB);
    return $dbcon;
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

/* bugzilla functions */
function make_bugzilla_version_list($varname, $cvalue)
{
    $table = BUGZILLA_DB.".versions";
    $where = "WHERE product_id=".BUGZILLA_PRODUCT_ID;
    $query = "SELECT value FROM $table $where ORDER BY value";

    $result = query_bugzilladb($query);
    if(!$result) return;

    echo "<select name='$varname'>\n";
    echo "<option value=0>Choose ...</option>\n";
    while(list($value) = mysql_fetch_row($result))
    {
        if($value == $cvalue)
            echo "<option value=$value selected>$value\n";
        else
            echo "<option value=$value>$value\n";
    }
    echo "</select>\n";
}

function make_maintainer_rating_list($varname, $cvalue)
{
    
    echo "<select name='$varname'>\n";
    echo "<option value=0>Choose ...</option>\n";
    $aRating = array("Gold", "Silver", "Bronze", "Garbage");
    $iMax = count($aRating);

    for($i=0; $i < $iMax; $i++)
    {
        if($aRating[$i] == $cvalue)
            echo "<option value=$aRating[$i] selected>$aRating[$i]\n";
        else
            echo "<option value=$aRating[$i]>$aRating[$i]\n";
    }
    echo "</select>\n";
}

/* get the number of applications in the appQueue table */
function getQueuedAppCount()
{
    $qstring = "SELECT count(*) as queued_apps FROM appQueue";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_apps;
}

/* get the number of applications in the appQueue table */
function getQueuedAppDataCount()
{
    $qstring = "SELECT count(*) as queued_appdata FROM appDataQueue";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_appdata;
}

/* get the number of applications in the appQueue table */
function getQueuedMaintainerCount()
{
    $qstring = "SELECT count(*) as queued_maintainers FROM appMaintainerQueue";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->queued_maintainers;
}

/* get the total number of maintainers and applications in the appMaintainers table */
function getMaintainerCount()
{
    $qstring = "SELECT count(*) as maintainers FROM appMaintainers";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->maintainers;
}

/* get the total number of vendors from the vendor table */
function getVendorCount()
{
    $qstring = "SELECT count(*) as vendors FROM vendor";
    $result = query_appdb($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->vendors;
}

/* Get the number of users in the database */
function getNumberOfComments()
{
    $result = query_appdb("SELECT count(*) as num_comments FROM appComments;");
    $row = mysql_fetch_object($result);
    return $row->num_comments;
}

/* Get the number of versions in the database */
function getNumberOfVersions()
{
    $result = query_appdb("SELECT count(versionId) as num_versions FROM appVersion WHERE versionName != 'NONAME';");
    $row = mysql_fetch_object($result);
    return $row->num_versions;
}

/* Get the number of maintainers in the database */
function getNumberOfMaintainers()
{
    $result = query_appdb("SELECT count(maintainerId ) as num_maintainers FROM appMaintainers;");
    $row = mysql_fetch_object($result);
    return $row->num_maintainers;
}

/* Get the number of app familes in the database */
function getNumberOfAppFamilies()
{
    $result = query_appdb("SELECT count(*) as num_appfamilies FROM appFamily;");
    $row = mysql_fetch_object($result);
    return $row->num_appfamilies;
}

/* Get the number of images in the database */
function getNumberOfImages()
{
    $result = query_appdb("SELECT count(*) as num_images FROM appData WHERE type='image';");
    $row = mysql_fetch_object($result);
    return $row->num_images;
}

function lookupVendorName($vendorId)
{
    $sResult = query_appdb("SELECT * FROM vendor ".
               "WHERE vendorId = ".$vendorId);
    if(!$sResult || mysql_num_rows($sResult) != 1)
        return "Unknown vendor";

    $vendor = mysql_fetch_object($sResult);
    return $vendor->vendorName;
}

?>
