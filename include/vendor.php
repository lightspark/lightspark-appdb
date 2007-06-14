<?php
/**********************************/
/* this class represents a vendor */
/**********************************/

/**
 * Vendor class for handling vendors.
 */
class Vendor {
    var $iVendorId;
    var $sName;
    var $sWebpage;
    var $sQueued;
    var $aApplicationsIds;  // an array that contains the appId of every application linked to this vendor

    /**    
     * constructor, fetches the data.
     */
    function Vendor($iVendorId = null, $oRow = null)
    {
        // we are working on an existing vendor
        if(!$iVendorId && !$oRow)
            return;

        if(!$oRow)
        {
            /*
                * We fetch the data related to this vendor.
                */
            $sQuery = "SELECT *
                        FROM vendor
                        WHERE vendorId = '?'";
            if($hResult = query_parameters($sQuery, $iVendorId))
                $oRow = mysql_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iVendorId = $oRow->vendorId;
            $this->sName = $oRow->vendorName;
            $this->sWebpage = $oRow->vendorURL;
            $this->sQueued = $oRow->queued;
        }

        /*
            * We fetch applicationsIds. 
            */
        $sQuery = "SELECT appId
                    FROM appFamily
                    WHERE vendorId = '?'";
        if($hResult = query_parameters($sQuery, $this->iVendorId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $this->aApplicationsIds[] = $oRow->appId;
            }
        }
    }


    /**
     * Creates a new vendor.
     *
     * NOTE: If creating a vendor with the same name as an existing vendor
     *       we retrieve the existing vendors information and return true,
     *       even though we didn't create the vendor, this makes it easier
     *       for the user of the vendor class.
     */
    function create()
    {
        /* Check for duplicates */
        $hResult = query_parameters("SELECT * FROM vendor WHERE vendorName = '?'",
                                   $this->sName);
        if($hResult && $oRow = mysql_fetch_object($hResult))
        {
            if(mysql_num_rows($hResult))
            {
                $this->vendor($oRow->vendorId);

                /* Even though we did not create a new vendor, the caller is provided
                with an id and can proceed as normal, so we return TRUE */
                return TRUE;
            }
        }

        $hResult = query_parameters("INSERT INTO vendor (vendorName, vendorURL, queued) ".
                                    "VALUES ('?', '?', '?')",
                                        $this->sName, $this->sWebpage,
                                        $this->mustBeQueued() ? "true" : "false");
        if($hResult)
        {
            $this->iVendorId = mysql_insert_id();
            $this->vendor($this->iVendorId);
            return true;
        }
        else
        {
            addmsg("Error while creating a new vendor.", "red");
            return false;
        }
    }

    /**
     * Un-queue vendor
     * Returns TRUE or FALSE
     */
    function unQueue()
    {
        if(!$this->canEdit())
            return FALSE;

        $hResult = query_parameters("UPDATE vendor SET queued = '?' WHERE vendorId = '?'",
                                       'false', $this->iVendorId);

        if(!$hResult)
            return FALSE;

        return TRUE;
    }

    /**
     * Update vendor.
     * Returns true on success and false on failure.
     */
    function update()
    {
        if(!$this->iVendorId)
            return $this->create();

        if($this->sName)
        {
            if (!query_parameters("UPDATE vendor SET vendorName = '?' WHERE vendorId = '?'",
                                  $this->sName, $this->iVendorId))
                return false;
            $this->sName = $sName;
        }

        if($this->sWebpage)
        {
            if (!query_parameters("UPDATE vendor SET vendorURL = '?' WHERE vendorId = '?'",
                                  $this->sWebpage, $this->iVendorId))
                return false;
            $this->sWebpage = $sWebpage;
        }
        return true;
    }


    /**
     * Deletes the vendor from the database. 
     */
    function delete($bSilent=false)
    {
        if(sizeof($this->aApplicationsIds)>0)
        {
            addmsg("The vendor has not been deleted because there are still applications linked to it.", "red");
        } else 
        {
            $sQuery = "DELETE FROM vendor 
                       WHERE vendorId = '?' 
                       LIMIT 1";
            if(query_parameters($sQuery, $this->iVendorId))
            {
                addmsg("The vendor has been deleted.", "green");
                return TRUE;
            }

            return FALSE;
        }
    }

    function outputEditor()
    {
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // Name
        echo html_tr(array(
                           array("<b>Vendor Name:</b>", 'align=right class="color0"'),
                           array('<input type=text name="sVendorName" value="'.$this->sName.'" size="60">', 'class="color0"')
                           ));
        // Url
        echo html_tr(array(
                           array("<b>Vendor URL:</b>", 'align=right class="color0"'),
                           array('<input type=text name="sVendorWebpage" value="'.$this->sWebpage.'" size="60">', 'class="color0"')
                           ));

        echo  '<input type="hidden" name="iVendorId" value="'.$this->iVendorId.'">',"\n";

        echo "</table>\n";
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0)
    {
        /* Vendor queueing is not implemented yet */
        if($bQueued)
            return FALSE;

        /* Not implemented */
        if($bRejected)
            return FALSE;

        if(!$iRows)
            $iRows = Vendor::objectGetEntriesCount($bQueued, $bRejected);

        $hResult = query_parameters("SELECT * FROM vendor
                       ORDER BY vendorName LIMIT ?,?",
                           $iStart, $iRows);

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectGetHeader()
    {
        $aCells = array(
            "Name",
            "Website",
            array("Linked apps", "align=\"right\""));

        return $aCells;
    }

    function objectGetTableRow()
    {
        $aCells = array(
            $this->objectMakeLink(),
            "<a href=\"$this->sWebpage\">$this->sWebpage</a>",
            array(sizeof($this->aApplicationsIds), "align=\"right\""));

        $bDeleteLink = sizeof($this->aApplicationsIds) ? FALSE : TRUE;

        return array($aCells, null, $bDeleteLink, null);
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;
        else
            return FALSE;
    }

    function mustBeQueued()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return FALSE;
        else
            return TRUE;
    }

    function getOutputEditorValues($aClean)
    {
        $this->sName = $aClean['sVendorName'];
        $this->sWebpage = $aClean['sVendorWebpage'];
    }

    function display()
    {
        echo 'Vendor Name: '.$this->sName,"\n";
        if($this->canEdit())
        {
            echo "[<a href=\"".$_SERVER['PHP_SELF']."?sClass=vendor&sAction=edit&".
                 "iId=$this->iVendorId&sTitle=Edit%20Vendor\">edit</a>]";
        }

        echo '<br />',"\n";
        if ($this->sWebpage)
            echo 'Vendor URL:  <a href="'.$this->sWebpage.'">'.
                 $this->sWebpage.'</a> <br />',"\n";


        if($this->aApplicationsIds)
        {
            echo '<br />Applications by '.$this->sName.'<br /><ol>',"\n";
            foreach($this->aApplicationsIds as $iAppId)
            {
                $oApp  = new Application($iAppId);
                echo '<li>'.$oApp->objectMakeLink().'</li>',"\n";
            }
            echo '</ol>',"\n";
        }
    }

    /* Make a URL for viewing the specified vendor */
    function objectMakeUrl()
    {
        $oManager = new objectManager("vendor", "View Vendor");
        return $oManager->makeUrl("view", $this->iVendorId);
    }

    /* Make a HTML link for viewing the specified vendor */
    function objectMakeLink()
    {
        return "<a href=\"".$this->objectMakeUrl()."\">$this->sName</a>";
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        /* Not implemented */
        if($bQueued)
            return FALSE;

        /* Not implemented */
        if($bRejected)
            return FALSE;

        $hResult = query_parameters("SELECT COUNT(vendorId) as count FROM vendor");

        if(!$hResult)
            return FALSE;

        if(!$oRow = mysql_fetch_object($hResult))
            return FALSE;

        return $oRow->count;
    }

    function allowAnonymousSubmissions()
    {
        return FALSE;
    }

    function objectMoveChildren($iNewId)
    {
        /* Keep track of how many children we have modified */
        $iCount = 0;

        foreach($this->aApplicationsIds as $iAppId)
        {
            $oApp = new application($iAppId);
            $oApp->iVendorId = $iNewId;
            if($oApp->update(TRUE))
                $iCount++;
            else
                return FALSE;
        }

        return $iCount;
    }

    function objectGetId()
    {
        return $this->iVendorId;
    }

    function objectGetItemsPerPage($bQueued = false)
    {
        $aItemsPerPage = array(25, 50, 100, 200);
        $iDefaultPerPage = 25;
        return array($aItemsPerPage, $iDefaultPerPage);
    }
}

?>
