<?php
/**
* Database access for survey result record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey result record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyResultList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'answer_id' => 'answer_id',
    'subject_id' => 'subject_id',
    'result_count' => 'count'
  );

  /**
  * Load records from database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  */
  public function load($filter = array(), $limit = NULL, $offset = NULL) {
    $sql = "SELECT answer_id, subject_id, result_count
              FROM %s";
    $conditions = array();
    if (!empty($filter)) {
      foreach ($filter as $field => $value) {
        if (in_array($field, array('answer_id', 'subject_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($field, $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY answer_id, subject_id";
    $parameters = array($this->databaseGetTableName('general_survey_result'));
    return $this->_loadRecords($sql, $parameters, NULL, $limit, $offset);
  }

  /**
  * Delete records from the database
  *
  * @param integer|array $filter
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($filter) {
    if (is_numeric($filter)) {
      $filter = array('answer_id' => (int)$filter);
    }
    $sanitizedFilter = array();
    foreach ($filter as $field => $value) {
      if (in_array($field, array('answer_id', 'subject_id'))) {
        $sanitizedFilter[$field] = $value;
      }
    }
    $result = FALSE;
    if (!empty($sanitizedFilter)) {
      $deleted = (
        FALSE !== $this->databaseDeleteRecord(
          $this->databaseGetTableName('general_survey_result'),
          $sanitizedFilter
        )
      );
      if ($deleted) {
        foreach ($this->_records as $id => $data) {
          if (isset($sanitizedFilter['answer_id']) && isset($sanitizedFilter['subject_id'])) {
            if ($data['answer_id'] == $sanitizedFilter['answer_id'] &&
                $data['subject_id'] == $sanitizedFilter['subject_id']) {
              unset($this->_records[$id]);
            }
          } elseif (isset($sanitizedFilter['answer_id'])) {
            if ($data['answer_id'] == $sanitizedFilter['answer_id']) {
              unset($this->_records[$id]);
            }
          } elseif (isset($sanitizedFilter['subject_id'])) {
            if ($data['subject_id'] == $sanitizedFilter['subject_id']) {
              unset($this->_records[$id]);
            }
          }
        }
        $result = TRUE;
      }
    }
    return $result;
  }
}
