<?

/* Code to view all kinds of interesting statistics about appdb */

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");
require(BASE."include/"."maintainer.php");

apidb_header("Appdb Statistics");
echo html_frame_start("","60%","",0);
echo "<table width='100%' border=1 cellpadding=3 cellspacing=0>\n\n";

/* Display the number of users */
echo "<tr class=color4>\n";
echo "    <td>Users:</td>\n";
echo "    <td>".getNumberOfUsers()."</td>\n";
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

/* Display the number of images */
echo "<tr class=color4>\n";
echo "    <td>Images:</td>\n";
echo "    <td>".getNumberOfImages()."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 30 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 30 days:</td>\n";
echo "    <td>".getActiveUsersWithinDays(30)."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 60 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 60 days:</td>\n";
echo "    <td>".getActiveUsersWithinDays(60)."</td>\n";
echo "</tr>\n\n";

/* Display the active users in the last 90 days */
echo "<tr class=color4>\n";
echo "    <td>Users active within the last 90 days:</td>\n";
echo "    <td>".getActiveUsersWithinDays(90)."</td>\n";
echo "</tr>\n\n";

echo "</table>\n\n";
echo html_frame_end("&nbsp;");

echo "</form>";
apidb_footer();


?>
