<?php

/* class for managing objects */
/* - handles processing of queued objects */
/* - handles the display and editing of unqueued objects */
class ObjectManager
{
    var $sClass;
    var $bIsQueue;
    var $sTitle;
    var $iId;

    function ObjectManager($sClass, $sTitle = "list", $iId = false)
    {
        $this->sClass = $sClass;
        $this->sTitle = $sTitle;
        $this->iId = $iId;
    }

    /* Check whether the associated class has the given method */
    function checkMethod($sMethodName, $bEnableOutput)
    {
        // NOTE: we only 'new' here because php4 requires an instance
        //       of an object as the first argument to method_exists(), php5
        //       doesn't
        if(!method_exists(new $this->sClass(), $sMethodName))
        {
            if($bEnableOutput) echo "class '".$this->sClass."' lacks method '".$sMethodName."'\n";
            return false;
        }

        return true;
    }

    /* Check whether the specified methods are valid */
    function checkMethods($aMethods, $bExit = true)
    {
        foreach($aMethods as $sMethod)
        {
            if(!$this->checkMethod($sMethod, false))
            {
                echo "Selected class does not support this operation ".
                     "(missing '$sMethod()')\n";

                if($bExit)
                    exit;
                else
                    return FALSE;
            }
        }

        return TRUE;
    }

    /* displays the list of entries */
    function display_table()
    {
        $this->checkMethods(array("ObjectGetEntries", "ObjectOutputHeader",
             "ObjectGetInstanceFromRow", "ObjectOutputTableRow"));


        /* query the class for its entries */
        /* We pass in $this->bIsQueue to tell the object */
        /* if we are requesting a list of its queued objects or */
        /* all of its objects */
        $hResult = call_user_func(array($this->sClass,
                                        "objectGetEntries"), $this->bIsQueue);

        /* did we get any entries? */
        if(mysql_num_rows($hResult) == 0)
        {
            $sIsQueue = $this->bIsQueue ? "true" : "false";

            if($this->bIsQueue)
                echo "<center>The queue for '$this->sClass' is empty</center>";
            else
                echo "<center>No entries of '$this->sClass' are present</center>";

            echo "<br /><center><a href=\"".$_SERVER['PHP_SELF']."?sClass=".
                 "$this->sClass&bIsQueue=$sIsQueue&sTitle=".
                 urlencode($this->sTitle)."&sAction=add\">Add an entry?</a></center>";
            return;
        }

        /* output the header */
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="0">';

        call_user_func(array($this->sClass,
                             "objectOutputHeader"), "color4");


        /* output each entry */
        for($iCount = 0; $oRow = mysql_fetch_object($hResult); $iCount++)
        {
            $oObject = call_user_func(array($this->sClass,
                                            "objectGetInstanceFromRow"), $oRow);

            $oObject->objectOutputTableRow($this,
                ($iCount % 2) ? "color0" : "color1");
        }

        echo "</table>";

        $oObject = new $this->sClass();
        if($oObject->canEdit())
        {
            echo "<br /><br /><a href=\"".$_SERVER['PHP_SELF']."?sClass=".
                 "$this->sClass&sAction=add&sTitle=Add\">".
                 "Add entry</a>\n";
        }
    }

    /* display the entry for editing */
    function display_entry_for_editing($sBackLink)
    {
        $this->checkMethods(array("outputEditor", "getOutputEditorValues",
                                  "update", "create"));

        // link back to the previous page
        echo html_back_link(1, $sBackLink);

        echo '<form name="sQform" action="'.BASE.'objectManager.php?sClass='.
             $this->sClass."&bIsQueue=".($this->bIsQueue ? "true" : "false").
             ' method="post" enctype="multipart/form-data">',"\n";

        echo '<input type="hidden" name="sClass" value="'.$this->sClass.'" />';
        echo '<input type="hidden" name="sTitle" value="'.$this->sTitle.'" />';
        echo '<input type="hidden" name="iId" value="'.$this->iId.'" />';
        echo '<input type="hidden" name="bIsQueue" '.
             'value='.($this->bIsQueue ? "true" : "false").'>';

        $oObject = new $this->sClass($this->iId);

        $oObject->outputEditor();

        /* if this is a queue add a dialog for replying to the submitter of the
           queued entry */
        if($this->bIsQueue)
        {
            echo html_frame_start("Reply text", "90%", "", 0);
            echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="sReplyText" style="width: 100%" cols="80" '. 
                 'rows="10"></textarea></td></tr>',"\n";

            /* buttons for operations we can perform on this entry */
            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input name="sSub" type="submit" value="Submit" class="button" '. 
                 '/>',"\n";
            echo '<input name="sSub" type="submit" value="Delete" class="button" '.
                 '/>',"\n";
            echo '<input name="sSub" type="submit" value="Reject" class="button" '.
                 '/>',"\n";
            echo '<input name="sSub" type="submit" value="Cancel" class="button" '.
                 '/>',"\n";
            echo '</td></tr>',"\n";
            echo '</table>';
            echo html_frame_end();
        } else
        {
            echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
            echo '<input name="sSubmit" type="submit" value="Save" class="button">'.
                 '&nbsp',"\n";
            echo "</td></tr>\n";
        }

        echo '</form>';

    }

    /* Display help for queue processing */
    function display_queue_processing_help()
    {
        /* No help text defined, so do nothing */
        if(!method_exists($this->sClass, "ObjectDisplayQueueProcessingHelp"))
            return FALSE;

        call_user_func(array($this->sClass,
                             "ObjectDisplayQueueProcessingHelp"));
    }

    /* Delete the object associated with the given id */
    function delete_entry()
    {
        $this->checkMethods(array("delete", "canEdit"));

        $oObject = new $this->sClass($this->iId);

        if(!$oObject->canEdit())
            return FALSE;

        if($oObject->delete())
            util_redirect_and_exit($this->makeUrl("view", false));
        else
            echo "Failure.\n";
    }

    /* Display screen for submitting a new entry of given type */
    function add_entry($sBackLink)
    {
        $this->checkMethods(array("outputEditor", "getOutputEditorValues",
                                  "update", "create"));

        $oObject = new $this->sClass();

        echo "<form method=\"post\">\n";

        $oObject->outputEditor();

        echo "<input type=\"hidden\" name=\"sClass=\"distribution\" />\n";
        echo "<input type=\"hidden\" name=\"sTitle\" value=\"$this->sTitle\" />\n";

        echo "<div align=\"center\">";
        echo "<input type=\"submit\" class=\"button\" value=\"Submit\" ". 
        "name=\"sSubmit\" />\n";
        echo "</div></form>\n";

        echo html_back_link(1, $sBackLink);
    }

    /* View an entry */
    function view($sBackLink)
    {
        $this->checkMethods(array("display"));

        $oObject = new $this->sClass($this->iId);

        $oObject->display();

        echo html_back_link(1, $sBackLink);
    }

    /* Process form data generated by adding or updating an entry */
    function processForm($aClean)
    {
        if(!$aClean['sSubmit'])
            return;

        $this->checkMethods(array("getOutputEditorValues", "update", "create",
                                  "canEdit"));

        $this->iId = $this->getIdFromInput($aClean);

        $oObject = new $this->sClass($this->iId);

        $oObject->getOutputEditorValues($aClean);

        if($this->iId)
        {
            if(!$oObject->canEdit())
                return FALSE;

            $oObject->update();
        }
        else
            $oObject->create();

        $sIsQueue = $tihs->bIsQueue ? "true" : "false";

        util_redirect_and_exit($this->makeUrl("view", false, "$this->sClass list"));
    }

    /* Make an objectManager URL based on the object and optional parameters */
    function makeUrl($sAction = false, $iId = false, $sTitle = false)
    {
        if($iId)
            $sId = "&iId=$iId";

        if($sAction)
            $sAction = "&sAction=$sAction";

        $sIsQueue = $this->bIsQueue ? "true" : "false";

        if(!$sTitle)
            $sTitle = $this->sTitle;

        $sTitle = urlencode($sTitle);

        return $_SERVER['PHP_SELF']."?bIsQueue=$sIsQueue&sClass=$this->sClass".
               "&sTitle=$sTitle$sId$sAction";
    }

    /* Get id from form data */
    function getIdFromInput($aClean)
    {
        $sId = "i".ucfirst($this->sClass)."Id";
        return $aClean[$sId];
    }
}

?>
