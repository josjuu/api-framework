<?php

abstract class DatabaseModel
{
    /**
     * Variable that contains all the property names of the fields that can be null in the database.
     *
     * @var array
     */
    private $nullableFields = array();
    /**
     * Variable that contains arrays with as first item the property name and as second item the database type of the property.
     *
     * @var array
     */
    private $databaseType = array();
    /**
     * The name of the table in the database.
     *
     * @var string
     */
    private $tableName = null;

    /**
     * DatabaseModel constructor.
     *
     * @param array $nullableFields
     *      Variable that contains all the property names of the fields that can be null in the database.
     * @param array $databaseType
     *      Variable that stores what type the property is in the database.
     *      The array contains an array with as first item the property name and as second item the database type of the property.
     * @param string $tableName
     *      The name of the table in the database.
     */
    public function __construct(array $nullableFields = null, array $databaseType = null, string $tableName = null)
    {
        $this->nullableFields = $nullableFields;
        $this->databaseType = $databaseType;
        $this->tableName = $tableName;

        if ($this->tableName == null && trim($this->tableName) == "") {
            throw new InvalidArgumentException("Table name was not set in the constructor.");
        }
    }

    // <editor-folds desc="Getters of private fields">

    public function getDatabaseType()
    {
        return $this->databaseType;
    }

    public function getNullableFields()
    {
        return $this->nullableFields;
    }
    // </editor-folds>
}