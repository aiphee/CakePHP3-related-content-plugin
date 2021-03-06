<?php

namespace RelatedContent\Model\Behavior;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Class HasRelatedBehavior
 * @package RelatedContent\Model\Behavior
 *
 * Init options:
 *        active_field            - name of field which says if item is active, if present only those with active set as true will be in index
 *
 */
class InRelatedIndexBehavior extends Behavior {

	/**
	 * Vrátí pole s názvy tabulek které mají připnuté chování, po přidání chování je třeba mazat cache
	 * Return array with table names with attached behavior, cache needs to be cleared after adding new
	 *
	 * @return array|mixed
	 */
	public function getTablesWithBehaviorNames() {
		if (($attachedTables = Cache::read('Related.attachedTables')) === false) { //Nepodařilo se načíst z cache
			$attachedTables = $this->getAttachedTables();
			$attachedTables = array_map(function ($model) {
				return $model->table();
			}, $attachedTables);
			Cache::write('Related.attachedTables', $attachedTables);
		}

		return $attachedTables;
	}


	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$this->_tableChanged($event, $entity, $options);
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		$this->_tableChanged($event, $entity, $options);
	}

	/***
	 * Při změně tabulky obnovit index pro tento model
	 * When the table gets changed, refresh it`s index in array
	 *
	 * @param $event
	 * @param $entity
	 * @param $options
	 *
	 * @return bool
	 */
	protected function _tableChanged($event, $entity, $options) {
		$modelName = $this->_table->alias();

		if (($indexedTables = Cache::read('Related.indexedTables')) === false) { //Nepodařilo se načíst z cache
			$this->refreshCache();
		} else { //Načteno z cache
			$indexedTables[$modelName] = $this->_table->find('list', ['only_active' => true])->toArray();
			Cache::write('Related.indexedTables', $indexedTables);
		}

		echo "";

		return true;
	}

	public function refreshCache() {
		$indexedTables = $this->_getListForAllAttachedModels();
		Cache::write('Related.indexedTables', $indexedTables);
	}

	/***
	 * Najde všechny modely které mají připnuté tohle chování a přegeneruje jim index
	 * Refreshes cache for all models with attached behavior
	 */
	protected function _getListForAllAttachedModels() {
		$indexed_tables = [];
		$tablesWithBehavior = $this->getAttachedTables();

		/** @var \Cake\ORM\Table $table */
		foreach ($tablesWithBehavior as $table) {
			$indexed_tables[$table->alias()] = $table->find('list', ['only_active' => true])->toArray();
		}

		return $indexed_tables;
	}

	/**
	 * Vrátí tabulky s chováním, které se mají indexovat
	 * Return tables with behavior which should be indexed
	 *
	 *
	 * @param bool $indexedByName Pass true if names should be returned instead of Tables
	 *
	 * @return array
	 */
	public function getAttachedTables($indexedByName = false) {

		$modelPath = 'Model' . DS . 'Table';
		$modelPath = APP . $modelPath;

		$tables = [];
		foreach ((new Folder($modelPath))->find('.*.php') as $file) {
			$table = str_replace('Table.php', '', $file);
			if (TableRegistry::exists($table)) {
				TableRegistry::remove($table);
			}
			$tableTable = TableRegistry::get($table, ['options' => ['skipSimilarInitialize' => true]]);

			if ($tableTable->hasBehavior('InRelatedIndex')) {
				if ($indexedByName) {
					$tables[$table] = $tableTable;
				} else {
					$tables[] = $tableTable;
				}
			}
		}

		return $tables;
	}
}
