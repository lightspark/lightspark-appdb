<?php
/*****************/
/* Login SideBar */
/*****************/

require_once(BASE."include/maintainer.php");
require_once(BASE."include/application.php");
require_once(BASE."include/user.php");
require_once(BASE."include/monitor.php");

function global_sidebar_login() {
  
    $g = new htmlmenu("User Menu");
    
    if($_SESSION['current']->isLoggedIn())
    {

        $g->add("Logout", BASE."account.php?sCmd=logout");
        $g->add("Preferences", BASE."preferences.php");
        
        /* if this user maintains any applications list them */
        /* in their sidebar */
        $apps_user_maintains = Maintainer::getAppsMaintained($_SESSION['current']);
        if($apps_user_maintains)
        {
            $g->addmisc("");
            $g->addmisc("You maintain:\n");
            while(list($index, list($appId, $versionId, $superMaintainer)) = each($apps_user_maintains))
            {
                $oApp = new application($appId);
                if($superMaintainer)
                    $g->addmisc($oApp->objectMakeLink()."*", "center");
                else
                    $g->addmisc(version::fullNameLink($versionId), "center");
            }
        }
        $iAppsRejected = application::objectGetEntriesCount(true, true) +
                         version::objectGetEntriesCount(true, true);
        if($iAppsRejected && !$_SESSION['current']->hasPriv("admin"))
        {
          $g->add("Review Rejected Apps ($iAppsRejected)", BASE."appsubmit.php", "center");
        }

        $aMonitored = Monitor::getVersionsMonitored($_SESSION['current']);
        if($aMonitored)
        {
            $g->addmisc("");
            $g->addmisc("You monitor:\n");

            while(list($i, list($iAppId, $iVersionId)) = each($aMonitored))
            {
              $oVersion = new version($iVersionId);
              $g->add(version::fullName($iVersionId), $oVersion->objectMakeUrl(), "center");
            }
        }

        /* Display a link to the user's queued items,
           but not for admins, as theirs are auto-accepted */
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            $g->addmisc("");
            $g->add("Your queued items", BASE."queueditems.php");
        }

    } else
    {
        $g->add("Log in", BASE."account.php?sCmd=login");
        $g->add("Register", BASE."account.php?sCmd=new");
    }

    $g->done();

}
?>
