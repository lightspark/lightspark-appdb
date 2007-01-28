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
    function Vendor($iVendorId = null)
    {
        // we are working on an existing vendor
        if(is_numeric($iVendorId))
        {
            /*
             * We fetch the data related to this vendor.
             */
            $sQuery = "SELECT *
                       FROM vendor
                       WHERE vendorId = '?'";
            if($hResult = query_parameters($sQuery, $iVendorId))
            {
                $oRow = mysql_fetch_object($hResult);
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
