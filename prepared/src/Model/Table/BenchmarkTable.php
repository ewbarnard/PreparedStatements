<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Benchmark Model
 *
 * @method \App\Model\Entity\Benchmark get($primaryKey, $options = [])
 * @method \App\Model\Entity\Benchmark newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Benchmark[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Benchmark|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Benchmark patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Benchmark[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Benchmark findOrCreate($search, callable $callback = null, $options = [])
 */
class BenchmarkTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('benchmark');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->scalar('motion')
            ->requirePresence('motion', 'create')
            ->notEmpty('motion');

        $validator
            ->decimal('lat')
            ->requirePresence('lat', 'create')
            ->notEmpty('lat');

        $validator
            ->decimal('lon')
            ->requirePresence('lon', 'create')
            ->notEmpty('lon');

        $validator
            ->decimal('ele')
            ->requirePresence('ele', 'create')
            ->notEmpty('ele');

        $validator
            ->dateTime('time')
            ->requirePresence('time', 'create')
            ->notEmpty('time');

        $validator
            ->scalar('nearest')
            ->requirePresence('nearest', 'create')
            ->notEmpty('nearest');

        $validator
            ->integer('distance')
            ->requirePresence('distance', 'create')
            ->notEmpty('distance');

        $validator
            ->integer('feet')
            ->requirePresence('feet', 'create')
            ->notEmpty('feet');

        $validator
            ->integer('seconds')
            ->requirePresence('seconds', 'create')
            ->notEmpty('seconds');

        $validator
            ->decimal('mph')
            ->requirePresence('mph', 'create')
            ->notEmpty('mph');

        $validator
            ->decimal('climb')
            ->requirePresence('climb', 'create')
            ->notEmpty('climb');

        return $validator;
    }
}
