<?

//
// Admin Script for API Db
// last modified 04-20-01
//

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."tableve.php");

//FIXME: need to check for admin privs
if(!loggedin())
{
    errorpage();
    exit;
}

// desc
function get_tables()
{
    $result = mysql_query("SHOW TABLES");
    $arr = array();
    $arr[] = "ALL";
    while($r = mysql_fetch_array($result))
        {
            $arr[] = $r[0];
        }
    return $arr;
}

// desc
function input_form()
{
    echo "<form method=get action=".apidb_url("admin").">\n";
    echo "</form>\n";
}

//desc
function make_options($name, $options, $label = "Submit")
{
    echo "<select name='$name'>\n";
    while(list($idx, $val) = each($options))
	echo "<option>$val</option>\n";
    echo "</select>\n";
}

//desc
if($table_cmd)
{
    apidb_header("Table Operation");
    $t = new TableVE("view");
    switch($table_cmd)
	{
	case "check":
	    $t->view("CHECK TABLE $table_id");
	    break;
	case "describe":
	    $t->view("DESCRIBE $table_id");
	    break;
	case "optimize":
	    $t->view("OPTIMIZE TABLE $table_id");
	    break;
	}
    apidb_footer();
    exit;
}

// output of admin page begins here
apidb_header("Admin");

// Draw User List
include(BASE."include/"."query_users.php");


apidb_footer();

?>
