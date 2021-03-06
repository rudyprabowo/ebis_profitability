<?php

namespace Laminas\Db\Metadata\Object;

class ConstraintObject
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $tableName;

    /** @var string */
    protected $schemaName;

    /**
     * One of "PRIMARY KEY", "UNIQUE", "FOREIGN KEY", or "CHECK"
     *
     * @var string
     */
    protected $type;

    /** @var string[] */
    protected $columns = [];

    /** @var string */
    protected $referencedTableSchema;

    /** @var string */
    protected $referencedTableName;

    /** @var string[] */
    protected $referencedColumns;

    /** @var string */
    protected $matchOption;

    /** @var string */
    protected $updateRule;

    /** @var string */
    protected $deleteRule;

    /** @var string */
    protected $checkClause;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $tableName
     * @param string $schemaName
     */
    public function __construct($name, $tableName, $schemaName = null)
    {
        $this->setName($name);
        $this->setTableName($tableName);
        $this->setSchemaName($schemaName);
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set schema name
     *
     * @param string $schemaName
     */
    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * Get schema name
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Set table name
     *
     * @param  string $tableName
     * @return self Provides a fluent interface
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /** @return bool */
    public function hasColumns()
    {
        return ! empty($this->columns);
    }

    /**
     * Get Columns.
     *
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set Columns.
     *
     * @param string[] $columns
     * @return self Provides a fluent interface
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get Referenced Table Schema.
     *
     * @return string
     */
    public function getReferencedTableSchema()
    {
        return $this->referencedTableSchema;
    }

    /**
     * Set Referenced Table Schema.
     *
     * @param string $referencedTableSchema
     * @return self Provides a fluent interface
     */
    public function setReferencedTableSchema($referencedTableSchema)
    {
        $this->referencedTableSchema = $referencedTableSchema;
        return $this;
    }

    /**
     * Get Referenced Table Name.
     *
     * @return string
     */
    public function getReferencedTableName()
    {
        return $this->referencedTableName;
    }

    /**
     * Set Referenced Table Name.
     *
     * @param string $referencedTableName
     * @return self Provides a fluent interface
     */
    public function setReferencedTableName($referencedTableName)
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * Get Referenced Columns.
     *
     * @return string[]
     */
    public function getReferencedColumns()
    {
        return $this->referencedColumns;
    }

    /**
     * Set Referenced Columns.
     *
     * @param string[] $referencedColumns
     * @return self Provides a fluent interface
     */
    public function setReferencedColumns(array $referencedColumns)
    {
        $this->referencedColumns = $referencedColumns;
        return $this;
    }

    /**
     * Get Match Option.
     *
     * @return string
     */
    public function getMatchOption()
    {
        return $this->matchOption;
    }

    /**
     * Set Match Option.
     *
     * @param string $matchOption
     * @return self Provides a fluent interface
     */
    public function setMatchOption($matchOption)
    {
        $this->matchOption = $matchOption;
        return $this;
    }

    /**
     * Get Update Rule.
     *
     * @return string
     */
    public function getUpdateRule()
    {
        return $this->updateRule;
    }

    /**
     * Set Update Rule.
     *
     * @param string $updateRule
     * @return self Provides a fluent interface
     */
    public function setUpdateRule($updateRule)
    {
        $this->updateRule = $updateRule;
        return $this;
    }

    /**
     * Get Delete Rule.
     *
     * @return string
     */
    public function getDeleteRule()
    {
        return $this->deleteRule;
    }

    /**
     * Set Delete Rule.
     *
     * @param string $deleteRule
     * @return self Provides a fluent interface
     */
    public function setDeleteRule($deleteRule)
    {
        $this->deleteRule = $deleteRule;
        return $this;
    }

    /**
     * Get Check Clause.
     *
     * @return string
     */
    public function getCheckClause()
    {
        return $this->checkClause;
    }

    /**
     * Set Check Clause.
     *
     * @param string $checkClause
     * @return self Provides a fluent interface
     */
    public function setCheckClause($checkClause)
    {
        $this->checkClause = $checkClause;
        return $this;
    }

    /**
     * Is primary key
     *
     * @return bool
     */
    public function isPrimaryKey()
    {
        return 'PRIMARY KEY' === $this->type;
    }

    /**
     * Is unique key
     *
     * @return bool
     */
    public function isUnique()
    {
        return 'UNIQUE' === $this->type;
    }

    /**
     * Is foreign key
     *
     * @return bool
     */
    public function isForeignKey()
    {
        return 'FOREIGN KEY' === $this->type;
    }

    /**
     * Is foreign key
     *
     * @return bool
     */
    public function isCheck()
    {
        return 'CHECK' === $this->type;
    }
}
