<?php
/***********/
/* SideBar */
/***********/
require_once(BASE."include/distribution.php");
require_once(BASE."include/vendor.php");
require_once(BASE."include/util.php");
  
function global_sidebar_menu()
{
    global $aClean;

    $g = new htmlmenu(APPDB_OWNER." Menu");
    $g->add(APPDB_OWNER, APPDB_OWNER_URL);
    $g->add("AppDB", BASE);
    $g->add("Bugzilla", BUGZILLA_ROOT);
    $g->add("Wine Wiki", "http://wiki.winehq.org");
    $g->done();

    $g = new htmlmenu("AppDB");
    $g->add("AppDB Home", BASE);
    $g->add("Screenshots", BASE."viewScreenshots.php");
    $g->add("Browse Apps", BASE."appbrowse.php");
    $g->add("Browse Newest Apps", BASE."browse_newest_apps.php");
    $g->add("Downloadable Apps", BASE."browse_downloadable.php");
    $g->add("Browse Apps by Rating", BASE."browse_by_rating.php");
    $g->add("Top 25", BASE."votestats.php");
    $g->add("Submit Application", BASE."appsubmit.php?sSub=view&sAppType=application");
    $g->add("Help &amp; Documentation", BASE."help/");
    $g->add("AppDB Stats", BASE."appdbStats.php");
    $g->add("View Distributions (".distribution::objectGetEntriesCount(false).")", BASE."objectManager.php?sClass=distribution&bIsQueue=false&sTitle=View%20Distributions");
    $g->add("View Vendors (".getNumberOfvendors().")", BASE."objectManager.php?sClass=vendor&bIsQueue=false&sTitle=View%20Vendors");
    $g->add("Email your suggestions for improving the AppDB", "mailto:appdb@winehq.org");
    $g->done();    

    $g = new htmlmenu("Search");
    $g->addmisc(app_search_box(!empty($aClean['sSearchQuery']) ? $aClean['sSearchQuery'] : ''));
    $g->done();

}


function app_search_box($q = '')
{
   $str =  "</span><form method=\"get\" action=\"".BASE."search.php\">\n";
   $str .= "<input type=text name=sSearchQuery value='$q' size=11 class=searchfield>";
   $str .= "<input type=submit value='Search' class=searchbutton>\n";
   $str .= "</form>\n<span>";
   return $str;
}

?>
