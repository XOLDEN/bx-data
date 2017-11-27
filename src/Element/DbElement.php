<?php
namespace Akop\Element;

/**
 * Класс для работы с данными из highload блоков
 * Предпочтительно наследовать класс с указанием $entityName
 * @author Андрей Копылов aakopylov@mail.ru
 */
class DbElement extends AbstractElement
{
    protected $tableName = "";
    protected $fieldsBase = [
        "id" => "id",
    ];


    public function __construct()
    {
        // global $DB;
        // $this->db = $DB;
        $this->connection = \Bitrix\Main\Application::getConnection();
        parent::__construct();
        $this->sqlHelper = $this->connection->getSqlHelper();
        // return $this;
    }


    public function getList(array $params = array())
    {
        parent::getList($params);

        $querySet = new QuerySet($this->tableName, $this->primaryKey);
        $querySet->addSelect($this->params['select']);
        $querySet->addFilter($this->params['filter']);
        $querySet->addOrder($this->params['order']);
        $querySet->setLimit($this->params['limit']);

        $list = $this->connection->query($querySet->getSelectSQL(array_keys($this->reversedFields)));
        while ($item = $list->fetch()) {
            $result[] = $this->getRenamed($item);
        }
        return $result;
    }

    public function add(array $params)
    {
        parent::add($params);
        $querySet = new QuerySet($this->tableName);
        $this->prepareParams();
        $this->connection->queryExecute($querySet->getAddSQL($this->params));
        return ($this->connection->getAffectedRowsCount() > 0);
    }

    public function delete($primaryKey)
    {
        // parent::delete($primaryKey);
        $querySet = new QuerySet($this->tableName, $this->primaryKey);
        $this->connection->queryExecute($querySet->getDeleteSQL($primaryKey));
        return ($this->connection->getAffectedRowsCount() > 0);
    }

    public function update($primaryKey, array $params)
    {
        parent::update($primaryKey, $params);
        $querySet = new QuerySet($this->tableName, $this->primaryKey);
        $this->prepareParams();
        $this->connection->queryExecute($querySet->getUpdateSQL($primaryKey, $this->params));
        return ($this->connection->getAffectedRowsCount() > 0);
    }

    public function getMap()
    {
        $result = $this->fieldsBase;

        $userFields = $this->connection->getTableFields($this->tableName);
        // \Akop\Util::pre($userFields, '$userFields');
        // return;
        foreach ($userFields as $fieldName => $field) {
            $alias = \Akop\Util::camelize($fieldName);
            $result[$alias] = $fieldName;
        }

        return $result;
    }

    public function query($sql)
    {
        $list = $this->connection->query($sql);
        while ($item = $list->fetch()) {
            $result[] = $item;
        }
        return $result;
    }

    public function queryExecute($sql)
    {
        return $this->connection->queryExecute($sql);
    }


    private function prepareParams()
    {
        global $DB;
        $connect = $DB->db_Conn;
        if (!\is_array($this->params) || !\is_object($connect) || !(\get_class($connect) === 'mysqli')) {
            return;
        }
        foreach ($this->params as &$value) {
            $value = \mysqli_real_escape_string($connect, $value);
        }
    }
}
