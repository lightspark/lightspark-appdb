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
        // we are working on an existing category
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
                $oRow = query_fetch_object($hResult);
                if($oRow)
                {
                    $this->iCatId = $iCatId;
                    $this->iParentId = $oRow->catParent;
                    $this->sName = $oRow->catName;
                    $this->sDescription = $oRow->catDescription;
                }
            }

            /*
             * We fetch applicationsIds. 
             */
            $sQuery = "SELECT appId
                       FROM appFamily
                       WHERE catId = '?'
                       AND state = 'accepted' ORDER BY appName";
            if($hResult = query_parameters($sQuery, $iCatId))
            {
                while($oRow = query_fetch_object($hResult))
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
                while($oRow = query_fetch_object($hResult))
                {
                    $this->aSubcatsIds[] = $oRow->catId;
                }
            }
        }
    }


    /**
     * Creates a new category.
     */
    function create()
    {
        $hResult = query_parameters("INSERT INTO appCategory (catName, catDescription, catParent) ".
                                    "VALUES('?', '?', '?')",
                                    $this->sName, $this->sDescription, $this->iParentId);
        if($hResult)
        {
            $this->iCatId = query_appdb_insert_id();
            $this->category($this->iCatId);
            return true;
        }

        return false;
    }

    /**
     * Update category.
     * Returns true on success and false on failure.
     */
    function update()
    {
        if(!query_parameters("UPDATE appCategory SET catName = '?', catDescription = '?', catParent = '?' WHERE catId = '?'",
                             $this->sName, $this->sDescription, $this->iParentId, $this->iCatId))
            return false;

        return true;
    }


    /**    
     * Deletes the category from the database. 
     */
    function delete()
    {
        if(!$this->canEdit())
            return false;

        if(sizeof($this->aApplicationsIds)>0)
            return FALSE;

        $sQuery = "DELETE FROM appCategory 
                    WHERE catId = '?' 
                    LIMIT 1";
        query_parameters($sQuery, $this->iCatId);

        return true;
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        return new mailOptions();
    }

    function objectGetChildren()
    {
        /* We don't have any (or we do, sort of, but we don't use them for anything at the moment) */
                return array();
    }

    /* Get a category's subcategory objects.  Names are indented according
       to subcategory level */
    function getSubCatList($iLevel)
    {
        $aOut = array();
        $iId = $this->iCatId ? $this->iCatId : 0;

        $sIndent = '';
        for($i = 0; $i < $iLevel; $i++)
            $sIndent .= '&nbsp; &nbsp;';

        $hResult = query_parameters("SELECT * FROM appCategory WHERE catParent = '?'
                                     ORDER BY catName", $iId);

        while($oRow = mysql_fetch_object($hResult))
        {
            $oCat = new category($oRow->catId);
            $oCat->sName = $sIndent.$oCat->sName;
            $aOut[] = $oCat;
            $aOut = array_merge($aOut, $oCat->getSubCatList($iLevel + 1));
        }
        return $aOut;
    }

    /* Get all category objects, ordered and with category names indented
       according to subcategory level */
    static function getOrderedList()
    {
        $oCat = new category();
        return $oCat->getSubCatList(0);
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        /* We don't send notification mails */
                return array(null, null, null);
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

        /* loop, working up through categories until we have no parent */
        while($iCatId != 0)
        {
            $hResult = query_parameters("SELECT catName, catId, catParent FROM appCategory WHERE catId = '?'",
                                       $iCatId);
            if(!$hResult || query_num_rows($hResult) != 1)
                break;
            $oCatRow = query_fetch_object($hResult);
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
                $str .= " &gt; ".$oApp->objectMakeLink();
                $str .= " &gt; ".$oVersion->sName;
            } else
            {
                $str .= " &gt; ".$oApp->sName;
            }
        }

        return $str;
    }

    function objectGetId()
    {
        return $this->iCatId;
    }

    function objectGetSubmitterId()
    {
        /* We don't log that */
        return 0;
    }

    function outputEditor()
    {
        $sQuery = "SELECT catId, catName FROM appCategory WHERE catId!='?'";
        $hResult = query_parameters($sQuery, $this->iCatId);

        /* Add the virtual 'Main' category */
        $aCatIds = array(0);
        $aCatNames = array('Main');

        /* Add the rest from the database */
        while($oRow = query_fetch_object($hResult))
        {
            $aCatIds[] = $oRow->catId;
            $aCatNames[] = $oRow->catName;
        }

        echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">
                <tr>
                <td width=\"15%\" class=\"box-label\"><b>Category name</b></td>
                <td class=\"box-body\">
                <input type=\"text\" size=\"50\" name=\"sName\" value=\"".$this->sName."\"> 
                </td>
                </tr>
                <tr>
                <td width=\"15%\" class=\"box-label\"><b>Description</b></td>
                <td class=\"box-body\">
                <input type=\"text\" size=\"50\" name=\"sDescription\" value=\"".$this->sDescription."\"> 
                </td>
                </tr>
                <tr>
                <td width=\"15%\" class=\"box-label\"><b>Parent</b></td>
                <td class=\"box-body\">
                ".html_select("iParentId",$aCatIds,$this->iParentId, $aCatNames)." 
                </td>
                </tr>
                </table>";
    }

    function allowAnonymousSubmissions()
    {
        return FALSE;
    }

    function getOutputEditorValues($aClean)
    {
        $this->sName = $aClean['sName'];
        $this->iParentId = $aClean['iParentId'];
        $this->sDescription = $aClean['sDescription'];
    }

    function mustBeQueued()
    {
        return $_SESSION['current']->hasPriv('admin');
    }

    function canEdit()
    {
        return $_SESSION['current']->hasPriv('admin');
    }

    /**
     * display the full path of the Category we are looking at
     */
    function display($appId, $versionId = '')
    {
        $sCatFullPath = Category::make_cat_path($this->getCategoryPath(), $appId, $versionId);
        echo html_frame_start("",'98%','',2);
        echo "<p><b>Category: ". $sCatFullPath ."</b><br>\n";
        echo html_frame_end();
    }
}

?>
