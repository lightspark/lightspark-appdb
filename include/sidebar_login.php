<?

require_once(BASE."include/"."maintainer.php");
require_once(BASE."include/"."category.php");

/*
 * Login SideBar
 *
 */
  
function global_sidebar_login() {
  
    global $apidb_root;

    $g = new htmlmenu("User Menu");
    
    if(loggedin())
    {
        global $current;

        $g->add("Logout", $apidb_root."account.php?cmd=logout");
        $g->add("Preferences", $apidb_root."preferences.php");
        
        /* if this user maintains any applications list them */
        /* in their sidebar */
        $apps_user_maintains = getAppsFromUserId($current->userid);
        if($apps_user_maintains)
        {
            $g->addmisc("");
            $g->addmisc("You maintain:\n");
            while(list($index, list($appId, $versionId, $superMaintainer)) = each($apps_user_maintains))
            {
                if($superMaintainer)
                    $g->addmisc("<a href='".$apidb_root."appview.php?appId=$appId'>".appIdToName($appId)."*</a>", "center");
                else
                    $g->addmisc("<a href='".$apidb_root."appview.php?appId=$appId&versionId=$versionId'>".appIdToName($appId)." ".versionIdToName($versionId)."</a>", "center");
            }
        }
    }
    else
    {
        $g->add("Login", $apidb_root."account.php?cmd=login");
    }
    
    $g->done();   

}

?>
