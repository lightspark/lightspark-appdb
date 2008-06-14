<?php

/**
 * Classes for managing SQL filters (parts of SQL queries)
 *
 * Copyright 2008 BitSplash Software LLC
 * Copyright 2008 Alexander N. Sørnes <alex@thehandofagony.com>
 *
*/

define('FILTER_LIKE', 1);
define('FILTER_EQUALS', 2);
define('FILTER_GREATER_THAN', 3);
define('FILTER_LESS_THAN', 4);
define('FILTER_NOT_EQUALS', 5);
define('FILTER_NOT_LIKE', 6);
define('FILTER_OPTION_BOOL', 7);

/* A filter as part of an SQL query, such as something = 'somevalue' */
class Filter
{
    private $sColumn; // The table column the filter is for
    private $iType; // The type of filter, like EQUALS, LIKE
    private $sData; // What the column is to be compared to */

    public function Filter($sColumn, $iType, $sData)
    {
        $this->sColumn = $sColumn;
        $this->iType = $iType;
        $this->sData = $sData;
    }

    public function getColumn()
    {
        return $this->sColumn;
    }

    public function setData($sData)
    {
        $this->sData = $sData;
    }

    public function getOperatorId()
    {
        return $this->iType;
    }

    public function getData()
    {
        return $this->sData;
    }

    public function getOperator()
    {
        switch($this->iType)
        {
            case FILTER_LIKE:
                return 'LIKE';
            case FILTER_EQUALS:
                return '=';
            case FILTER_LESS_THAN:
                return '<';
            case FILTER_GREATER_THAN:
                return '>';
            case FILTER_NOT_EQUALS:
                return '!=';
            case FILTER_NOT_LIKE:
                return 'NOT LIKE';

            default:
                return 0; // error
        }
    }

    /* Gets an SQL expression representing the current filter, for use in a WHERE clause */
    public function getExpression()
    {
        /* We let callers handle options themselves, so don't include them in the WHERE clause */
        if($this->iType == FILTER_OPTION_BOOL)
            return '';

        $sOp = $this->getOperator();

        return "{$this->sColumn} $sOp '{$this->sData}'";
    }
}

/* Class handling tables where the user can filter contents */
class FilterSet
{
    private $aFilters; // Array of filters for this table

    public function FilterSet($sTableName = '')
    {
        $this->aFilters = array();

        if($sTableName)
            $this->loadTable($sTableName);
    }

    public function loadTable($sTableName)
    {
        $sQuery = "SELECT * FROM $sTableName";
        $hResult = query_appdb($sQuery);

        while($oRow = mysql_fetch_object($hResult))
        {
            $this->addFilterObject(new Filter($oRow->sColumn, $oRow->iType, $oRow->sData));
        }
    }

    public function saveTable($sTableName)
    {
        $hResult = query_appdb("DROP TABLE IF EXISTS $sTableName");

        $hResult = query_appdb("CREATE TABLE $sTableName (
                                   sColumn VARCHAR(255) NOT NULL,
                                   iType INT(3) NOT NULL,
                                   sData VARCHAR(255) NOT NULL DEFAULT ''
                                   )");

        if(!$hResult)
            return false;

        $bSuccess = true;
        foreach($this->aFilters as $oFilter)
        {
            $hResult = query_appdb("INSERT INTO $sTableName (sColumn,iType,sData)
                                    VALUES('{$oFilter->getColumn()}','{$oFilter->getOperatorId()}','{$oFilter->getData()}')");
            if(!$hResult)
                $bSuccess = false;
        }
        return $bSuccess;
    }

    public function addFilterObject(Filter $oFilter)
    {
        $this->aFilters[] = $oFilter;
    }

    public function AddFilter($sColumn, $iType, $sData)
    {
        $this->aFilters[] = new Filter($sColumn, $iType, $sData);
    }

    public function getFilterCount()
    {
        return sizeof($this->aFilters);
    }

    public function getFilters()
    {
        return $this->aFilters;
    }

    public function getWhereClause()
    {
        $aFilters = array();
        for($i = 0; $i < sizeof($this->aFilters); $i++)
        {
            $oFilter = $this->aFilters[$i];

            $sThisFilter = $oFilter->getExpression();

            if($sThisFilter)
                $aFilters[] = $sThisFilter;
        }

        return implode($aFilters, ' AND ');
    }

    function getQuery($sTable, $iLimit = 0)
    {
        $sWhere = $this->getFilterCount() ? 'WHERE '.$this->getWhereClause() : '';
        $sQuery = "SELECT * FROM $sTable $sWhere";

        $iLimit = mysql_real_escape_string($iLimit);

        if($iLimit)
            $sQuery .= " LIMIT 0,$iLimit";

        return $sQuery;
    }

    function getMatchedItems($sTable, $iLimit = 0)
    {
        return query_appdb($this->getQuery($sTable, $iLimit));
    }

    function getMatchedItemsCount($sTable)
    {
        return mysql_num_rows($this->getMatchedItems($sTable));
    }

}

?>
