<?php

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage();
    exit;
}
else
{
    global $admin_mode;
    $admin_mode = 1;
}

$vendorId = $_REQUEST['vendorId'];
apidb_header("Edit Vendor Information");

$t = new TableVE("edit");


if($_POST)
{
    $t->update($_POST);
}
else
{
    $table = "vendor";
    $query = "SELECT * FROM $table WHERE vendorId = $vendorId";

    if(debugging())
	echo "$query <br><br>\n";

    $t->edit($query);
}

apidb_footer();

?>
