<?php
/**
 * Created by PhpStorm.
 * User: Mson
 * Date: 01-05-2019
 * Time: 14:00
 */

class sqlBuilder
{
    /**
     * Cirumfix will be added before and after the table and column name.
     * Cirumfix for MySql is '`'.
     * Cirumfix for Sql Server is ''.
     *
     * @var string
     */
    private $circumfix = "";
    private $columns = null;
    private $sql = null;
    private $sqlState = "NOT_SET";

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
        if (!is_array($columns) || empty($columns)) {
            throw new InvalidArgumentException("Columns is not an array or empty.");
        }

        if (!is_string($table) || empty($table)) {
            throw new InvalidArgumentException("Table is not a string or empty.");
        }

        $tempSql = "UPDATE {$this->circumfix}{$table}{$this->circumfix} SET ";

        if($values == null){
            for ($i = 0; $i < count($columns); $i++){
                $tempSql .= $i != 0 ? ", " : " ";
                $tempSql .= "{$this->circumfix}{$columns[$i]}{$this->circumfix} = ?";
            }
        }else if(is_array($values)){
            if(count($values) != count($columns)){
                throw new InvalidArgumentException("The amount of columns and values wasn't the same.");
            }

            for ($i = 0; $i < count($values); $i++){
                $tempSql .= $i != 0 ? ", " : " ";
                $tempSql .= "{$this->circumfix}{$columns[$i]}{$this->circumfix} = '{$values[$i]}'";
            }
        }else{
            throw new InvalidArgumentException("The type of values was incorrect.");
        }

        $this->resetSqlBuilder();
        $this->sql = $tempSql;
        $this->sqlState = "UPDATE_QUERY";
        $this->addWhere($whereCause);
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
        if (!is_array($columns) || empty($columns)) {
            throw new InvalidArgumentException("Columns is not an array or empty.");
        }

        if (!is_string($table) || empty($table)) {
            throw new InvalidArgumentException("Table is not a string or empty.");
        }

        $tempSql = "INSERT INTO {$this->circumfix}{$table}{$this->circumfix} ("; // TODO: The quote is incorrect in sql server. Make it so that the quote is a variable and you can select the what surrounds the table.

        for ($i = 0; $i < count($columns); $i++) {
            $tempSql .= $i != 0
                ? ", {$this->circumfix}{$columns[$i]}{$this->circumfix}"
                : "{$this->circumfix}{$columns[$i]}{$this->circumfix}";
        }

        $tempSql .= ") VALUES ";

        $this->resetSqlBuilder();
        $this->sql = $tempSql;
        $this->columns = $columns;
        $this->sqlState = "INSERT_QUERY";

        try {
            $this->addValues($values);
        } catch (InvalidArgumentException $e) {
            $this->resetSqlBuilder();
            throw $e;
        }
    }

    /**
     * Adds the where cause to the SQL
     * TODO: Improve and and validation.
     *
     * @param $whereCause
     *      The where cause of the sql.
     *      TODO: Make it that you can leave out the where when it is the first time to add the where cause.
     *      TODO: Make it that you can append to an existing where statement.
     */
    public function addWhere($whereCause){
        if($this->getSqlState() != "UPDATE_QUERY"){
            throw new InvalidArgumentException("The sql state was incorrect. Method is not compatible with the current query.");
        }

        $this->sql = $this->getSql() . " {$whereCause}";
    }

    /**
     * Adds more to the values in the insert query.
     *
     * @param $values
     *      If the param is an array, it is expected to have an array in an array with the values.
     *      If the param is an int, it will add the question marks for prepared statements and the value of the param is the amount of values you want.
     *      If the param is null, it will add the question marks for prepared statements, but will only set it once. Same as you put the value 1 in the param.
     */
    public function addValues($values)
    {
        if ($this->sqlState != "INSERT_QUERY") {
            Throw new InvalidArgumentException("The sql state was incorrect. Method is only accessible with insert queries");
        }

        $tempValues = preg_match("/(?<=VALUES\W).+/", $this->sql);
        if ($tempValues == 0) {
            $tempValues = "";
        }

        if (is_array($values)) {
            for ($i = 0; $i < count($values); $i++) {
                if (count($values[$i]) != count($this->columns)) {
                    throw new InvalidArgumentException("The amount of columns and values wasn't the same. Problem occurred with the " . ordinal($i + 1) . " array in values.");
                }

                $tempValues .= empty($tempValues) ? "(" : ", (";

                for ($j = 0; $j < count($values[$i]); $j++) {
                    $tempValues .= $j == 0
                        ? "'{$values[$i][$j]}'"
                        : ", '{$values[$i][$j]}'";
                }

                $tempValues .= ")";
            }
        } else if (is_numeric($values)) {
            if ($values < 1) {
                Throw new InvalidArgumentException("The value is negative. It is expected that it is 1 or higher.");
            }

            for ($i = 0; $i < $values; $i++) {
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

        $this->sql .= $tempValues;
    }

    /**
     * Resets the saved sql query.
     */
    public function resetSqlBuilder()
    {
        $this->sql = null;
        $this->sqlState = "NOT_SET";
        $this->columns = null;
    }

    /**
     * Gets the saved sql query.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
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

    /**
     * Gets the current circumfix.
     *
     * @return string
     */
    public function getCircumfix()
    {
        return $this->circumfix;
    }

    /**
     * Sets the circumfix.
     *
     * @param string $circumfix
     */
    public function setCircumfix($circumfix)
    {
        $this->circumfix = $circumfix;
    }
}
