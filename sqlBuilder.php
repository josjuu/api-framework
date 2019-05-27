<?php
/**
 * Created by PhpStorm.
 * User: Mson
 * Date: 01-05-2019
 * Time: 14:00
 */

class sqlBuilder
{
    //<editor-fold desc="class properties">
    /**
     * Cirumfix will be added before and after the table and column name.
     * Cirumfix for MySql is '`'.
     * Cirumfix for Sql Server is ''.
     *
     * @var string
     */
    private $circumfix = null;
    private $columns = null;
    private $sqlState = "NOT_SET";

    // Select parts
    private $partSelect = null;
    private $partFrom = null;
    private $partInnerJoin = null;
    private $partGroupBy = null;
    private $partHaving = null;
    private $partOrderBy = null;

    // Insert parts
    private $partInsertInto = null;
    private $partValues = null;

    // Delete parts
    private $partDelete = null;

    // Update parts
    private $partUpdate = null;
    private $partSet = null;

    // Misc
    private $partWhere = null;
    //</editor-fold>

    public function __construct($circumfix = "`")
    {
        $this->circumfix = $circumfix;
    }

    /**
     * Creates the update query.
     *
     * @param $table string
     *      The name of the table.
     * @param $columns array
     *      The param is expected to have the names of the columns in an array.
     * @param $values array|int|null
     *      If the param is an array, it is expected to be an array with the values of the columns.
     *      If the param is null, it will add the question marks for prepared statements.
     * @param $whereCause string|null
     *      Adds the whereCause to the sql.
     */
    public function createUpdate($table, $columns, $values = null, $whereCause = null)
    {
        if ($this->getSqlState() != "NOT_SET") {
            throw new InvalidArgumentException("There is still an active sql query");
        }

        if (!is_array($columns) || empty($columns)) {
            throw new InvalidArgumentException("Columns is not an array or empty.");
        }

        if (!is_string($table) || empty($table)) {
            throw new InvalidArgumentException("Table is not a string or empty.");
        }

        $this->partUpdate = "UPDATE {$this->circumfix}{$table}{$this->circumfix}";
        $this->setPartSetCustom($columns, $values);
        $this->partSet;
        $this->partWhere = $whereCause;

        $this->sqlState = "UPDATE_QUERY";
    }

    /**
     * Creates an insert query.
     *
     * @param $table string
     *      The name of the table.
     * @param $columns array
     *      The param is expected to have the names of the columns in an array.
     * @param $values array|int|null
     *      If the param is an array, it is expected to be an array in an array with the values.
     *      If the param is an int, it will add the question marks for prepared statements and the value of the param is the amount of values you want.
     *      If the param is null, it will add the question marks for prepared statements, but will only set it once. Same as you put the value 1 in the param.
     */
    public function createInsert($table, $columns, $values = null)
    {
        if ($this->getSqlState() != "NOT_SET") {
            throw new InvalidArgumentException("There is still an active sql query");
        }

        if (!is_array($columns) || empty($columns)) {
            throw new InvalidArgumentException("Columns is not an array or empty.");
        }

        if (!is_string($table) || empty($table)) {
            throw new InvalidArgumentException("Table is not a string or empty.");
        }

        $tempInsertInto = "INSERT INTO {$this->circumfix}{$table}{$this->circumfix} ("; // TODO: The quote is incorrect in sql server. Make it so that the quote is a variable and you can select the what surrounds the table.

        for ($i = 0; $i < count($columns); $i++) {
            $tempInsertInto .= $i != 0
                ? ", {$this->circumfix}{$columns[$i]}{$this->circumfix}"
                : "{$this->circumfix}{$columns[$i]}{$this->circumfix}";
        }

        $tempInsertInto .= ")";

        $this->partInsertInto = $tempInsertInto;

        $this->columns = $columns;
        $this->sqlState = "INSERT_QUERY";

        try {
            $this->setPartValuesCustom($values);
        } catch (InvalidArgumentException $e) {
            $this->resetSqlBuilder();
            throw $e;
        }
    }

    /**
     * Resets the saved sql query.
     */
    public function resetSqlBuilder()
    {
        $this->sqlState = "NOT_SET";
        $this->columns = null;
        $this->resetParts();
    }

    /**
     * Sets the parts to null.
     */
    private function resetParts()
    {
        $this->partSelect = null;
        $this->partFrom = null;
        $this->partInnerJoin = null;
        $this->partGroupBy = null;
        $this->partHaving = null;
        $this->partOrderBy = null;
        $this->partInsertInto = null;
        $this->partValues = null;
        $this->partDelete = null;
        $this->partUpdate = null;
        $this->partSet = null;
        $this->partWhere = null;
    }

    /**
     * Creates and returns the sql query.
     *
     * @return string
     *      Returns the sql query.
     */
    public function getSql()
    {
        if ($this->getSqlState() == "NOT_SET") {
            throw new InvalidArgumentException("Unable to make query. Sql state was not set.");
        }

        $sql = null;

        if ($this->getSqlState() == "INSERT_QUERY") {
            $sql = $this->partInsertInto;
            $sql .= " ";
            $sql .= $this->partValues;

            return $sql;
        }

        if ($this->getSqlState() == "DELETE_QUERY") {
            $sql = $this->partDelete;
        }

        if ($this->getSqlState() == "UPDATE_QUERY") {
            $sql = $this->partUpdate;
            $sql .= " ";
            $sql .= $this->partSet;
        }

        if ($this->getSqlState() == "SELECT_QUERY") {
            $sql = $this->partSelect;
            $sql .= " ";
            $sql .= $this->partFrom;
        }

        if (!isNullOrEmptyString($this->partWhere)) {
            $sql .= " ";
            $sql .= $this->partWhere;
        }

        if ($this->getSqlState() == "SELECT_QUERY") {
            if (!isNullOrEmptyString($this->partGroupBy)) {
                $sql .= " ";
                $sql .= $this->partGroupBy;
            }

            if (!isNullOrEmptyString($this->partHaving)) {
                $sql .= " ";
                $sql .= $this->partHaving;
            }

            if (!isNullOrEmptyString($this->partOrderBy)) {
                $sql .= " ";
                $sql .= $this->partOrderBy;
            }
        }

        $sql = preg_replace('/\s+/', ' ', $sql);

        return $sql;
    }

    /**
     * Gets the state of the sql query.
     *
     * @return string
     */
    public function getSqlState()
    {
        return $this->sqlState;
    }

    //<editor-fold desc="setters of the part with logic in it"
    /**
     * Sets the set part of the query with the columns and values.
     *
     * @param $columns
     *      An array with the names of the columns.
     * @param $values
     *      If the values is null then it will add the question marks for prepared statements.
     *      Else the values is an array with the values of the respective column.
     */
    public function setPartSetCustom($columns, $values)
    {
        $tempSet = "";

        if ($values == null) {
            for ($i = 0; $i < count($columns); $i++) {
                $tempSet .= $i != 0 ? ", " : " ";
                $tempSet .= "{$this->circumfix}{$columns[$i]}{$this->circumfix} = ?";
            }
        } else if (is_array($values)) {
            if (count($values) != count($columns)) {
                throw new InvalidArgumentException("The amount of columns and values wasn't the same.");
            }

            for ($i = 0; $i < count($values); $i++) {
                $tempSet .= $i != 0 ? ", " : " ";
                $tempSet .= "{$this->circumfix}{$columns[$i]}{$this->circumfix} = '{$values[$i]}'";
            }
        } else {
            throw new InvalidArgumentException("The type of values was incorrect.");
        }

        if (isNullOrEmptyString($this->partSet)) {
            $this->partSet = "SET " . $tempSet;
        } else {
            $this->partSet = $this->partSet . ", " . $tempSet;
        }
    }

    /**
     * Adds more to the values in the insert query.
     *
     * @param $partValues
     *      If the param is an array, it is expected to have an array in an array with the values.
     *      If the param is an int, it will add the question marks for prepared statements and the value of the param is the amount of values you want.
     *      If the param is null, it will add the question marks for prepared statements, but will only set it once. Same as you put the value 1 in the param.
     */
    public function setPartValuesCustom($partValues)
    {
        $tempValues = "";

        if (is_array($partValues)) {
            for ($i = 0; $i < count($partValues); $i++) {
                if (count($partValues[$i]) != count($this->columns)) {
                    throw new InvalidArgumentException("The amount of columns and values wasn't the same. Problem occurred with the " . ordinal($i + 1) . " array in values.");
                }

                $tempValues .= empty($tempValues) ? "(" : ", (";

                for ($j = 0; $j < count($partValues[$i]); $j++) {
                    $tempValues .= $j == 0
                        ? "'{$partValues[$i][$j]}'"
                        : ", '{$partValues[$i][$j]}'";
                }

                $tempValues .= ")";
            }
        } else if (is_numeric($partValues)) {
            if ($partValues < 1) {
                Throw new InvalidArgumentException("The value is negative. It is expected that it is 1 or higher.");
            }

            for ($i = 0; $i < $partValues; $i++) {
                $tempValues .= empty($tempValues) ? "(" : ", (";

                for ($j = 0; $j < count($this->columns); $j++) {
                    $tempValues .= $j == 0
                        ? ":{$this->columns[$j]}_$i"
                        : ", :{$this->columns[$j]}_$i";
                }

                $tempValues .= ")";
            }
        } else {
            $tempValues = "(";

            for ($j = 0; $j < count($this->columns); $j++) {
                $tempValues .= $j == 0
                    ? "?"
                    : ", ?";
            }

            $tempValues .= ")";
        }

        if (isNullOrEmptyString($this->partValues)) {
            $this->partValues = "VALUES " . $tempValues;
        } else {
            $this->partValues .= ", " . $tempValues;
        }
    }
    //</editor-fold>

    //<editor-fold desc="getters and setters of the parts">
    /**
     * @return null
     */
    public function getPartSelect()
    {
        return $this->partSelect;
    }

    /**
     * @return null
     */
    public function getPartFrom()
    {
        return $this->partFrom;
    }

    /**
     * @return null
     */
    public function getPartInnerJoin()
    {
        return $this->partInnerJoin;
    }

    /**
     * @param null $partInnerJoin
     */
    public function setPartInnerJoin($partInnerJoin)
    {
        $this->partInnerJoin = $partInnerJoin;
    }

    /**
     * @return null
     */
    public function getPartGroupBy()
    {
        return $this->partGroupBy;
    }

    /**
     * @param null $partGroupBy
     */
    public function setPartGroupBy($partGroupBy)
    {
        $this->partGroupBy = $partGroupBy;
    }

    /**
     * @return null
     */
    public function getPartHaving()
    {
        return $this->partHaving;
    }

    /**
     * @param null $partHaving
     */
    public function setPartHaving($partHaving)
    {
        $this->partHaving = $partHaving;
    }

    /**
     * @return null
     */
    public function getPartOrderBy()
    {
        return $this->partOrderBy;
    }

    /**
     * @param null $partOrderBy
     */
    public function setPartOrderBy($partOrderBy)
    {
        $this->partOrderBy = $partOrderBy;
    }

    /**
     * @return null
     */
    public function getPartInsertInto()
    {
        return $this->partInsertInto;
    }

    /**
     * @return null
     */
    public function getPartValues()
    {
        return $this->partValues;
    }

    /**
     * @param null $partValues
     */
    public function setPartValues($partValues)
    {
        $this->$partValues = $partValues;
    }

    /**
     * @return null
     */
    public function getPartDelete()
    {
        return $this->partDelete;
    }

    /**
     * @return null
     */
    public function getPartUpdate()
    {
        return $this->partUpdate;
    }

    /**
     * @return null
     */
    public function getPartSet()
    {
        return $this->partSet;
    }

    /**
     * @param null $partSet
     */
    public function setPartSet($partSet)
    {
        $this->partSet = $partSet;
    }

    /**
     * @return null
     */
    public function getPartWhere()
    {
        return $this->partWhere;
    }

    /**
     * @param null $partWhere
     */
    public function setPartWhere($partWhere)
    {
        $this->partWhere = $partWhere;
    }
    //</editor-fold>
}