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
        if($iVendorId)
        {
            /*
             * We fetch the data related to this vendor.
             */
            if(!$this->vendorId)
            {
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
        $aInsert = compile_insert_string(array( 'vendorName'=> $sName,
                                                'vendorURL' => $sWebpage ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO vendor $sFields VALUES $sValues", "Error while creating a new vendor."))
        {
            $this->iVendorId = mysql_insert_id();
            $this->vendor($this->iVendorId);
            return true;
        }
        else
            return false;
    }


    /**
     * Update vendor.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sWebpage=null)
    {
        if ($sName)
        {
            if (!query_appdb("UPDATE vendor SET vendorName = '".$sName."' WHERE vendorId = ".$this->iVendorId))
                return false;
            $this->sName = $sName;
        }     

        if ($sWebpage)
        {
            if (!query_appdb("UPDATE vendor SET vendorURL = '".$sWebpage."' WHERE vendorId = ".$this->iVendorId))
                return false;
            $this->sWebpage = $sWebpage;
        }
        return true;
    }


    /**    
     * Deletes the vendor from the database. 
     * FIXME: What should happen if sizeof($aApplicationsIds)>0 ?
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM vendor 
                   WHERE vendorId = ".$this->iVendorId." 
                   LIMIT 1";
    }
}
?>
