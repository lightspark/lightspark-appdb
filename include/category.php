<?php
/***************************************************/
/* this class represents a category + its children */
/***************************************************/

/**
 * Category class for handling categories.
 */
class Category {
    var $iCatId;
    var $iParentId;
    var $sName;
    var $sDescription;
    var $aApplicationsIds;  // an array that contains the appId of every application linked to this category
    var $aSubcatsIds;        // an array that contains the appId of every application linked to this category


    /**    
     * constructor, fetches the data.
     */
    function Category($iCatId = null)
    {
        // we are working on an existing vendor
        if($iCatId=="0" || $iCatId)
        {
            /*
             * We fetch the data related to this vendor.
             */
            $sQuery = "SELECT *
                       FROM appCategory
                       WHERE catId = '?' ORDER BY catName;";
            if($hResult = query_parameters($sQuery, $iCatId))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iCatId = $iCatId;
                $this->iParentId = $oRow->catParent;
                $this->sName = $oRow->catName;
                $this->sDescription = $oRow->catDescription;

            }

            /*
             * We fetch applicationsIds. 
             */
            $sQuery = "SELECT appId
                       FROM appFamily
                       WHERE catId = '?'
                       AND queued = 'false' ORDER BY appName";
            if($hResult = query_parameters($sQuery, $iCatId))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aApplicationsIds[] = $oRow->appId;
                }
            }

            /*
             * We fetch subcatIds. 
             */
            $sQuery = "SELECT catId
                       FROM appCategory
                       WHERE catParent = '?' ORDER BY catName;";
            if($hResult = query_parameters($sQuery, $iCatId))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aSubcatsIds[] = $oRow->catId;
                }
            }
        }
    }


    /**
     * Creates a new category.
     */
    function create($sName=null, $sDescription=null, $iParentId=null)
    {
        $hResult = query_parameters("INSERT INTO appCategory (catName, catDescription, catParent) ".
                                    "VALUES('?', '?', '?')",
                                    $sName, $sDescription, $iParentId);
        if($hResult)
        {
            $this->iCatId = mysql_insert_id();
            $this->category($this->iCatId);
            return true;
        }
        else
        {
            addmsg("Error while creating a new vendor.", "red");
            return false;
        }
    }


    /**
     * Update category.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sDescription=null, $iParentId=null)
    {
        if(!$this->iCatId)
            return $this->create($sName, $sDescription, $iParentId);

        if($sName)
        {
            if (!query_parameters("UPDATE appCategory SET catName = '?' WHERE catId = '?'",
                                  $sName, $this->iCatId))
                return false;
            $this->sName = $sName;
        }     

        if($sDescription)
        {
            if (!query_parameters("UPDATE appCategory SET catDescription = '?' WHERE catId = '?'",
                                  $sDescription, $this->iCatId))
                return false;
            $this->sDescription = $sDescription;
        }

        if($iParentId)
        {
            if (!query_parameters("UPDATE appCategory SET catParent = '?' WHERE catId = '?'",
                                  $iParentId, $this->iCatId))
                return false;
            $this->iParentId = $iParentId;
        }
       
        return true;
    }


    /**    
     * Deletes the category from the database. 
     */
    function delete($bSilent=false)
    {
        if(!$_SESSION['current']->canDeleteCategory($this))
            return false;

        if(sizeof($this->aApplicationsIds)>0)
        {
            addmsg("The category has not been deleted because there are still applications linked to it.", "red");
        } else 
        {
            $sQuery = "DELETE FROM appCategory 
                       WHERE catId = '?' 
                       LIMIT 1";
            query_parameters($sQuery, $this->iCatId);
            addmsg("The category has been deleted.", "green");
        }

        return true;
    }


    /**
     * returns a path like:
     *
     *     { ROOT, Games, Simulation }
     */
    function getCategoryPath()
    {
        $aPath = array();
        $iCatId  = $this->iCatId;
        while($iCatId != 0)
        {
            $hResult = query_parameters("SELECT catName, catId, catParent FROM appCategory WHERE catId = '?'",
                                       $iCatId);
            if(!$hResult || mysql_num_rows($hResult) != 1)
                break;
            $oCatRow = mysql_fetch_object($hResult);
            $aPath[] = array($oCatRow->catId, $oCatRow->catName);
            $iCatId = $oCatRow->catParent;
        }
        $aPath[] = array(0, "ROOT");
        return array_reverse($aPath);
    }

    /* return the total number of applications in this category */
    function getApplicationCount($depth = null)
    {
        $MAX_DEPTH = 5;

        if($depth)
            $depth++;
        else
            $depth = 0;

        /* if we've reached our max depth, just return 0 and stop recursing */
        if($depth >= $MAX_DEPTH)
            return 0;

        $totalApps = 0;

        /* add on all apps in each category this category includes */
        if($this->aSubcatsIds)
        {
            while(list($i, $iSubcatId) = each($this->aSubcatsIds))
            {
                $subCat = new Category($iSubcatId);
                $totalApps += $subCat->getApplicationCount($depth);
            }
        }

        $totalApps += sizeof($this->aApplicationsIds); /* add on the apps at this category level */
        
        return $totalApps;
    }
}


/*
 * Application functions that are not part of the class
 */

/**
 * create the Category: line at the top of appdb pages$
 */
function make_cat_path($path, $appId = '', $versionId = '')
{
    $str = "";
    $catCount = 0;
    while(list($iCatIdx, list($iCatId, $name)) = each($path))
    {
        if($name == "ROOT")
            $catname = "Main";
        else
            $catname = $name;

        if ($catCount > 0) $str .= " &gt; ";
        $str .= html_ahref($catname,"appbrowse.php?catId=$iCatId");
        $catCount++;
    }

    if(!empty($appId))
    {
        $oApp = new Application($appId);
        if(!empty($versionId))
        {
            $oVersion = new Version($versionId);
            $str .= " &gt; ".html_ahref($oApp->sName,"appview.php?appId=$appId");
            $str .= " &gt; ".$oVersion->sName;
        } else
        {
            $str .= " &gt; ".$oApp->sName;
        }
    }

    return $str;
}
?>
