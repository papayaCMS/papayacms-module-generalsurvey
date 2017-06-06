<?php
/**
* Database access for survey subject record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey subject record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveySubjectList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'subject_id' => 'id',
    'subject_parent_id' => 'parent_id',
    'survey_id' => 'survey_id',
    'subject_name' => 'name'
  );

  /**
  * Load records from the database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = array(), $limit = NULL, $offset = NULL) {
    $sql = "SELECT subject_id, subject_parent_id, survey_id, subject_name
              FROM %s";
    $conditions = array();
    if (is_array($filter) && !empty($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'parent_id', 'survey_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY survey_id, subject_parent_id, subject_name";
    $parameters = array($this->databaseGetTableName('general_survey_subject'));
    return $this->_loadRecords($sql, $parameters, 'subject_id', $limit, $offset);
  }

  /**
  * Delete a record from the database
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($id) {
    $result = FALSE;
    $deleted = (
      FALSE !== $this->databaseDeleteRecord(
        $this->databaseGetTableName('general_survey_subject'),
        'subject_id',
        $id
      )
    );
    if ($deleted) {
      if (isset($this->_records[$id])) {
        unset($this->_records[$id]);
      }
      $result = TRUE;
    }
    return $result;
  }

  /**
  * Get absoulute count of last query
  *
  * @return integer
  */
  public function getAbsCount() {
    return $this->_recordCount;
  }
}
