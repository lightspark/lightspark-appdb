<?php
/**************************/
/* FIXME: add description */
/**************************/

/*
 * application evironment
 */ 
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."qclass.php");
require(BASE."include/"."pn_buttons.php");

/*
  arguments to this script:

  $fields[]
  $implementations[]
*/

opendb();


if(loggedin())
{
    if($_SESSION['current']->getpref("query:hide_header") == "yes")
	disable_header();
    if($_SESSION['current']->getpref("query:hide_sidebar") == "yes")
	disable_sidebar();
}


// create $vars object
$vars = $_GET;
$qc = new qclass();
$qc->process($vars);
$query = $qc->get_query();


// set default lines per page
if(!$linesPerPage)
{
    $linesPerPage = 20;
}
$vars["linesPerPage"] = $linesPerPage;


// set default currrent posistion
if(!$curPos)
{
    $curPos = 0;
}
$vars["curPos"] = $curPos;


// Get total count
if($totalCount == 0)
{
    $tempResult = mysql_query($query);
    if(!$tempResult)
	{
	    echo "$query <br>\n";
	    echo "An error occurred: ".mysql_error()."<p>";
	    exit;
	}
    $totalCount = mysql_num_rows($tempResult);
    $vars["totalCount"] = $totalCount;
    mysql_free_result($tempResult);
}

// No data
if($totalCount == 0)
{
    if(debugging())
    {
	echo $query;
	echo "<br><br>";
    }

    echo "Your query returned no data.</body></html>\n";
    return;
}
 
$endPos=$curPos+$linesPerPage;


if($verbose)
{
    // verbose view (edit mode)

    include(BASE."include/"."tableve.php");
    if(!$mode)
	$mode = "view";
    apidb_header(ucfirst($mode)." Query");

    $t = new TableVE($mode);
    $query = str_replace("\\", "", $query);

    $endPos = $curPos + $linesPerPage;
    $query .= " LIMIT $curPos,$endPos";

    if(debugging())
	echo "$query <br><br>\n";

    add_pn_buttons($vars, $endPos);
    echo "<br> curPos: $curPos <br> linesPerPage: $linesPerPage <br> totalCount: $totalCount <br>";
 
    if($mode == "edit")
        $t->edit($query);
    else
        $t->view($query);
  
    add_pn_buttons($vars, $endPos);
  
    apidb_footer();
}
else
{
    // normal view (user view)
    
    apidb_header("Query Results");
    
    include(BASE."include/"."query_inc.php");

    twinedb_query($query, $vars);

    apidb_footer();
}


?>
