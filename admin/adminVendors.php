<?php
/*************************************************************/
/* code to view and maintain the list of application vendors */
/*************************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");

if(!havepriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}

apidb_header("Admin Vendors");
echo '<form name="qform" action="adminVendors.php" method="post" enctype="multipart/form-data">',"\n";

if ($_REQUEST['sub'])
{
    if($_REQUEST['sub'] == 'delete')
    {
        $sQuery = "SELECT * FROM appFamily WHERE vendorId = ".$_REQUEST['vendorId'].";";
        if (debugging())  echo "$sQuery";
        $hResult = query_appdb($sQuery);


        if(!$hResult || !mysql_num_rows($hResult))
        {
            $sQuery = "DELETE FROM vendor WHERE vendorId = ".$_REQUEST['vendorId'].";";
            if (debugging()) echo "$sQuery";
            $hResult = query_appdb($sQuery);
            echo html_frame_start("Delete vendor: ".$_REQUEST['vendorId'],400,"",0);
            if($hResult)
            {
                //success
                echo "<p>Vendor was successfully deleted</p>\n";
            }
        } else
        {
            echo "<p><b> Error: Can not delete a vendor with applications attached to it!</b></p>\n";
        }
        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminVendors.php');
    }
} else
{
    //get available vendors
    $sQuery = "SELECT * FROM vendor ORDER BY vendorName, vendorId;";
    $hResult = query_appdb($sQuery);

    if(!$hResult || !mysql_num_rows($hResult))
    {
        // no vendors
        echo html_frame_start("","90%");
        echo '<p><b>There are no application vendors.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        // show vendorlist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Vendor name</font></td>\n";
        echo "    <td><font color=white>Vendor url</font></td>\n";
        echo "    <td>&nbsp;</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($hResult))
        {
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td><a href=".BASE."vendorview.php?vendorId=".$ob->vendorId.">".$ob->vendorName."</a></td>\n";
            echo "    <td><a href=\"".$ob->vendorURL."\">".$ob->vendorURL."</a></td>\n";
            echo "    <td>[<a href='adminVendors.php?sub=delete&vendorId=$ob->vendorId'>delete</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }
}

echo "</form>";
apidb_footer();
?>
