<?php
/*************************************************/
/* Main Include Library for Application Database */
/*************************************************/

// get modules
ini_set("memory_limit","64M");
require_once(BASE."include/config.php");
require(BASE."include/util.php");
require(BASE."include/user.php");
require(BASE."include/session.php");
require(BASE."include/menu.php");
require(BASE."include/html.php");
require(BASE."include/error_log.php");
require(BASE."include/query.php");
require(BASE."include/table.php");
require_once(BASE."include/objectManager.php");

/* if magic quotes are enabled make sure the user disables them */
/* otherwise they will see all kinds of odd effects that are difficult */
/* to track down */
if(get_magic_quotes_gpc())
{
    echo "<b>Please disable the magic quotes GPC PHP setting. See <a href=\"http://us2.php.net/manual/en/ref.info.php#ini.magic-quotes-gpc\"> this page</a> for more information</b><br/><br/>";
    echo "AppDB php code assumes magic quotes are disabled.<br/><br/>";
    echo "Magic quotes are a bad idea for a few reasons.<br/><br/>";
    echo "First is that php calls <b>addslashes()</b> on all \$_POST, \$_REQUEST and cookie variables ";
    echo "if magic quotes is enabled. ";
    echo "Ooooooh you say.<br/>";
    echo "<i>\"Aren't magic quotes a convienent way to protect my php code from sql injection attacks?\"</i><br/><br/>";
    echo "No!  <b>addslashes()</b> isn't adequate.  You should use <b>query_escape_string()</b> or some other function";
    echo " that will handle multi-byte characters.  See <a href=\"http://shiflett.org/archive/184\">this article</a>";
    echo " for a way to exploit <b>addslash()</b>ed parameters.<br/><br/>";
    echo "A second reason is that with magic quotes enabled, due to the use of <b>query_escape_string()</b> to";
    echo " protect from sql injection attacks we'll end up with variables that have been addslash()ed and";
    echo " <b>query_escape_string()</b>ed.  So you end up having to call stripslashes() on EVERY variable. ";
    exit;
}

/**
 * rename $_REQUEST variables to preserve backwards compatibility
 * with bugzilla links and urls in emails and on google from before our
 * mass rename of GPC variables to use our coding standard prefixing
 *
 * NOTE: we may be able to remove these backwareds compatibility changes
 *       in a few years, check in mid 2007 to see how many old
 *       links are still poping up in google then
 */
if(isset($_REQUEST['versionId']))
{
   $_REQUEST['iVersionId'] = $_REQUEST['versionId'];
   unset($_REQUEST['versionId']);
}
if(isset($_REQUEST['appId']))
{
   $_REQUEST['iAppId'] = $_REQUEST['appId'];
   unset($_REQUEST['appId']);
}
if(isset($_REQUEST['bug_id']))
{
    $_REQUEST['iBugId'] = $_REQUEST['bug_id'];
    unset($_REQUEST['bug_id']);
}
if(isset($_REQUEST['catId']))
{
    $_REQUEST['iCatId'] = $_REQUEST['catId'];
    unset($_REQUEST['catId']);
}
if(isset($_REQUEST['sub']))
{
    $_REQUEST['sSub'] = $_REQUEST['sub'];
    unset($_REQUEST['sub']);
}
if(isset($_REQUEST['topic']))
{
    $_REQUEST['sTopic'] = $_REQUEST['topic'];
    unset($_REQUEST['topic']);
}
if(isset($_REQUEST['mode']))
{
    $_REQUEST['sMode'] = $_REQUEST['mode'];
    unset($_REQUEST['mode']);
}
/* End backwards compatibility code */

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

    // grab the starting time
    global $sPageGeneratingStartTime;
    $sPageGeneratingStartTime = microtime();
    $aStartarray = explode(" ", $sPageGeneratingStartTime);
    $sPageGeneratingStartTime = $aStartarray[1] + $aStartarray[0]; 

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

    // grab the end of the page generating time
    global $sPageGeneratingStartTime;
    $sPageGeneratingEndTime = microtime();
    $aEndarray = explode(" ", $sPageGeneratingEndTime);
    $sPageGeneratingEndTime = $aEndarray[1] + $aEndarray[0];
    $sTotaltime = $sPageGeneratingEndTime - $sPageGeneratingStartTime;
    $sTotaltime = round($sTotaltime,5);
    echo "<center>Page loaded in <b>$sTotaltime</b> seconds.</center>";

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
function addmsg($shText, $color = "black")
{
    if($color)
        $shText = "<font color='$color'> $shText </font>\n";

    $sQuery = "INSERT INTO sessionMessages VALUES (null, ?, '?', '?')";
    if (!query_parameters($sQuery, "NOW()", session_id(), $shText))
    {
        echo "An error has occurred in addmsg()";
        echo $shText;
    }
}


function purgeSessionMessages()
{
  $sQuery = "truncate sessionMessages";
  query_parameters($sQuery);
}


/**
 * output msg_buffer and clear it.
 */
function dumpmsgbuffer()
{
    $hResult = query_parameters("SELECT * FROM sessionMessages WHERE sessionId = '?'", session_id());
    if(!$hResult)
        return;

    while($oRow = query_fetch_object($hResult))
    {
        echo html_frame_start("","300","",5);
        echo "<div align=center> $oRow->message </div>";
        echo html_frame_end("&nbsp;");
        echo "<br>\n";
    }

    query_parameters("DELETE FROM sessionMessages WHERE sessionId = '?'", session_id());
}

/**
 * Init Session (stores user info in session)
 */
$session = new session("whq_appdb");
$session->register("current");

if(!isset($_SESSION['current']))
{
    $_SESSION['current'] = new User();
}

// if we are debugging we need to see all errors
if($_SESSION['current']->showDebuggingInfos()) error_reporting(E_ALL ^ E_NOTICE);

// include filter.php to filter all REQUEST input
require(BASE."include/filter.php");

?>
