<?php
/**
 * @package framework
 * @subpackage search
 */

/**
 * Selects textual content with an exact match between columnname and keyword.
 *
 * @todo case sensitivity switch
 * @todo documentation
 * 
 * @package framework
 * @subpackage search
 */
class DataObjectAsPageMatchAllFilter extends ExactMatchFilter {
	/**
	 * Applies an exact match (equals) on a field value against multiple
	 * possible values.
	 *
	 * @return DataQuery
	 */
	protected function applyMany(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$modifiers = $this->getModifiers();
		$values = array();
		foreach($this->getValue() as $value) {
			$values[] = Convert::raw2sql($value);
		}

		$CategoryModel = $this->model;
		
		$this->setModel("DataObjectAsPage");

		$match = array();

		foreach($values as &$v) {
			$match[] = sprintf(
				"%s IN (
				   SELECT " . $query->dataClass() ."ID
				   FROM `" . $query->dataClass() . "_" . $this->relation[0] ."`
				   WHERE " . $CategoryModel ."ID = '%s'
				   GROUP BY " . $query->dataClass() ."ID
				)",
				$this->getDbName(),
				$v
			);
		}
		
		$where = implode(' AND ', $match);

		return $query->where($where);
	}
}
