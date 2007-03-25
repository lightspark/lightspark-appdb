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
    var $bIsRejected;

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
        $this->checkMethods(array("ObjectGetEntries", "ObjectGetHeader",
             "ObjectGetInstanceFromRow", "ObjectOutputTableRow", "canEdit"));

        $oObject = new $this->sClass();
        /* query the class for its entries */
        /* We pass in $this->bIsQueue to tell the object */
        /* if we are requesting a list of its queued objects or */
        /* all of its objects */
        $hResult = $oObject->objectGetEntries($this->bIsQueue, $this->bIsRejected);

        /* did we get any entries? */
        if(mysql_num_rows($hResult) == 0)
        {
            switch($this->getQueueString($this->bIsQueue, $this->bIsRejected))
            {
                case "true":
                    echo "<center>The queue for '$this->sClass' is empty</center>";
                break;
                case "false":
                    echo "<center>No entries of '$this->sClass' are present</center>";
                break;
                case "rejected":
                    echo "<center>No rejected entries of '$this->sClass' are ".
                            "present</center>";
                break;
            }

            echo "<br /><center><a href=\"".$this->makeUrl("add", false,
                    "Add $this->sClass entry")."\">Add an entry?</a></center>";
            return;
        }

        /* output the header */
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="0">';

        /* Output header cells */
        $this->outputHeader("color4");

        /* output each entry */
        for($iCount = 0; $oRow = mysql_fetch_object($hResult); $iCount++)
        {
            $oObject = call_user_func(array($this->sClass,
                                            "objectGetInstanceFromRow"), $oRow);

            /* arg1 = OM object, arg2 = CSS style, arg3 = text for edit link */
            $oObject->objectOutputTableRow($this, ($iCount % 2) ? "color0" : "color1",
                $this->bIsQueue ? "process" : "edit");
        }

        echo "</table>";

        $oObject = new $this->sClass();
        if($oObject->canEdit())
        {
            echo "<br /><br /><a href=\"".$this->makeUrl("add", false,
                    "Add $this->sClass")."\">Add entry</a>\n";
        }
    }

    /* display the entry for editing */
    function display_entry_for_editing($sBackLink, $sErrors)
    {
        $this->checkMethods(array("outputEditor", "getOutputEditorValues",
                                  "update", "create"));

        // link back to the previous page
        echo html_back_link(1, $sBackLink);

        $oObject = new $this->sClass($this->iId);

        /* Display errors, if any, and fetch form data */
        if($this->displayErrors($sErrors))
        {
            global $aClean;
            $oObject->getOutputEditorValues($aClean);
        }

        echo '<form name="sQform" action="'.$this->makeUrl("edit", $this->iId).
                '" method="post" enctype="multipart/form-data">',"\n";

        echo '<input type="hidden" name="sClass" value="'.$this->sClass.'" />';
        echo '<input type="hidden" name="sTitle" value="'.$this->sTitle.'" />';
        echo '<input type="hidden" name="iId" value="'.$this->iId.'" />';
        echo '<input type="hidden" name="bIsQueue" '.
             'value='.($this->bIsQueue ? "true" : "false").' />';
        echo '<input type="hidden" name="bIsRejected" '.
             'value='.($this->bIsRejected ? "true" : "false").' />';

        $oObject->outputEditor();

        /* if this is a queue add a dialog for replying to the submitter of the
           queued entry */
        if($this->bIsQueue)
        {
            /* If it isn't implemented, that means there is no default text */
            if(method_exists(new $this->sClass, "getDefaultReply"))
                $sDefaultReply = $oObject->getDefaultReply();

            echo html_frame_start("Reply text", "90%", "", 0);
            echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="sReplyText" style="width: 100%" cols="80" '. 
                 'rows="10">'.$sDefaultReply.'</textarea></td></tr>',"\n";

            /* buttons for operations we can perform on this entry */
            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input name="sSubmit" type="submit" value="Submit" class="button" '. 
                 '/>',"\n";
            if(!method_exists(new $this->sClass, "objectHideDelete"))
            {
                echo '<input name="sSubmit" type="submit" value="Delete" '.
                     'class="button" />',"\n";
            }

            if(!$this->bIsRejected)
            {
                echo '<input name="sSubmit" type="submit" value="Reject" class="button" '.
                    '/>',"\n";
            }

            echo '<input name="sSubmit" type="submit" value="Cancel" class="button" '.
                 '/>',"\n";
            echo '</td></tr>',"\n";
            echo '</table>';
            echo html_frame_end();
        } else
        {
            echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
            echo '<input name="sSubmit" type="submit" value="Submit" class="button">'.
                 '&nbsp',"\n";
            echo "</td></tr>\n";
        }

        echo '</form>';

    }

    /* Display help for queue processing */
    function display_queue_processing_help()
    {
        /* No help text defined, so do nothing */
        if(!method_exists(new $this->sClass(), "ObjectDisplayQueueProcessingHelp"))
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
    function add_entry($sBackLink, $sErrors = "")
    {
        $this->checkMethods(array("outputEditor", "getOutputEditorValues",
                                  "update", "create"));


        $oObject = new $this->sClass();

        /* Display help if it is exists */
        if(method_exists(new $this->sClass, "objectDisplayAddItemHelp"))
            $oObject->objectDisplayAddItemHelp();

        /* Display errors, if any, and fetch form data */
        if($this->displayErrors($sErrors))
        {
            global $aClean;
            $oObject->getOutputEditorValues($aClean);
        }
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

        /* If it isn't implemented, that means there is no default text */
        if(method_exists(new $this->sClass, "getDefaultReply"))
        {
            /* Don't send the default reply text */
            if($oObject->getDefaultReply() == $aClean['sReplyText'])
                $aClean['sReplyText'] = "";
        }

        $oObject->getOutputEditorValues($aClean);

        /* Check input, if necessary */
        if(method_exists(new $this->sClass, "checkOutputEditorInput"))
        {
            $sErrors = $oObject->checkOutputEditorInput($aClean);
            if($sErrors)
                return $sErrors;
        }

        switch($aClean['sSubmit'])
        {
            case "Submit":
                if($this->iId)
                {
                    if(!$oObject->canEdit())
                        return FALSE;

                    if($this->bIsQueue)
                        $oObject->unQueue();

                    $oObject->update();
                }
                else
                    $oObject->create();
            break;

            case "Reject":
                if(!$oObject->canEdit())
                    return FALSE;

                $oObject->reject();
            break;
            case "Delete":
                $this->delete_entry();
        }

        /* Displaying the entire un-queued list for a class is not a good idea,
        so only do so for queued data */
        if($this->bIsQueue)
            $sRedirectLink = $this->makeUrl("view", false, "$this->sClass list");
        else
            $sRedirectLink = APPDB_ROOT;

        util_redirect_and_exit($sRedirectLink);

        return TRUE;
    }

    /* Make an objectManager URL based on the object and optional parameters */
    function makeUrl($sAction = false, $iId = false, $sTitle = false)
    {
        if($iId)
            $sId = "&iId=$iId";

        if($sAction)
            $sAction = "&sAction=$sAction";

        $sIsQueue = $this->bIsQueue ? "true" : "false";
        $sIsRejected = $this->bIsRejected ? "true" : "false";

        if(!$sTitle)
            $sTitle = $this->sTitle;

        $sTitle = urlencode($sTitle);

        return APPDB_ROOT."objectManager.php?bIsQueue=$sIsQueue&sClass=$this->sClass".
               "&sTitle=$sTitle$sId$sAction&bIsRejected=$sIsRejected";
    }

    /* Get id from form data */
    function getIdFromInput($aClean)
    {
        $sId = "i".ucfirst($this->sClass)."Id";
        return $aClean[$sId];
    }

    /* Output headers for a table */
    function outputHeader($sClass)
    {
        $oObject = new $this->sClass();
        $aCells = $oObject->objectGetHeader();

        /* Add an action column if the user can edit this class, or if it is a queue.
           Even though a user annot process items, he can edit his queued submissions */
        if($oObject->canEdit() || $this->bIsQueue)
            $aCells[] = "Action";

        echo html_tr($aCells, $sClass);
    }

    function getQueueString($bQueued, $bRejected)
    {
        if($bQueued)
        {
            if($bRejected)
                $sQueueString = "rejected";
            else
                $sQueueString = "true";
        } else
            $sQueueString = "false";

        return $sQueueString;
    }

    function displayErrors($sErrors)
    {
        if($sErrors)
        {
            echo "<font color=\"red\">\n";
            echo "The following errors were found<br />\n";
            echo "<ul>$sErrors</ul>\n";
            echo "</font><br />";
        } else
            return FALSE;
    }
}

?>
