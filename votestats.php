<?php
/**
 * Voting statistics.
 *
 * Optional parameters:
 *  - iTopNumber, the number of applications to be displayed
 *  - iCategoryId, the category identifier of the category whose stats we want to see
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/category.php");

// set default values and check if the value makes sense
if(empty($aClean['iTopNumber']) || $aClean['iTopNumber'] > 200 || $aClean['iTopNumber'] < 0)
    $aClean['iTopNumber'] = 25;
if(empty($aClean['iCategoryId']))
    $aClean['iCategoryId'] = 0;

apidb_header("Vote Stats - Top ".$aClean['iTopNumber']." Applications");

/* display the selection for the top number of apps to view */
echo "<form method=\"post\" name=\"sMessage\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<b>Number of top apps to display:</b>";
echo "<select name='iTopNumber'>";
$topNumberArray = array(25, 50, 100, 200);

foreach ($topNumberArray as $i => $value)
{
    if($topNumberArray[$i] == $aClean['iTopNumber'])
        echo "<option value='$topNumberArray[$i]' SELECTED>$topNumberArray[$i]";
    else
        echo "<option value='$topNumberArray[$i]'>$topNumberArray[$i]";
}
echo "</select>";

/**
 * build an array of categories from the current category back up 
 * the tree to the main category 
 */
$cat_array = Array();
$cat_name_array = Array();

if(!empty($aClean['iCategoryId']))
{
    $currentCatId = $aClean['iCategoryId'];

    do
    {
        $sCatQuery = "SELECT appCategory.catName, appCategory.catParent ".
            "FROM appCategory WHERE appCategory.catId = '?'";
        $hResult = query_parameters($sCatQuery, $currentCatId);

        if($hResult)
        {
            $oRow = mysql_fetch_object($hResult);
            $catParent = $oRow->catParent;

            array_push($cat_array, "$currentCatId");
            array_push($cat_name_array, "$oRow->catName");
        }

        $currentCatId = $catParent;
    } while($currentCatId != 0);
}

/*******************************************************************/
/* add options for all of the categories that we are recursed into */
echo "<b>Section:</b>";
echo '<select name="iCategoryId">';

if(empty($aClean['iCategoryId']))
    echo '<option value="0" SELECTED>Any</option>';
else
    echo '<option value="0">Any</option>';

$indent = 1;

/* reverse the arrays because we need the entries in the opposite order */
$cat_array_reversed = array_reverse($cat_array);
$cat_name_array_reversed = array_reverse($cat_name_array);
foreach ($cat_array_reversed as $i => $value)
{
    /* if this is the current category, select this option */
    if($aClean['iCategoryId'] == $cat_array_reversed[$i])
        echo "<option value='$cat_array_reversed[$i]' SELECTED>";
    else
        echo "<option value='$cat_array_reversed[$i]'>";

    echo str_repeat("-", $indent);
    echo stripslashes($cat_name_array_reversed[$i]);
    echo "</option>";
    $indent++;
}


// add to the list all of the sub sections of the current section
$cat = new Category($aClean['iCategoryId']);
$subs = $cat->aSubcatsIds;

if($subs)
{
    while(list($i, $id) = each($subs))
    {
        $oSubcat = new Category($id);
        /* if this is the current category, select this option */
        if($id == $aClean['iCategoryId'])
            echo "<option value=$id SELECTED>";
        else
            echo "<option value=$id>";

        echo str_repeat("-", $indent);
        echo stripslashes($oSubcat->sName);
    }
}
echo '</select>';
echo '<input type="submit" value="Refresh" />';
echo '</form>';
echo '<br />';
echo '<br />';

/***************************************************/
/* build a list of the apps in the chosen category */
if(empty($aClean['iCategoryId']))
{
    /* leave out the appFamily.catId = '$aClean['iCategoryId']' */
    $hResult = query_parameters("SELECT appVotes.versionId, appName, count(userId) as 
                           count
                           FROM appVotes, appFamily, appVersion
                           WHERE appVotes.versionId = appVersion.versionId AND
                           appFamily.appId = appVersion.appId
                           GROUP BY appVotes.versionId ORDER BY count DESC LIMIT ?", 
                               $aClean['iTopNumber']);
} else
{
    /* Display all application for a given category (including sub categories)
    SELECT f.appId, f.appName
    FROM appFamily AS f, appCategory AS c
    WHERE f.catId = c.catId
    AND (
    c.catId =29
    OR c.catParent =29)*/
    
    $hResult = query_parameters("SELECT v.versionId, f.appName, count( v.versionId ) AS count
                  FROM appFamily AS f, appCategory AS c, appVotes AS v, appVersion
                  WHERE appVersion.appId = f.appId
                  AND appVersion.versionId = v.versionId
                  AND f.catId = c.catId
                  AND (
                        c.catId = '?'
                        OR c.catParent = '?'
                      )
                  GROUP BY v.versionId
                  ORDER BY count DESC LIMIT ?", $aClean['iCategoryId'], $aClean['iCategoryId'], $aClean['iTopNumber']);
}

if($hResult)
{
    echo html_frame_start("", "90%", '', 0);
    echo html_table_begin("width='100%' align=center");
    echo "<tr class=color4><td><font color=white>Application Name</font></td>\n";
    echo "<td><font color=white>Votes</font></td></tr>\n";
    
    $c = 1;
    while($row = mysql_fetch_object($hResult))
    {
        $bgcolor = ($c % 2) ? "color0" : "color1";
        $link = "<a href='appview.php?iVersionId=$row->versionId'>$row->appName</a>";
        echo "<tr class=$bgcolor><td width='90%'>$c. $link </td> <td> $row->count </td></tr>\n";
        $c++;
    }

    echo html_table_end();
    echo html_frame_end();

    /* Make sure we tell the user here are no apps, otherwise they might */
    /* think that something went wrong with the server */
    if($c == 1)
    {
        echo '<h2 align="center">No apps found in this category, please vote for your favorite apps!</h2>';
    }
 
    echo '<p align="center"><a href="help/?sTopic=voting">What does this screen mean?</a></p>';
}

apidb_footer();
?>
