<?php
/*********************************************/
/* Application Database Documentation Center */
/*********************************************/

include("path.php");
include(BASE."include/"."incl.php");

$help_path = $apidb_root."/help";

if($topic)
{
    display_help($topic);
} else {
    display_index();    
}

function display_index ()
{
    global $help_path;
    global $apidb_root;

    apidb_header("Documentation Index");

    echo "<p><b>Providing all the help you need 24x7</b><p><hr noshade>\n";

    echo "<ul>\n";

    // read dir
    $files = array();
    $d = opendir($help_path);
    while($entry = readdir($d))
    {
    	array_push($files, $entry);
    }
    closedir($d);
    
    //sort dir
    sort($files);
    
    // display dir
    while (list($key,$file) = each($files))
    {
        if(!ereg("(.+)\\.help$", $file, $arr))
	    continue;
	
	$id    = $arr[1];
	$title = get_help_title("$help_path/$file");
	
	echo "  <li> <a href='".$apidb_root."help?topic=$id'> $title </a> </li><p>\n";
    }

    echo "</ul><hr noshade>\n";

    echo "<p>Need more help? Contact us at <a href='mailto:appdb@winehq.org'>appdb@winehq.org</a><p>\n";
        
    apidb_footer();
}

function display_help ($topic)
{
    global $help_path;

    $file  = "$help_path/$topic.help";
    $title = get_help_title($file);

    if(! $title) {
	$title = "Help on $topic";
    }
    
    apidb_header($title);
    if(file_exists($file)) {
        include($file);  
    } else {
        echo "<p><b> No help available on that topic </b><p>\n";
    }
        
    apidb_footer();
}

function get_help_title ($file)
{
    $fp = @fopen($file, "r");
    if(!$fp)
	return null;

    $line = fgets($fp, 1024);
    if(!$line)
	return null;

    $line = trim($line);

    if(eregi("^<!--TITLE: (.+)-->$", $line, $arr))
    {
	return $arr[1];
    }
    return "Internal Error: missing title";
}

?>
