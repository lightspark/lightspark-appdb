<?php
/***********/
/* SideBar */
/***********/
  
function global_sidebar_menu() {
  
    $g = new htmlmenu(APPDB_OWNER." Menu");
    $g->add("Back to ".APPDB_OWNER, APPDB_OWNER_URL);
    $g->done();

    $g = new htmlmenu("App DB");
    $g->add("AppDB Home", BASE);
    $g->add("Browse Apps", BASE."appbrowse.php");
    $g->add("Top 25", BASE."votestats.php");
    $g->add("Submit App", BASE."appsubmit.php");
    $g->add("Documentation", BASE."help/");
    $g->add("Help & Support", BASE."support.php");
    $g->add("Appdb Stats", BASE."appdbStats.php");
    $g->done();    

    $g = new htmlmenu("Search");
    $g->addmisc(app_search_box($_REQUEST['q']));
    $g->done();

}


function app_search_box($q = '')
{
   $str =  "</span><form method=\"get\" action=\"".BASE."search.php\">\n";
   $str .= "<input type=text name=q value='$q' size=8 class=searchfield>";
   $str .= "<input type=submit value='Search' class=searchbutton>\n";
   $str .= "</form>\n<span>";
   return $str;
}

?>
