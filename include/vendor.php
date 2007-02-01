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
    var $aApplicationsIds;  // an array that contains the appId of every application linked to this vendor

    /**    
     * constructor, fetches the data.
     */
    function Vendor($iVendorId = null, $oRow = null)
    {
        // we are working on an existing vendor
        if(is_numeric($iVendorId))
        {
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
                $this->iVendorId = $iVendorId;
                $this->sName = $oRow->vendorName;
                $this->sWebpage = $oRow->vendorURL;
            }

            /*
             * We fetch applicationsIds. 
             */
            $sQuery = "SELECT appId
                       FROM appFamily
                       WHERE vendorId = '?'";
            if($hResult = query_parameters($sQuery, $iVendorId))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aApplicationsIds[] = $oRow->appId;
                }
            }
        }
    }


    /**
     * Creates a new vendor.
     */
    function create()
    {
        $hResult = query_parameters("INSERT INTO vendor (vendorName, vendorURL) ".
                                    "VALUES ('?', '?')",
                                        $this->sName, $this->sWebpage);
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
        echo '<tr valign=top><td class="color1" width="20%"><b>Vendor Name</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sVendorName" value="'.$this->sName.'" size="50"></td></tr>',"\n";
        // Url
        echo '<tr valign=top><td class="color1"><b>Vendor Url</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sVendorWebpage" value="'.$this->sWebpage.'" size="50"></td></tr>',"\n";

        echo  '<input type="hidden" name="iVendorId" value="'.$this->iVendorId.'">',"\n";

        echo "</table>\n";
    }

    function objectGetEntries($bQueued, $iRows = 0, $iStart = 0)
    {
        /* Vendor queueing is not implemented yet */
        if($bQueued)
            return NULL;

        if(!$iRows)
            $iRows = getNumberOfVendors();

        $hResult = query_parameters("SELECT * FROM vendor
                       ORDER BY vendorName LIMIT ?,?",
                           $iStart, $iRows);

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectOutputHeader($sClass = "")
    {
        $sCells = array(
            "Name",
            "Website",
            array("Linked apps", "align=\"right\""));

        if(vendor::canEdit())
            $sCells[sizeof($sCells)] = "Action";

        echo html_tr($sCells, $sClass);
    }

    function objectGetInstanceFromRow($oRow)
    {
        return new vendor($oRow->vendorId, $oRow);
    }

    function objectOutputTableRow($sClass = "")
    {
        $aCells = array(
            "<a href=\"".BASE."vendorview.php?iVendorId=$this->iVendorId\">".
            "$this->sName</a>",
            "<a href=\"$this->sWebpage\">$this->sWebpage</a>",
            array(sizeof($this->aApplicationsIds), "align=\"right\""));

        if($this->canEdit())
        {
            if(!sizeof($this->aApplicationsIds))
                $sDelete = " &nbsp; [<a href=\"".BASE."vendorview.php?sSub=delete&".
                "iVendorId=$this->iVendorId\">".
                "delete</a>]";

            $aCells[sizeof($aCells)] = "[<a href=\"".BASE."admin/editVendor.php?".
            "iVendorId=$this->iVendorId\">edit</a>]$sDelete";
        }

        echo html_tr($aCells, $sClass);
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;
        else
            return FALSE;
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
            echo "[<a href=\"".BASE."admin/editVendor.php?iVendorId=$this->iVendorId\">edit</a>]";
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
                echo '<li> <a href="appview.php?iAppId='.$oApp->iAppId.'">'.
                     $oApp->sName.'</a> </li>',"\n";
            }
            echo '</ol>',"\n";
        }
    }
}

/* Get the total number of Vendors in the database */
function getNumberOfVendors()
{
    $hResult = query_parameters("SELECT count(*) as num_vendors FROM vendor");
    if($hResult)
    {
      $oRow = mysql_fetch_object($hResult);
      return $oRow->num_vendors;
    }
    return 0;
}
?>
