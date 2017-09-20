<?php
/**
 * Created by PhpStorm.
 * User: ewb
 * Date: 9/20/17
 * Time: 1:32 PM
 */

namespace App\Shell;

use Cake\Console\Shell;
use Cake\Database\Connection;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;

class FirstImportShell extends Shell {
    private static $path = '../Exports/public.csv';

    /** @var Connection */
    private $connection;

    /** @var StatementInterface */
    private $insert;

    /** @var StatementInterface */
    private $queryMotion;

    /** @var StatementInterface */
    private $queryNearest;

    /** @var StatementInterface */
    private $queryTime;

    private $import = [];

    public function import() {
        $this->prepareStatements();
        $this->loadExport();
        $this->populateTable();
        $this->report();

        $this->verbose(PHP_EOL . 'Done.', 2);
    }

    private function prepareStatements() {
        ini_set('memory_limit', '2048M');
        $this->connection = ConnectionManager::get('default');

        $sql = 'INSERT INTO prepared_statements.first_import 
          (motion, lat, lon, ele, time, nearest, distance, feet, seconds, mph, climb) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $this->insert = $this->connection->prepare($sql);

        $sql = 'SELECT id FROM prepared_statements.first_import WHERE time = ? LIMIT 1';
        $this->queryTime = $this->connection->prepare($sql);

        $sql = 'SELECT motion, count(motion) count
          FROM prepared_statements.first_import 
          GROUP BY motion 
          ORDER BY count(motion) 
          LIMIT 10';
        $this->queryMotion = $this->connection->prepare($sql);

        $sql = 'SELECT nearest, count(nearest) count
          FROM prepared_statements.first_import 
          GROUP BY nearest 
          ORDER BY count(nearest) 
          LIMIT 100';
        $this->queryNearest = $this->connection->prepare($sql);
    }

    private function loadExport() {
        $page = file_get_contents(static::$path);
        $lines = explode("\n", $page);
        $header = [];
        foreach ($lines as $line) {
            if ($line !== '') {
                if (!count($header)) {
                    $header = str_getcsv($line);
                } else {
                    $this->import[] = array_combine($header, str_getcsv($line));
                }
            }
        }
        $this->verbose('Loaded export (CSV) file');
    }

    private function populateTable() {
        foreach ($this->import as $item) {
            $this->queryTime->execute([$item['time']]);
            $row = $this->queryTime->fetch('assoc');
            if (!(is_array($row) && array_key_exists('id', $row))) {
                $this->insert->execute(array_values($item));
            }
        }
    }

    private function report() {
        $this->verbose(PHP_EOL . 'Motion Counts:');
        $this->queryMotion->execute([]);
        $rows = $this->queryMotion->fetchAll('assoc');
        foreach ($rows as $row) {
            $line = sprintf('%-10s %-8s', $row['motion'], $row['count']);
            $this->verbose($line);
        }

        $this->verbose(PHP_EOL . 'Nearest-Waypoint Counts:');
        $this->queryNearest->execute([]);
        $rows = $this->queryNearest->fetchAll('assoc');
        foreach ($rows as $row) {
            $line = sprintf('%-50s %-8s', $row['nearest'], $row['count']);
            $this->verbose($line);
        }
    }
}
