<?php

/**
 * A list of the user's queued items
 */

require("path.php");
require(BASE."include/incl.php");
require(BASE."include/filter.php");
require(BASE."include/appData.php");

apidb_header("View Your Queued Items");

$oUser = new User($_SESSION['current']->iUserId);

if(!$oUser->isLoggedIn())
{
    echo "You need to log in to display your queued items.";
    apidb_footer();
}

/* Test data */
if($sResult = testData::listSubmittedBy($oUser->iUserId))
{
    echo html_frame_start("Your Queued Test Results","90%");
    echo $sResult;
    echo html_frame_end("&nbsp;");
}
else
    echo "You have no queued test results.<br /><br />\n";

/* Applications */
if($sResult = Application::listSubmittedBy($oUser->iUserId))
{
    echo html_frame_start("Your Queued Applications","90%");
    echo $sResult;
    echo html_frame_end("&nbsp;");
} else
    echo "You have no queued applications.<br /><br />\n";

/* Versions */
if($sResult = Version::listSubmittedBy($oUser->iUserId))
{
    echo html_frame_start("Your Queued Versions","90%");
    echo $sResult;
    echo html_frame_end("&nbsp;");
} else
    echo "You have no queued versions.<br /><br />\n";

/* Bug links */
if($sResult = bug::listSubmittedBy($oUser->iUserId))
{
    echo html_frame_start("Your Queued Bug Links","90%");
    echo $sResult;
    echo html_frame_end("&nbsp;");
} else
    echo "You have no queued bugs.<br /><br />\n";

/* Application data */
if($sResult = appData::listSubmittedBy($oUser->iUserId))
{
    echo html_frame_start("Your Queued Application Data","90%");
    echo $sResult;
    echo html_frame_end("&nbsp;");
} else
    echo "You have no queued application data.<br /><br />\n";

apidb_footer();

?> 
