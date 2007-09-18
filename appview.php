<?php
/**
 * Displays an application or a version.
 *
 * Mandatory parameters:
 *  - iAppId, application identifier
 *               OR
 *  - iVersionId, version identifier
 *
 * Optional parameters:
 *  - sSub, action to perform ("delete", "unqueue", "Submit a new bug link.")
 *  - iBuglinkId, bug identifier to link a bug with a version
 *
 * TODO:
 *  - replace sSub with iAction and replace "delete", "unqueue", etc. with integer constants DELETE, UNQUEUE, etc.
 *  - move and rename display_bundle into its respective modules
 *  - replace require_once with require after checking that it won't break anything
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/application.php");
require_once(BASE."include/appdb.php");
require_once(BASE."include/vote.php");
require_once(BASE."include/category.php");
require_once(BASE."include/maintainer.php");


/**
 * Displays the SUB apps that belong to this application.
 */
function display_bundle($iAppId)
{
    $oApp = new Application($iAppId);
    $hResult = query_parameters("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
                            "WHERE appFamily.queued='false' AND bundleId = '?' AND appBundle.appId = appFamily.appId",
                            $iAppId);
    if(!$hResult || query_num_rows($hResult) == 0)
    {
         return; // do nothing
    }

    echo html_frame_start("","98%","",0);
    echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"1\">\n\n";

    echo "<tr class=\"color4\">\n";
    echo "    <td>Application Name</td>\n";
    echo "    <td>Description</td>\n";
    echo "</tr>\n\n";

    $c = 0;
    while($ob = query_fetch_object($hResult))
    {
        $oApp = new application($ob->appId);
        //set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //display row
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td>".$oApp->objectMakeLink()."</td>\n";
        echo "    <td>".util_trim_description($oApp->sDescription)."</td>\n";
        echo "</tr>\n\n";

        $c++;
    }

    echo "</table>\n\n";
    echo html_frame_end();
}

/**
 * We want to see an application family (=no version).
 */
if( isset($aClean['iAppId']) )
{
    $oApp = new Application($aClean['iAppId']);
    $oApp->display();
} else if( isset($aClean['iVersionId']) ) // We want to see a particular version.
{
    $oVersion = new Version($aClean['iVersionId']);

    // header
    apidb_header("Viewing App: ".version::fullName($oVersion->iVersionId));
    $oVersion->display($aClean);
} else
{
    // Oops! Called with no params, bad llamah!
    util_show_error_page_and_exit('Page Called with No Params!');
}

apidb_footer();
?>
