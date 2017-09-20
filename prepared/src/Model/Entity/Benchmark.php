<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Benchmark Entity
 *
 * @property int $id
 * @property string $motion
 * @property float $lat
 * @property float $lon
 * @property float $ele
 * @property \Cake\I18n\FrozenTime $time
 * @property string $nearest
 * @property int $distance
 * @property int $feet
 * @property int $seconds
 * @property float $mph
 * @property float $climb
 */
class Benchmark extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'motion' => true,
        'lat' => true,
        'lon' => true,
        'ele' => true,
        'time' => true,
        'nearest' => true,
        'distance' => true,
        'feet' => true,
        'seconds' => true,
        'mph' => true,
        'climb' => true
    ];
}
