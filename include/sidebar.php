<?php
/***********/
/* SideBar */
/***********/
  
function global_sidebar_menu() {
  
    global $apidb_root, $q;

    $g = new htmlmenu("WineHQ Menu");
    $g->add("Back to WineHQ", "http://www.winehq.org/");
    $g->done();

    $g = new htmlmenu("App DB");
    $g->add("AppDB Home", $apidb_root);
    $g->add("Browse Apps", $apidb_root."appbrowse.php");
    $g->add("Top 25", $apidb_root."votestats.php");
    $g->add("Submit App", $apidb_root."appsubmit.php");
    $g->add("Documentation", $apidb_root."help/");
    $g->add("Help & Support", $apidb_root."support.php");
    $g->add("Appdb Stats", $apidb_root."appdbStats.php");
    $g->done();    

    $g = new htmlmenu("Search");
    $g->addmisc(app_search_box($q));
    $g->done();

}


function app_search_box($q = '')
{
   global $apidb_root;
   $str =  "</span><form method=\"get\" action=\"".$apidb_root."search.php\">\n";
   $str .= "<input type=text name=q value='$q' size=8 class=searchfield>";
   $str .= "<input type=submit value='Search' class=searchbutton>\n";
   $str .= "</form>\n<span>";
   return $str;
}

?>
