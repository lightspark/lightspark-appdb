<?php
/*************************************************/
/* Main Include Library for Application Database */
/*************************************************/

// get modules
ini_set("memory_limit","64M");
require(BASE."include/config.php");
require(BASE."include/util.php");
require(BASE."include/user.php");
require(BASE."include/session.php");
require(BASE."include/menu.php");
require(BASE."include/html.php");
require(BASE."include/db.php");

// create arrays
$sidebar_func_list = array();
$help_list = array();

function apidb_help_add($desc, $id)
{
    global $help_list;
    $help_list[] = array($desc, $id);
}


// return url with docroot prepended 
function apidb_url($path)
{
    return BASE.$path;
}

// return FULL url with docroot prepended
function apidb_fullurl($path = "")
{
    return BASE.$path;
}

function appdb_fullpath($path)
{
    /* IE: we know this file is in /yyy/xxx/include, we want to get the /yyy/xxx 
    /* so we call dirname  on this file path twice */
    $fullpath = dirname(dirname(__FILE__))."//".$path;
    /* get rid of potential double slashes due to string concat */
    return str_replace("//", "/", $fullpath); 
}


/*
 * output the common apidb header
 */
function apidb_header($title = 0)
{
    $realname = $_SESSION['current']->sRealname;

    // Set Page Title
    $page_title = $title;
    if ($title)
         $title = " - $title";

    // Display Header
    include(BASE."include/header.php");

    // Display Sidebar
    echo "<table width='100%' border=0 cellspacing=0 cellpadding=0>\n";
    echo "<tr valign='top'>\n";
    echo "<td width=150>\n";
    apidb_sidebar();
    echo "</td>\n";
    echo "<td width='100%'>\n";

    echo html_frame_start($page_title, '100%');

    // Display Status Messages
    dumpmsgbuffer();
}


/*
 * output the common apidb footer
 */
function apidb_footer()
{
    echo html_frame_end();

    //Close Sidebar and Content Well
    echo "<br></td></tr></table>\n";
    
    // Display Footer
    if(!isset($header_disabled))
    include(BASE."include/"."footer.php");
}

/*
 * output the sidebar, calls all functions registered with apidb_sidebar_add
 */
function apidb_sidebar()
{
    global $sidebar_func_list;

    //TURN on GLOBAL ADMIN MENU
    if ($_SESSION['current']->hasPriv("admin"))
    {
        include(BASE."include/sidebar_admin.php");
        apidb_sidebar_add("global_admin_menu");
    } else if($_SESSION['current']->isMaintainer()) /* if the user maintains anything, add their menus */
    {
        include(BASE."include/sidebar_maintainer_admin.php");
        apidb_sidebar_add("global_maintainer_admin_menu");
    }

    // Login Menu
    include(BASE."include/sidebar_login.php");
    apidb_sidebar_add("global_sidebar_login");

    // Main Menu
    include(BASE."include/sidebar.php");
    apidb_sidebar_add("global_sidebar_menu");

    //LOOP and display menus
    for($i = 0; $i < sizeof($sidebar_func_list); $i++)
    {
        $func = $sidebar_func_list[$i];
        $func();
    }
}


/**
 * register a sidebar menu function
 * the supplied function is called when the sidebar is built
 */
function apidb_sidebar_add($funcname)
{
    global $sidebar_func_list;
    array_unshift($sidebar_func_list, $funcname);
}


function apidb_image($name)
{
    return BASE."images/$name";
}


/**
 * display an error page
 */
function errorpage($text = null, $message = null)
{
    if (!$text) {
        $text = "You must be logged in to perform that operation.";
    }
    header("HTTP/1.0 404 Object not found or user is not logged in");
    apidb_header("Oops");
    echo "<div align=center><font color=red><b>$text</b></font></div>\n";
    echo "<p>$message</p>\n";
    apidb_footer();
}



/**
 * redirect to $url
 */
function redirect($url)
{
    header("Location: ".$url); 
    exit;
}

/**
 * redirect back to referrer, or else to the main page
 */
function redirectref($url = null)
{
    if(!$url)
        $url = $_SERVER['HTTP_REFERER'];
    if(!$url)
        $url = apidb_fullurl();
    redirect($url);
}

/**
 * format a date as required for HTTP by RFC 2068 sec 3.3.1 
 */
function fHttpDate($iDate) {
   return gmdate("D, d M Y H:i:s",$iDate)." GMT";
}

/**
 *  parse all the date formats required by HTTP 1.1 into PHP time values
 */
function pHttpDate($sDate) {
   $iDate = strtotime($sDate);
   if ($iDate != -1) return $iDate;
		/* the RFC also requires asctime() format... */
   $aTs = strptime($sDate,"%a %b  %e %H:%M:%S %Y");
   $iDate = gmmktime($aTs[2],$aTs[1],$aTs[0],$aTs[4],$aTs[3],$aTs[5],0);
   return $iDate;
}

/**
 * msgs will be displayed on the Next page view of the same user
 */
function addmsg($text, $color = "black")
{
    global $hAppdbLink;

    if($color)
        $text = "<font color='$color'> $text </font>\n";

    $text = addslashes($text);
    $sQuery = "INSERT INTO sessionMessages VALUES (null, null, '".session_id()."', '$text')";
    if (!mysql_query($sQuery,$hAppdbLink))
    {
        echo "An error has occurred in addmsg(): ".mysql_error($hAppdbLink);
        echo $text;
    }
}



/**
 * output msg_buffer and clear it.
 */
function dumpmsgbuffer()
{
    $hResult = query_appdb("SELECT * FROM sessionMessages WHERE sessionId = '".session_id()."'");
    if(!$hResult)
        return;

    while($oRow = mysql_fetch_object($hResult))
    {
        echo html_frame_start("","300","",5);
        echo "<div align=center> $oRow->message </div>";
        echo html_frame_end("&nbsp;");
        echo "<br>\n";
    }

    query_appdb("DELETE FROM sessionMessages WHERE sessionId = '".session_id()."'");
}

/**
 * Init Session (stores user info in session)
 */
$session = new session("whq_appdb");
$session->register("current");

if(!isset($_SESSION['current'])) $_SESSION['current'] = new User();

// if we are debugging we need to see all errors
if($_SESSION['current']->showDebuggingInfos()) error_reporting(E_ALL ^ E_NOTICE);
?>
