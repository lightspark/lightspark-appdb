<?

/*
 * Add Application Note
 *
 */

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");
include(BASE."include/"."qclass.php");

global $apidb_root;

//check for admin privs
if(!loggedin() || (!havepriv("admin") && !$current->ownsApp($appId)) )
{
    errorpage("Insufficient Privileges!");
    exit;
}

apidb_header("Add Application Note");

$t = new TableVE("create");

if($HTTP_POST_VARS)
{
    $t->update($HTTP_POST_VARS);
}
else
{
    $table = "appNotes";

    if (!$versionId) { $versionId = 0; }

    //delete old NONAMES
    mysql_query("DELETE FROM $table WHERE noteTitle = 'NONAME'");

    //show edit form
    $query = "INSERT INTO $table VALUES(0, 'NONAME', '', $appId, $versionId)";

    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $t->create($query, $table, "noteId");
    
    echo html_back_link(1,$apidb_root."appview.php?appId=$appId&versionId=$versionId");
}

apidb_footer();

?>
