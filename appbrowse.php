<?php
/**********************************/
/* code to BROWSE categories/apps */
/**********************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."appdb.php");
require(BASE."include/"."category.php");


function admin_menu()
{
    if(isset($_REQUEST['catId'])) $catId=$_REQUEST['catId'];
    else $catId="";

    $m = new htmlmenu("Admin");
    $m->add("Edit this Category", BASE."admin/editCategory.php?catId=$catId");
    $url = BASE."admin/deleteAny.php?what=category&catId=$catId&confirmed=yes";
    $m->add("Delete this Category", "javascript:deleteURL(\"Are you sure?\", \"".$url."\")");


    $m->done();
}

if(isset($_REQUEST['catId'])) $catId=$_REQUEST['catId'];
else $catId=0; // ROOT

if( !is_numeric($catId) )
{
	errorpage("Something went wrong with the category ID");
	exit;
}

// list sub categories
$cat = new Category($catId);
$catFullPath = make_cat_path($cat->getCategoryPath());
$subs = $cat->getCategoryList();

//display admin box
if(havepriv("admin") && $catId != 0)
    apidb_sidebar_add("admin_menu");

//output header
apidb_header("Browse Applications");

if($subs)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $catFullPath ."</b><br>\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";
    
    echo "<tr class=color4>\n";
    echo "    <td><font color=white>Sub Category</font></td>\n";
    echo "    <td><font color=white>Description</font></td>\n";
    echo "    <td><font color=white>No. Apps</font></td>\n";
    echo "</tr>\n\n";
    
    $c = 0;
    while(list($id, list($name, $desc)) = each($subs))
    {
	//set row color
	$bgcolor = ($c % 2 == 0) ? "color0" : "color1"; 
	
	//get number of apps
        $appcount = $cat->getAppCount($id);

	//format desc
	$desc = substr(stripslashes($desc),0,70);

	//display row
	echo "<tr class=$bgcolor>\n";
	echo "    <td><a href='appbrowse.php?catId=$id'>".stripslashes($name)."</a></td>\n";
	echo "    <td>$desc &nbsp;</td>\n";
	echo "    <td>$appcount &nbsp;</td>\n";
	echo "</tr>\n\n";
			    
	$c++;
    }
    
    echo "</table>\n\n";
    echo html_frame_end("$c categories");
}



// list applications in this category
$apps = $cat->getAppList($catId);
if($apps)
{
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $catFullPath ."</b><br>\n";
    echo html_frame_end();
    
    echo html_frame_start("","98%","",0);
    echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";
    
    echo "<tr class=color4>\n";
    echo "    <td><font color=white>Application Name</font></td>\n";
    echo "    <td><font color=white>Description</font></td>\n";
    echo "    <td><font color=white>No. Versions</font></td>\n";
    echo "</tr>\n\n";
	    
    $c = 0;
    while(list($id, list($name, $desc)) = each($apps))
    {
	//skip if a NONAME
	if ($ob->appName == "NONAME") { continue; }    
    
	//set row color
	$bgcolor = ($c % 2 == 0) ? "color0" : "color1";
        
        //get number of versions
	$query = mysql_query("SELECT count(*) as versions FROM appVersion WHERE appId = $id AND versionName != 'NONAME'");
	$ob = mysql_fetch_object($query);
        
	//format desc
	$desc = substr(stripslashes($desc),0,70);
	
	//display row
	echo "<tr class=$bgcolor>\n";
	echo "    <td><a href='appview.php?appId=$id'>".stripslashes($name)."</a></td>\n";
	echo "    <td>$desc &nbsp;</td>\n";
	echo "    <td>$ob->versions &nbsp;</td>\n";
	echo "</tr>\n\n";
			
	$c++;
    }
    
    echo "</table>\n\n";
    echo html_frame_end("$c applications in this category");
}

// Disabled for now
//if ($catId != 0)
//{
//	log_category_visit($cat->id);
//}

echo p();

apidb_footer();

?>
