<?php
/**
 * Created by PhpStorm.
 * User: ewb
 * Date: 9/18/17
 * Time: 3:07 PM
 */

namespace App\Shell;

use Cake\Console\Shell;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Database\StatementInterface;

class BenchmarkShell extends Shell {
    private static $path = '../Exports/public.csv';

    private static $passes = 5;

    /** @var Connection */
    private $connection;

    /** @var StatementInterface */
    private $insert;

    /** @var StatementInterface */
    private $resultInsert;

    /** @var StatementInterface */
    private $resultQuery;

    private $header = [];

    private $lines = [];

    private $import = [];

    private $count = 0.0;

    private $begin = 0.0;

    /** @noinspection PhpHierarchyChecksInspection */

    /**
     * @throws \Cake\Datasource\Exception\MissingDatasourceConfigException
     */
    public function main() {
        $this->prepareStatements();
        $this->loadExport();

        $passes = 0;
        while ($passes++ < static::$passes) {
            $this->benchmark();
        }

        $this->report();
        return 0;
    }

    private function report() {
        $this->resultQuery->execute([]);
        $rows = $this->resultQuery->fetchAll('assoc');
        $this->verbose(sprintf('%-20s %-10s %-20s %-10s',
            'Method', 'Seconds', 'Rows Per Second', 'Gain'));
        foreach ($rows as $row) {
            $this->verbose(sprintf('%-20s %-10s %-20s %-10s',
                $row['method'], $row['seconds'], $row['rows_per_second'], $row['gain']));
        }
    }

    private function prepareStatements() {
        ini_set('memory_limit', '2048M');
        $this->connection = ConnectionManager::get('default');

        $sql = 'INSERT INTO prepared_statements.result 
          (method, rows, seconds, rows_per_second) VALUES (?, ?, ?, ?)';
        $this->resultInsert = $this->connection->prepare($sql);

        $sql = 'SELECT method, format(AVG(seconds), 2) seconds, 
            format(avg(rows/seconds), 2) rows_per_second,
            concat(format(avg(rows/seconds)/
            (SELECT avg(rows/seconds) FROM result 
            WHERE method = \'modelSave\'), 1), \'X\') gain
            FROM prepared_statements.result 
            GROUP BY method 
            ORDER BY avg(rows_per_second)
            LIMIT 10';
        $this->resultQuery = $this->connection->prepare($sql);

        $this->connection->query('truncate table prepared_statements.result');
    }

    private function loadExport() {
        $page = file_get_contents(static::$path);
        $this->lines = explode("\n", $page);
        foreach ($this->lines as $line) {
            if ($line !== '') {
                if (!count($this->header)) {
                    $this->header = str_getcsv($line);
                } else {
                    $this->import[] = array_combine($this->header, str_getcsv($line));
                }
            }
        }
        $this->count = (float)count($this->import);
    }

    private function benchmark() {
        $this->modelSave();
        $this->modelSaveMany();
        $this->bulkInsertSingle();
        $this->preparedSingle();
        $this->preparedBulk();
    }

    private function modelSave() {
        $this->beginRun();

        $table = TableRegistry::get('Benchmark');
        foreach ($this->import as $item) {
            $entity = $table->newEntity($item);
            $table->save($entity);
        }
        $this->flushTables();

        $this->endRun(__FUNCTION__);
    }

    private function beginRun() {
        $this->truncate();
        $this->begin = microtime(true);
    }

    private function truncate() {
        $this->connection->query('truncate table prepared_statements.benchmark');
    }

    private function flushTables() {
        $this->connection->query('flush tables prepared_statements.benchmark');
    }

    private function endRun($caller) {
        $interval = microtime(true) - $this->begin;
        $per = sprintf('%.3f', $this->count / $interval);
        $elapsed = sprintf('%.6f', $interval);
        $this->resultInsert->execute([$caller, (int)$this->count, $elapsed, $per]);
    }

    private function modelSaveMany() {
        $this->beginRun();

        $table = TableRegistry::get('Benchmark');
        $entities = $table->newEntities($this->import);
        $table->saveMany($entities);
        $this->flushTables();

        $this->endRun(__FUNCTION__);
    }

    private function bulkInsertSingle() {
        $this->beginRun();

        $table = TableRegistry::get('Benchmark');
        $query = $table->query();
        $query->insert(array_keys($this->import[0]));
        foreach ($this->import as $item) {
            $query->values($item);
        }
        $query->execute();
        $this->flushTables();

        $this->endRun(__FUNCTION__);
    }

    private function preparedSingle() {
        $this->beginRun();

        $sql = 'INSERT INTO prepared_statements.benchmark 
          (motion, lat, lon, ele, time, nearest, distance, feet, seconds, mph, climb) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $this->insert = $this->connection->prepare($sql);

        foreach ($this->import as $item) {
            $this->insert->execute(array_values($item));
        }
        $this->flushTables();

        $this->endRun(__FUNCTION__);
    }

    private function preparedBulk() {
        $this->beginRun();

        $sql = 'INSERT INTO prepared_statements.benchmark 
          (motion, lat, lon, ele, time, nearest, distance, feet, seconds, mph, climb) 
          VALUES ';
        $rows = [];
        foreach ($this->import as $item) {
            $row = [];
            foreach ($item as $value) {
                $row[] = "'$value'";
            }
            $rows[] = '(' . implode(',', $row) . ')';
        }
        $sql .= implode(',', $rows);
        $this->connection->query($sql);
        $this->flushTables();

        $this->endRun(__FUNCTION__);
    }
}
