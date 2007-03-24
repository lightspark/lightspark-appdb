<?php
/****************************************************************/
/* Code to view all kinds of interesting statistics about appdb */
/****************************************************************/

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/user.php");

apidb_header("Appdb Statistics");
echo html_frame_start("","60%","",0);
echo "<table width='100%' border=1 cellpadding=3 cellspacing=0>\n\n";

/* Display the number of users */
echo "<tr class=color4>\n";
echo "    <td>Users:</td>\n";
echo "    <td>".User::count()."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 30 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 30 days:</td>\n";
echo "    <td>".User::active_users_within_days(30)."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 60 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 60 days:</td>\n";
echo "    <td>".User::active_users_within_days(60)."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 90 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 90 days:</td>\n";
echo "    <td>".User::active_users_within_days(90)."</td>\n";
echo "</tr>\n\n";

/* Display the inactive users */
echo "<tr class=color4>\n";
echo "    <td>Inactive users (not logged in since six months):</td>\n";
echo "    <td>".(User::count()-User::active_users_within_days(183))."</td>\n";
echo "</tr>\n\n";

/* Display the users who were warned and pending deletion */
echo "<tr class=color4>\n";
echo "    <td>Inactive users pending deletion:</td>\n";
echo "    <td>".User::get_inactive_users_pending_deletion()."</td>\n";
echo "</tr>\n\n";

/* Display the number of comments */
echo "<tr class=color4>\n";
echo "    <td>Comments:</td>\n";
echo "    <td>".getNumberOfComments()."</td>\n";
echo "</tr>\n\n";

/* Display the number of application familes */
echo "<tr class=color4>\n";
echo "    <td>Application families:</td>\n";
echo "    <td>".getNumberOfAppFamilies()."</td>\n";
echo "</tr>\n\n";

/* Display the number of versions */
echo "<tr class=color4>\n";
echo "    <td>Versions:</td>\n";
echo "    <td>".getNumberOfVersions()."</td>\n";
echo "</tr>\n\n";

/* Display the number of application maintainers */
echo "<tr class=color4>\n";
echo "    <td>Application maintainers:</td>\n";
echo "    <td>".Maintainer::getNumberOfMaintainers()."</td>\n";
echo "</tr>\n\n";

/* Display the number of images */
echo "<tr class=color4>\n";
echo "    <td>Screenshots:</td>\n";
echo "    <td>".appData::objectGetEntriesCount("false", false, "screenshot")."</td>\n";
echo "</tr>\n\n";
	
echo "</table>\n\n";
echo html_frame_end("&nbsp;");
apidb_footer();
?>
