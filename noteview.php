<?php
/**************************************/
/* Application Database - Note Viewer */
/**************************************/

include("path.php");
require(BASE."include/"."incl.php");

function admin_menu()
{
    global $noteId;

    $m = new htmlmenu("Admin");
    $m->add("Edit this Note", BASE."admin/editAppNote.php?noteId=$noteId");
    $m->done();
}

//do query
$result = mysql_query("SELECT noteTitle, noteDesc FROM appNotes WHERE noteId = $noteId");
$ob = mysql_fetch_object($result);

//die if error
if(!$result || mysql_num_rows($result) == 0)
{
    // error
    errorpage("No Note Found","The selected note was not found.");
    exit;
}

//display admin menu
if(loggedin() && (havepriv("admin") || $_SESSION['current']->ownsApp($appId))) {
    apidb_sidebar_add("admin_menu");
}

//show page
apidb_header();

echo html_frame_start("View Note - $ob->noteTitle ","80%");

echo add_br(stripslashes($ob->noteDesc));

echo html_frame_end();

if ($versionId)
{
    echo html_back_link(1,"appview.php?appId=$appId&versionId=$versionId");
}
else
{
    echo html_back_link(1,"appview.php?appId=$appId");
}

apidb_footer();

?>
