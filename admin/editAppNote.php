<?

/*
 * Edit AppNote
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

apidb_header("Edit Application Note");

$t = new TableVE("edit");

if($HTTP_POST_VARS)
{
    // commit changes of form to database
    $t->update($HTTP_POST_VARS);
}
else
{
    // show form
    $table = "appNotes";
    $query = "SELECT * FROM $table WHERE noteId = $noteId";

    if(debugging()) { echo "<p align=center><b>query:</b> $query </p>"; }

    $t->edit($query);

    echo html_back_link(1,$apidb_root."noteview.php?noteId=$noteId");

}

apidb_footer();

?>
