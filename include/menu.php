<?php
class htmlmenu {

    function htmlmenu($name, $form = null)
    {
        global $apidb_root;

        if ($form)
            echo "<form action='$form' method=get>\n";

        echo '
<div align=left>
<table width="160" border="0" cellspacing="0" cellpadding="0">
<tr>
<td colspan=2>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="topMenu">
      <tr>
        <td width="100%" rowspan="3" align="left"><span class="menuTitle">&nbsp; '.$name.'</span></td>
        <td rowspan="3" valign="middle" align="right"><img src="'.$apidb_root.'images/winehq_border_dot_right.gif" border="0" alt=""></td>
        <td valign="top" align="left"><img src="'.$apidb_root.'images/winehq_border_top_right.gif" border="0" alt=""></td>
      </tr>
      <tr>
        <td><img src="'.$apidb_root.'images/blank.gif" width="1" height="1" border="0" alt=""></td>
      </tr>
      <tr>
        <td valign="bottom" align="right"><img src="'.$apidb_root.'images/winehq_border_bottom_right.gif" border="0" alt=""></td>
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
    function add($name, $url = null)
    {
        if($url)
        {
            echo "  <tr class=sideMenu><td width='100%'><span class=menuItem>&nbsp;<a href='$url' class=menuItem>$name</a></span></td></tr>\n";
        } else 
        {
            echo "  <tr class=sideMenu><td width='100%'><span class=menuItem>&nbsp;$name</span></td></tr>\n";
        }
    }

    function addmisc($stuff, $align = "left")
    {
        echo " <tr class=sideMenu><td width='100%' align=$align><span class=menuItem>&nbsp;$stuff</span></td></tr>\n";
    }

    function done($form = null)
    {
        global $apidb_root;

        echo '
        </table>
        </td></tr>
        </table>
        </td>
        <td><img src="'.$apidb_root.'images/blank.gif" border=0 width=5 height=1 alt="-"></td>
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
