<?

/*
 * Login SideBar
 *
 */
  
function global_sidebar_login() {
  
    global $apidb_root;

    $g = new htmlmenu("User Menu");
    
    if(loggedin())
    {
        $g->add("Logout", $apidb_root."account.php?cmd=logout");
        $g->add("Preferences", $apidb_root."preferences.php");
    }
    else
    {
        $g->add("Login", $apidb_root."account.php?cmd=login");
    }
    
    $g->done();   

}

?>
