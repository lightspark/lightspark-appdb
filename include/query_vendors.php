<!-- start of Vendor query -->
<table border=1 width="100%" cellspacing=0 cellpadding=3 bordercolor=black>
    <tr>
        <th class="box-title">Search Vendors</th>
    </tr>
    <tr>
        <td class="box-body">
        <form ACTION="stdquery.php" METHOD="get">
        <input TYPE="HIDDEN" NAME="orderby" VALUE="vendorId">
        Pattern:
        <input TYPE="TEXT"   NAME="searchfor"> (leave blank to match all)
        <input TYPE="HIDDEN" NAME="searchwhat" VALUE="vendor.vendorName">
        <input TYPE="HIDDEN" NAME="fields[]" VALUE="vendor.vendorId">
        <input TYPE="HIDDEN" NAME="fields[]" VALUE="vendor.vendorName">
        <input TYPE="HIDDEN" NAME="fields[]" VALUE="vendor.vendorURL">
        <br /><br />
        <input type=checkbox name=verbose value=yes> Verbose query results <br />
    <?php if(havepriv("admin")) echo "<input type=checkbox name=mode value=edit> Edit mode <br />\n"; ?>

        <br />Entries Per Page:
        <select NAME="linesPerPage">
        <option>50
        <option>100
        <option>150
        <option>200
        <option>500
        <option>ALL
        </select>
        <br /> <input TYPE="SUBMIT" VALUE="List Vendors">
        </form>
        </td>
    </tr>
</table>
<!-- end of Vendor query -->
