<?php
/*********************/
/* voting statistics */
/*********************/

/*
 * application environment
 */ 
include("path.php");
include(BASE."include/"."incl.php");
require(BASE."include/"."category.php");

/* default to 25 apps, main categories */
$topNumber = 25;
$categoryId = "any"; /* default to all categories */

/* process the post variables to override the default settings */
if($_POST)
{
    if(isset($_POST['topNumber'])) $topNumber = (int)mysql_real_escape_string($_POST['topNumber']);
    if(isset($_POST['categoryId'])) $categoryId = mysql_real_escape_string($_POST['categoryId']);
}

/* Check if the value makes sense */
if(!is_integer($topNumber) || $topNumber > 200 || $topNumber < 1)
    $topNumber = 25;

apidb_header("Vote Stats - Top $topNumber Applications");

/* display the selection for the top number of apps to view */
echo "<form method=\"post\" name=\"message\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>Number of top apps to display:</b>";
echo "<select name='topNumber'>";
$topNumberArray = array(25, 50, 100, 200);

foreach ($topNumberArray as $i => $value)
{
    if($topNumberArray[$i] == $topNumber)
        echo "<option value='$topNumberArray[$i]' SELECTED>$topNumberArray[$i]";
    else
        echo "<option value='$topNumberArray[$i]'>$topNumberArray[$i]";
}
echo "</select>";

/* list sub categories */
if($categoryId == "any")
    $catId = 0;
else
    $catId = $categoryId;

/******************************************************************/
/* build an array of categories from the current category back up */
/* the tree to the main category */
$cat_array = Array();
$cat_name_array = Array();

if($catId != 0)
{
    $currentCatId = $catId;

    do
    {
        $catQuery = "SELECT appCategory.catName, appCategory.catParent ".
            "FROM appCategory WHERE appCategory.catId = '$currentCatId';";
        $result = mysql_query($catQuery);

        if($result)
        {
            $row = mysql_fetch_object($result);
            $catParent = $row->catParent;

            array_push($cat_array, "$currentCatId");
            array_push($cat_name_array, "$row->catName");
        }

        $currentCatId = $catParent;
    } while($currentCatId != 0);
}

/*******************************************************************/
/* add options for all of the categories that we are recursed into */
echo "<b>Section:</b>";
echo "<select name='categoryId'>";

if($catId == 0)
    echo "<option value='any' SELECTED>Any";
else
    echo "<option value='any'>Any";

$indent = 1;

/* reverse the arrays because we need the entries in the opposite order */
$cat_array_reversed = array_reverse($cat_array);
$cat_name_array_reversed = array_reverse($cat_name_array);
foreach ($cat_array_reversed as $i => $value)
{
    /* if this is the current category, select this option */
    if($categoryId == $cat_array_reversed[$i])
        echo "<option value='$cat_array_reversed[$i]' SELECTED>";
    else
        echo "<option value='$cat_array_reversed[$i]'>";

    echo str_repeat("-", $indent);
    echo stripslashes($cat_name_array_reversed[$i]);

    $indent++;
}

/******************************************************************/
/* add to the list all of the sub sections of the current section */
$cat = new Category($catId);
$subs = $cat->getCategoryList();

if($subs)
{
    while(list($id, list($name, $desc)) = each($subs))
    {
        /* if this is the current category, select this option */
        if($id == $catId)
            echo "<option value=$id SELECTED>";
        else
            echo "<option value=$id>";

        echo str_repeat("-", $indent);
        echo stripslashes($name);
    }
}
echo '</select>';
echo '<input type="submit" value="Refresh">';
echo '</form>';
echo '<br />';
echo '<br />';

/***************************************************/
/* build a list of the apps in the chosen category */
if(strcasecmp($categoryId, "any") == 0)
{
    /* leave out the appFamily.catId = '$categoryId' */
    $voteQuery = "SELECT appVotes.appId, appName, count(userId) as count ".
        "FROM appVotes, appFamily ".
        "WHERE appVotes.appId = appFamily.appId ".
        "GROUP BY appId ORDER BY count DESC LIMIT $topNumber";
} else
{
    $voteQuery = "SELECT appVotes.appId, appName, count(userId) as count ".
        "FROM appVotes, appFamily, appCategory ".
        "WHERE appVotes.appId = appFamily.appId AND ".
        "(appFamily.catId = '$categoryId' OR ".
        "(appFamily.catId = appCategory.catId AND appCategory.catParent = '$categoryId')) ".
        "GROUP BY appId ORDER BY count DESC LIMIT $topNumber";
}

$result = mysql_query($voteQuery);

if($result)
{
    echo html_frame_start("", "90%", '', 0);
    echo html_table_begin("width='100%' align=center");
    echo "<tr class=color4><td><font color=white>Application Name</font></td>\n";
    echo "<td><font color=white>Votes</font></td></tr>\n";
    
    $c = 1;
    while($row = mysql_fetch_object($result))
    {
	$bgcolor = ($c % 2) ? "color0" : "color1";
        $link = "<a href='appview.php?appId=$row->appId'>$row->appName</a>";
	echo "<tr class=$bgcolor><td width='90%'>$c. $link </td> <td> $row->count </td></tr>\n";
        $c++;
    }

    echo html_table_end();
    echo html_frame_end();

    /* Make sure we tell the user here are no apps, otherwise they might */
    /* think that something went wrong with the server */
    if($c == 1)
    {
	echo '<h2 align="center">No apps found in this category</h2>';
    }
 
    echo "<center><a href='help/?topic=voting'>What does this screen mean?</a></center>\n";

}
else
{
    echo "Error: " . mysql_error();
}

apidb_footer();

?>
