<?

/*=========================================================================
 *
 * view comments
 *
 * script expects appId, versionId and threadId as argument
 *
 */

include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."comments.php");

apidb_header("Comments");

view_app_comments($appId, $versionId, $threadId);

apidb_footer();

?>
