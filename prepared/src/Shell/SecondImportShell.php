<?php
/**
 * Created by PhpStorm.
 * User: ewb
 * Date: 9/20/17
 * Time: 3:33 PM
 */

namespace App\Shell;

use App\Util\LookupUtil;
use Cake\Console\Shell;
use Cake\Database\Connection;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionManager;

class SecondImportShell extends Shell {
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

    public function second() {
        $this->prepareStatements();
        $this->loadExport();
        $this->populateTable();
        $this->report();
    }

    private function prepareStatements() {
        ini_set('memory_limit', '2048M');
        $this->connection = ConnectionManager::get('default');

        $sql = 'INSERT INTO prepared_statements.second_import 
          (motion_id, waypoint_id, lat, lon, ele, time, distance, feet, seconds, mph, climb) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $this->insert = $this->connection->prepare($sql);

        $sql = 'SELECT id FROM prepared_statements.second_import WHERE time = ? LIMIT 1';
        $this->queryTime = $this->connection->prepare($sql);

        $sql = 'SELECT m.`name` motion, count(m.`name`) count 
          FROM prepared_statements.second_import si
          INNER JOIN prepared_statements.motion m ON m.id = si.motion_id
          GROUP BY m.`name` ORDER BY count(m.`name`)
          LIMIT 10';
        $this->queryMotion = $this->connection->prepare($sql);

        $sql = 'SELECT w.`name` nearest, count(w.`name`) count 
          FROM prepared_statements.second_import si 
          INNER JOIN prepared_statements.waypoint w 
          ON w.id = si.waypoint_id
          GROUP BY w.`name` ORDER BY count(w.`name`)
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
                $this->populateImport($item);
            }
        }
    }

    private function populateImport(array $import) {
        $motionId = LookupUtil::lookup($this->connection, 'motion', $import['motion']);
        $waypointId = LookupUtil::lookup($this->connection, 'waypoint', $import['nearest']);
        $parms = [
            $motionId, $waypointId, $import['lat'], $import['lon'], $import['ele'], $import['time'],
            $import['distance'], $import['feet'], $import['seconds'], $import['mph'], $import['climb'],
        ];
        $this->insert->execute($parms);
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
