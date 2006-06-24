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
                       WHERE vendorId = ".$iVendorId;
            if($hResult = query_appdb($sQuery))
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
                       WHERE vendorId = ".$iVendorId;
            if($hResult = query_appdb($sQuery))
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
    function create($sName=null, $sWebpage=null)
    {
        $hResult = query_parameters("INSERT INTO vendor (vendorName, vendorURL) ".
                                    "VALUES ('?', '?')", $sName, $sWebpage);
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
    function update($sName=null, $sWebpage=null)
    {
        if(!$this->iVendorId)
            return $this->create($sName, $sWebpage);

        if($sName)
        {
            if (!query_appdb("UPDATE vendor SET vendorName = '".$sName."' WHERE vendorId = ".$this->iVendorId))
                return false;
            $this->sName = $sName;
        }     

        if($sWebpage)
        {
            if (!query_appdb("UPDATE vendor SET vendorURL = '".$sWebpage."' WHERE vendorId = ".$this->iVendorId))
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
                       WHERE vendorId = ".$this->iVendorId." 
                       LIMIT 1";
            query_appdb($sQuery);
            addmsg("The vendor has been deleted.", "green");
        }
    }

    function OutputEditor()
    {
        echo html_frame_start("Vendor Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // Name
        echo '<tr valign=top><td class="color1" width="20%"><b>Vendor Name</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sName" value="'.$this->sName.'" size="50"></td></tr>',"\n";
        // Url
        echo '<tr valign=top><td class="color1"><b>Vendor Url</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sWebpage" value="'.$this->sWebpage.'" size="50"></td></tr>',"\n";

        echo  '<input type="hidden" name="iVendorId" value="'.$this->iVendorId.'">',"\n";

        echo "</table>\n";
        echo html_frame_end();
    }

}

/* Get the total number of Vendors in the database */
function getNumberOfVendors()
{
    $hResult = query_appdb("SELECT count(*) as num_vendors FROM vendor");
    if($hResult)
    {
      $oRow = mysql_fetch_object($hResult);
      return $oRow->num_vendors;
    }
    return 0;
}
?>
