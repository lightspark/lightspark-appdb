<?php
/*************************************************/
/* Main Include Library for Application Database */
/*************************************************/

// set global path
$apidb_root = BASE;

//get modules
require(BASE."include/"."config.php");
require(BASE."include/"."util.php");
require(BASE."include/"."user.php");
require(BASE."include/"."session.php");
require(BASE."include/"."menu.php");
require(BASE."include/"."html.php");

// create arrays
$sidebar_func_list = array();
$help_list = array();

function apidb_help_add($desc, $id)
{
    global $help_list;
    $help_list[] = array($desc, $id);
}


//return url with docroot prepended 
//
function apidb_url($path)
{
    global $apidb_root;
    return $apidb_root.$path;
}

//return FULL url with docroot prepended
function apidb_fullurl($path = "")
{
    global $apidb_root;
    return $apidb_root.$path;
}

function apidb_fullpath($path)
{
    global $apidb_root;
    global $DOCUMENT_ROOT;
    return $DOCUMENT_ROOT.$apidb_root.$path;
}


/*
 * output the common apidb header
 */
function apidb_header($title = 0)
{
    global $apidb_root;

    $username = isset($_SESSION['current'])?$_SESSION['current']->username:"";

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
    global $apidb_root;

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
    global $apidb_root;
    global $sidebar_func_list;

    //TURN on GLOBAL ADMIN MENU
    if (havepriv("admin"))
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
    global $apidb_root;
    return $apidb_root."images/$name";
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
 * redirect back to referer, or else to the main page
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
    global $PHPSESSID;

    if($color)
        $text = "<font color='$color'> $text </font>\n";

    $text = str_replace("'", "\\'", $text);
    mysql_query("INSERT INTO sessionMessages VALUES (null, null, '$PHPSESSID', '$text')");
    echo mysql_error();
}



/**
 * output msg_buffer and clear it.
 */
function dumpmsgbuffer()
{
    global $PHPSESSID;
    
    $result = mysql_query("SELECT * FROM sessionMessages WHERE sessionId = '$PHPSESSID'");
    if(!$result)
        return;

    while($r = mysql_fetch_object($result))
    {
        echo html_frame_start("","300","",5);
        echo "<div align=center> $r->message </div>";
        echo html_frame_end("&nbsp;");
        echo "<br>\n";
    }

    mysql_query("DELETE FROM sessionMessages WHERE sessionId = '$PHPSESSID'");
}

/**
 * Statics
 */
define("APPDB_ROOT", "http://appdb.winehq.org/");
define("STANDARD_NOTIFY_FOOTER","------- You are receiving this mail because: -------\n".
"You are an maintainer of this app or an appdb administrator\n".
"to change your preferences go to: ".APPDB_ROOT."preferences.php\n");

/*
 * Start DB Connection
 */
opendb();

/*
 * Init Session (stores user info and cart info in session)
 */
$session = new session("whq_appdb");
$session->register("current");

// if we are debugging we need to see all errors
if(debugging()) error_reporting(E_ALL ^ E_NOTICE);
?>
