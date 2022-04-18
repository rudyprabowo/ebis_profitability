<?php
namespace Core\SaveHandler\Session;

use Laminas\Session\Exception;
use Laminas\Stdlib\AbstractOptions;

class MainSaveHandlerOptions extends AbstractOptions
{
    /**
     * ID Column
     * @var string
     */
    protected $idColumn = 'id';

    /**
     * Name Column
     * @var string
     */
    protected $nameColumn = 'name';

    /**
     * Data Column
     * @var string
     */
    protected $dataColumn = 'data';

    /**
     * Lifetime Column
     * @var string
     */
    protected $lifetimeColumn = 'lifetime';

    /**
     * Modified Column
     * @var string
     */
    protected $modifiedColumn = 'modified';

    /**
     * UserAgent Column
     * @var string
     */
    protected $uagColumn = 'uag';

    /**
     * UserID Column
     * @var string
     */
    protected $uidColumn = 'uid';

    /**
     * ipaddress Column
     * @var string
     */
    protected $ipColumn = 'ip';

    /**
     * Set Id Column
     *
     * @param string $idColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setIdColumn($idColumn)
    {
        $me = $this;
        $idColumn = (string) $idColumn;
        if (strlen($idColumn) === 0) {
            throw new Exception\InvalidArgumentException('$idColumn must be a non-empty string');
        }
        $me->idColumn = $idColumn;
        return $this;
    }

    /**
     * Get Id Column
     *
     * @return string
     */
    public function getIdColumn()
    {
        $me = $this;
        return $me->idColumn;
    }

    /**
     * Set Name Column
     *
     * @param string $nameColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setNameColumn($nameColumn)
    {
        $me = $this;
        $nameColumn = (string) $nameColumn;
        if (strlen($nameColumn) === 0) {
            throw new Exception\InvalidArgumentException('$nameColumn must be a non-empty string');
        }
        $me->nameColumn = $nameColumn;
        return $this;
    }

    /**
     * Get Name Column
     *
     * @return string
     */
    public function getNameColumn()
    {
        $me = $this;
        return $me->nameColumn;
    }

    /**
     * Set Data Column
     *
     * @param string $dataColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setDataColumn($dataColumn)
    {
        $me = $this;
        $dataColumn = (string) $dataColumn;
        if (strlen($dataColumn) === 0) {
            throw new Exception\InvalidArgumentException('$dataColumn must be a non-empty string');
        }
        $me->dataColumn = $dataColumn;
        return $this;
    }

    /**
     * Get Data Column
     *
     * @return string
     */
    public function getDataColumn()
    {
        $me = $this;
        return $me->dataColumn;
    }

    /**
     * Set Lifetime Column
     *
     * @param string $lifetimeColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setLifetimeColumn($lifetimeColumn)
    {
        $me = $this;
        $lifetimeColumn = (string) $lifetimeColumn;
        if (strlen($lifetimeColumn) === 0) {
            throw new Exception\InvalidArgumentException('$lifetimeColumn must be a non-empty string');
        }
        $me->lifetimeColumn = $lifetimeColumn;
        return $this;
    }

    /**
     * Get Lifetime Column
     *
     * @return string
     */
    public function getLifetimeColumn()
    {
        $me = $this;
        return $me->lifetimeColumn;
    }

    /**
     * Set Modified Column
     *
     * @param string $modifiedColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setModifiedColumn($modifiedColumn)
    {
        $me = $this;
        $modifiedColumn = (string) $modifiedColumn;
        if (strlen($modifiedColumn) === 0) {
            throw new Exception\InvalidArgumentException('$modifiedColumn must be a non-empty string');
        }
        $me->modifiedColumn = $modifiedColumn;
        return $this;
    }

    /**
     * Get Modified Column
     *
     * @return string
     */
    public function getModifiedColumn()
    {
        $me = $this;
        return $me->modifiedColumn;
    }

    /**
     * Set User ID Column
     *
     * @param string $uidColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setUIDColumn($uidColumn)
    {
        $me = $this;
        $uidColumn = (string) $uidColumn;
        if (strlen($uidColumn) === 0) {
            throw new Exception\InvalidArgumentException('$uidColumn must be a non-empty string');
        }
        $me->uidColumn = $uidColumn;
        return $this;
    }

    /**
     * Get User ID Column
     *
     * @return string
     */
    public function getUIDColumn()
    {
        $me = $this;
        return $me->uidColumn;
    }

    /**
     * Set User Agent Column
     *
     * @param string $uagColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setUagColumn($uagColumn)
    {
        $me = $this;
        $uagColumn = (string) $uagColumn;
        if (strlen($uagColumn) === 0) {
            throw new Exception\InvalidArgumentException('$uagColumn must be a non-empty string');
        }
        $me->uagColumn = $uagColumn;
        return $this;
    }

    /**
     * Get User Agent Column
     *
     * @return string
     */
    public function getUagColumn()
    {
        $me = $this;
        return $me->uagColumn;
    }

    /**
     * Set IP Address Column
     *
     * @param string $modifiedColumn
     * @return MainSaveHandlerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function setIPColumn($ipColumn)
    {
        $me = $this;
        $ipColumn = (string) $ipColumn;
        if (strlen($ipColumn) === 0) {
            throw new Exception\InvalidArgumentException('$ipColumn must be a non-empty string');
        }
        $me->ipColumn = $ipColumn;
        return $this;
    }

    /**
     * Get IP Address Column
     *
     * @return string
     */
    public function getIPColumn()
    {
        $me = $this;
        return $me->ipColumn;
    }
}