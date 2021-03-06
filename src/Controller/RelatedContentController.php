<?php
namespace RelatedContent\Controller;

use Cake\Cache\Cache;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use RelatedContent\Model\Behavior\InRelatedIndexBehavior;

class RelatedContentController extends AppController {

	/**
	 * Vrátí názvy odpovídající požadavku (včetně vynechaných znaků)
	 *
	 * @param bool|string $tables_to_get omezit výběr na určité tabulky, serializováno
	 */
	public function search($tables_to_get = false) {
		if ($tables_to_get) {
			$tables_to_get = unserialize($tables_to_get);
		}
		if (!$this->request->is('ajax')) {
			throw new BadRequestException;
		}
		$query = $this->request->query['term'];

		$this->autoRender = false;
		$indexed_tables = $this->_getIndexedTables();

		$array = [];
		foreach ($indexed_tables as $model => $indexed_table) {
			if (!$tables_to_get || in_array($model, $tables_to_get)) {
				foreach ($indexed_table as $key => $value) {
					$regexp = implode('.*?', str_split($query));
					$regexp = '/' . $regexp . '/i';
					if (preg_match($regexp, $value)) {
						$array[] = [
							'key' => $key,
							'value' => $value,
							'model' => $model,
							'table' => Inflector::underscore($model),
						];
					}
				}
			}

		}

		echo json_encode($array);
	}

	/**
	 * @return mixed
	 */
	protected function _getIndexedTables() {
		if (($indexed_tables = Cache::read('Related.indexedTables')) === false) {
			$InRelatedIndexBehavior = new InRelatedIndexBehavior(TableRegistry::get(''));
			$InRelatedIndexBehavior->refreshCache();
			$indexed_tables = Cache::read('Related.indexedTables');
		}

		return $indexed_tables;
	}

	public function getActionName($controller, $id) {
		$this->autoRender = false;
		$indexed_tables = $this->_getIndexedTables();
		if (isset($indexed_tables[$controller][$id])) {
			echo $indexed_tables[$controller][$id] . ' (' . __(Inflector::humanize(Inflector::underscore($controller))) . ' / ' . $id . ')';
		}
	}
}
