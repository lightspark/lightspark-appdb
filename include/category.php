<?php
/***************************************************/
/* this class represents a category + its children */
/***************************************************/

class Category {
    
    var $name;
    var $id;
    var $subcat;


    /**
     * the special name "ROOT" is the top category
     */
    function Category($id = 0)
    {
        $this->id = $id;
        $this->load($id);
    }

    
    /**    
     * Deletes the category from the database. 
     * and request the deletion of linked elements.
     */
    function delete()
    {
        $r = query_appdb("SELECT appId FROM appFamily WHERE catId = ".$this->id,"Failed to delete category ".$this->id);
        if($r)
        {
            while($ob = mysql_fetch_object($r))
            {
                $oApp = new Application($ob->appId);
                $oApp->delete();
            }
            $r = query_appdb("DELETE FROM appCategory WHERE catId = $catId","Failed to delete category $catId");
            if($r)
                addmsg("Category $catId deleted.", "green");
        }
    }

    /**
     * load the category data into this class 
     */
    function load($id)
    {
        $this->id = $id;

        if($id == 0)
        {
            $this->name = "ROOT";
        } else
        {
            $result = query_appdb("SELECT * FROM appCategory WHERE catId = $id");
            if(!$result)
            {
                // category not found! 
                errorpage("Internal Error: Category not found!");
                return;
            }

            $ob = mysql_fetch_object($result);
            $this->name = $ob->catName;
        }

        $result = query_appdb("SELECT catId, catName, catDescription FROM ".
                              "appCategory WHERE catParent = $this->id " .
                              "ORDER BY catName");
        if(mysql_num_rows($result) == 0)
            return; // no sub categories

        $this->subcat = array();
        while($row = mysql_fetch_object($result))
        {
            // ignore NONAME categories
            if($row->catName == "NONAME")
                continue;
            $this->subcat[$row->catId] = array($row->catName, $row->catDescription);
        }
    }


    /**
     * resolve the category id by name
     */
    function getCategoryId($name)
    {
        if($name == "ROOT")
            return 0;

        $result = query_appdb("SELECT catId FROM appCategory WHERE ".
                              "catName = '$name'");
        if(!$result)
            return -1;
        if(mysql_num_rows($result) != 1)
             return -1;
        $row = mysql_fetch_object($result);
        return $row->catId;
    }


    /**
     * returns the list of sub categories
     *
     * category list has the following format:
     *
     *     {  { catId => { catName, catDescription } }, ...  }
     */
    function getCategoryList()
    {
        return $this->subcat;
    }

    /**
     * returns a path like:
     *
     *     { ROOT, Games, Simulation }
     */
    function getCategoryPath()
    {
        $path = array();
        $id   = $this->id;
        while(1)
        {
            $result = query_appdb("SELECT catName, catId, catParent FROM appCategory WHERE catId = $id");
            if(!$result || mysql_num_rows($result) != 1)
                break;
            $cat = mysql_fetch_object($result);
            $path[] = array($cat->catId, $cat->catName);
            $id = $cat->catParent;
        }
        $path[] = array(0, "ROOT");
        return array_reverse($path);
    }

    
    /**
     * returns a list of applications in the specified category
     */
    function getAppList($id)
    {
        $result = query_appdb("SELECT appId, appName, description FROM ".
                              "appFamily WHERE catId = $id ".
                              "ORDER BY appName");
        if(!$result || mysql_num_rows($result) == 0)
            return array();

        $list = array();
        while($row = mysql_fetch_object($result))
        {
            if($row->appName == "NONAME")
                continue;
            $list[$row->appId] = array($row->appName, $row->description);
        }
        return $list;
    }


    /**
     * returns the number of apps in the specified category
     */
    function getAppCount($id, $recurse = 1)
    {
        $total = 0;

        $result = query_appdb("SELECT appId FROM appFamily WHERE catId = $id");
        if($result)
            $total += mysql_num_rows($result);

        if($recurse)
        {
            $result = query_appdb("SELECT catId FROM appCategory WHERE catParent = $id");
            if($result)
            {
                 while($ob = mysql_fetch_object($result))
                     $total += $this->getAppCount($ob->catId, 1);
            }
        }
        return $total;
    }
};


/**
 * create the Category: line at the top of appdb pages$
 */
function make_cat_path($path, $appId = '', $versionId = '')
{
    $str = "";
    $catCount = 0;
    while(list($idx, list($id, $name)) = each($path))
    {
        if($name == "ROOT")
            $catname = "Main";
        else
            $catname = $name;

        if ($catCount > 0) $str .= " &gt; ";
        $str .= html_ahref($catname,"appbrowse.php?catId=$id");
        $catCount++;
    }

    if(!empty($appId))
    {
        if(!empty($versionId))
        {
            $str .= " &gt; ".html_ahref(lookup_app_name($appId),"appview.php?appId=$appId");
            $str .= " &gt; ".lookup_version_name($versionId);
        }
        else
        {
            $str .= " &gt; ".lookup_app_name($appId);
        }
    }

    return $str;
}

function lookupCategoryName($catId)
{
    $sResult = query_appdb("SELECT * FROM appCategory ".
               "WHERE catId = ".$catId);
    if(!$sResult || mysql_num_rows($sResult) != 1)
        return "Unknown category";

    $ob = mysql_fetch_object($sResult);
    return $ob->catName;
}

?>
