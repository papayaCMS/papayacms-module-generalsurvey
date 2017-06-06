<?php
/**
* Database access for survey user-to-subject record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey user-to-subject record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyUserSubjectList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'user_id' => 'user_id',
    'subject_id' => 'subject_id'
  );

  /**
  * Load records from database
  *
  * @param string|array $filter optional, default empty array
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = array()) {
    $sql = "SELECT user_id, subject_id
              FROM %s";
    $conditions = array();
    if (!empty($filter)) {
      if (is_array($filter)) {
        foreach ($filter as $field => $value) {
          if (in_array($field, array('user_id', 'subject_id'))) {
            $conditions[] = $this->databaseGetSqlCondition($field, $value);
          }
        }
      } else {
        $conditions[] = "user_id = '".$this->databaseEscapeString($filter)."'";
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY user_id, subject_id";
    $parameters = array($this->databaseGetTableName('general_survey_user_subject'));
    return $this->_loadRecords($sql, $parameters);
  }

  /**
  * Delete records from the database
  *
  * @param string|array $filter
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($filter) {
    if (is_string($filter)) {
      $filter = array('user_id' => $filter);
    }
    $sanitizedFilter = array();
    foreach ($filter as $field => $value) {
      if (in_array($field, array('user_id', 'subject_id'))) {
        $sanitizedFilter[$field] = $value;
      }
    }
    $result = FALSE;
    if (!empty($sanitizedFilter)) {
      $deleted = (
        FALSE !== $this->databaseDeleteRecord(
          $this->databaseGetTableName('general_survey_user_subject'),
          $sanitizedFilter
        )
      );
      if ($deleted) {
        foreach ($this->_records as $id => $data) {
          if (isset($sanitizedFilter['user_id']) && isset($sanitizedFilter['subject_id'])) {
            if ($data['user_id'] == $sanitizedFilter['user_id'] &&
                $data['subject_id'] == $sanitizedFilter['subject_id']) {
              unset($this->_records[$id]);
            }
          } elseif (isset($sanitizedFilter['user_id'])) {
            if ($data['user_id'] == $sanitizedFilter['user_id']) {
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
