<?php
/*************************************************/
/* Main Include Library for Application Database */
/*************************************************/

// get modules
ini_set("memory_limit","64M");
require(BASE."include/"."config.php");
require(BASE."include/"."util.php");
require(BASE."include/"."user.php");
require(BASE."include/"."session.php");
require(BASE."include/"."menu.php");
require(BASE."include/"."html.php");
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

function apidb_fullpath($path)
{
    return $_SERVER['DOCUMENT_ROOT'].BASE.$path;
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

    // banner ad
    include(BASE."include/"."banner.php");
    $banner_ad = banner_display();

    // Display Header
    include(BASE."include/"."header.php");

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
        include(BASE."include/"."sidebar_admin.php");
        apidb_sidebar_add("global_admin_menu");
    }

    // Login Menu
    include(BASE."include/"."sidebar_login.php");
    apidb_sidebar_add("global_sidebar_login");

    // Main Menu
    include(BASE."include/"."sidebar.php");
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
    $result = query_appdb("SELECT * FROM sessionMessages WHERE sessionId = '".session_id()."'");
    if(!$result)
        return;

    while($r = mysql_fetch_object($result))
    {
        echo html_frame_start("","300","",5);
        echo "<div align=center> $r->message </div>";
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
