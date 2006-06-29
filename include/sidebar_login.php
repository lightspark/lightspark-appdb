<?php
/*****************/
/* Login SideBar */
/*****************/

require_once(BASE."include/maintainer.php");
require_once(BASE."include/application.php");
require_once(BASE."include/user.php");

function global_sidebar_login() {
  
    $g = new htmlmenu("User Menu");
    
    if($_SESSION['current']->isLoggedIn())
    {

        $g->add("Logout", BASE."account.php?cmd=logout");
        $g->add("Preferences", BASE."preferences.php");
        
        /* if this user maintains any applications list them */
        /* in their sidebar */
        $apps_user_maintains = getAppsFromUserId($_SESSION['current']->iUserId);
        if($apps_user_maintains)
        {
            $g->addmisc("");
            $g->addmisc("You maintain:\n");
            while(list($index, list($appId, $versionId, $superMaintainer)) = each($apps_user_maintains))
            {
                if($superMaintainer)
                    $g->addmisc("<a href='".BASE."appview.php?appId=$appId'>".Application::lookup_name($appId)."*</a>", "center");
                else
                    $g->addmisc("<a href='".BASE."appview.php?versionId=$versionId'>".Application::lookup_name($appId)." ".Version::lookup_name($versionId)."</a>", "center");
            }
        }
        $appsRejected = $_SESSION['current']->getAllRejectedApps();
        if($appsRejected)
            $g->addmisc("<a href='".BASE."appsubmit.php?'>Review Rejected Apps</a>", "center");

    }
    else
    {
        $g->add("Login", BASE."account.php?cmd=login");
    }
    
    $g->done();   

}
?>
