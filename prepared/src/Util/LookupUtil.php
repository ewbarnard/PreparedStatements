<?php
/**
 * Created by PhpStorm.
 * User: ewb
 * Date: 9/20/17
 * Time: 3:07 PM
 */

namespace App\Util;

use Cake\Database\Connection;
use Cake\Database\StatementInterface;

final class LookupUtil {
    /** @var int Maximum cached IDs */
    private static $cacheLimit = 200;

    /** @var array One singleton per table */
    private static $instances = [];

    /** @var Connection */
    private $connection;

    /** @var StatementInterface */
    private $query;

    /** @var StatementInterface */
    private $insert;

    /** @var string Table name */
    private $table;

    /** @var array Cache of IDs given name */
    private $cache = [];

    private function __construct(Connection $connection, $table, array $dependencies) {
        $this->connection = $connection;
        $this->table = $table;
        if (count($dependencies)) {
            $this->injectDependencies($dependencies);
        }
        $this->prepareStatements();
    }

    /**
     * Testing support
     *
     * @param array $dependencies
     * @return void
     */
    private function injectDependencies(array $dependencies) {
        foreach ($dependencies as $key => $value) {
            if (property_exists(static::class, $key)) {
                $this->$key = $value;
            }
        }
    }

    private function prepareStatements() {
        if (!$this->query) {
            /** @noinspection SqlResolve */
            $sql = 'SELECT id FROM prepared_statements.' . $this->table . ' WHERE `name` = ?';
            $this->query = $this->connection->prepare($sql);
        }
        if (!$this->insert) {
            /** @noinspection SqlResolve */
            $sql = 'INSERT INTO prepared_statements.' . $this->table . ' (`name`) VALUES (?)';
            $this->insert = $this->connection->prepare($sql);
        }
    }

    public static function lookup(Connection $connection, $table, $value) {
        $instance = static::getInstance($connection, $table);
        return array_key_exists($value, $instance->cache) ?
            $instance->cache[$value] : $instance->runLookup($value);
    }

    /**
     * @param Connection $connection
     * @param string $table
     * @param array $dependencies
     * @return LookupUtil
     */
    public static function getInstance(Connection $connection, $table, array $dependencies = []) {
        if (!array_key_exists($table, static::$instances)) {
            static::$instances[$table] = new static($connection, $table, $dependencies);
        }
        return static::$instances[$table];
    }

    private function runLookup($value) {
        if (count($this->cache) >= static::$cacheLimit) {
            $this->cache = []; // Cache got too big; clear and start over
        }
        if (!$this->query) {
            // Should only happen when developing unit tests
            throw new \InvalidArgumentException('No query for ' . $this->table);
        }
        $parms = [substr($value, 0, 255)];
        $this->query->execute($parms);
        $row = $this->query->fetch('assoc');
        if (is_array($row) && array_key_exists('id', $row)) {
            $id = (int)$row['id'];
        } else {
            $this->insert->execute($parms);
            $id = (int)$this->insert->lastInsertId();
        }
        $this->cache[$value] = $id;
        return $id;
    }
}
