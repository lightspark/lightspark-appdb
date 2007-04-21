<?php
class htmlmenu {

    function htmlmenu($name, $form = null)
    {

        if ($form)
            echo "<form action=\"$form\" method=\"post\">\n";

        echo '
<div align=left>
<table width="160" border="0" cellspacing="0" cellpadding="0">
<tr>
<td colspan=2>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="topMenu">
      <tr>
        <td width="100%" rowspan="3" align="left"><span class="menuTitle">&nbsp; '.$name.'</span></td>
        <td rowspan="3" valign="middle" align="right"><img src="'.BASE.'images/winehq_border_dot_right.gif" alt=""></td>
        <td valign="top" align="left"><img src="'.BASE.'images/winehq_border_top_right.gif" alt=""></td>
      </tr>
      <tr>
        <td><img src="'.BASE.'images/blank.gif" width="1" height="1" alt=""></td>
      </tr>
      <tr>
        <td valign="bottom" align="right"><img src="'.BASE.'images/winehq_border_bottom_right.gif" alt=""></td>
      </tr>
    </table>
</td>
</tr>
<tr>
<td>
    <table width="155" border="0" cellspacing="0" cellpadding="1">
    <tr class="topMenu"><td>
      <table width="100%" border="0" cellspacing="0" cellpadding="5">
';

    }

    /* add a table row */
    function add($sName, $shUrl = null, $sAlign = "left")
    {
        if($shUrl)
        {
            // we have a valid url, make the entire table row clickable and provide
            // some highlighting for visual feedback
            html_tr_highlight_clickable($shUrl, "sideMenu", "#e0e6ff", "#ffffff");
            echo "<td width='100%' align=$sAlign><span class=MenuItem>&nbsp;<u>".
                    "<a href=\"$shUrl\">$sName</a></u></span></td>";
            echo "</tr>\n";
        } else
        {
            echo "  <tr class=sideMenu><td width='100%' align=$sAlign><span ".
                    "class=menuItem>&nbsp;$sName</span></td></tr>\n";
        }
    }

    function addmisc($sStuff, $sAlign = "left")
    {
        echo " <tr class=sideMenu><td width='100%' align=$sAlign><span class=menuItem>&nbsp;$sStuff</span></td></tr>\n";
    }

    function done($form = null)
    {
        echo '
        </table>
        </td></tr>
        </table>
        </td>
        <td><img src="'.BASE.'images/blank.gif" width=5 height=1 alt="-"></td>
        </tr>
        </table>
        </div>
        <br>
        ';

        if ($form)
            echo "</form>\n";
    }
}
?>
