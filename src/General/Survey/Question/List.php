<?php
/**
* Database access for survey question record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey question record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestionList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'question_id' => 'id',
    'questiongroup_id' => 'questiongroup_id',
    'question_title' => 'title',
    'question_description' => 'description',
    'question_type' => 'type',
    'question_order' => 'order'
  );

  /**
  * Load records from database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = array(), $limit = NULL, $offset = NULL) {
    $sql = "SELECT question_id, questiongroup_id, question_title,
                   question_description, question_type, question_order
              FROM %s";
    $conditions = array();
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'questiongroup_id', 'type'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY questiongroup_id, question_order";
    $parameters = array($this->databaseGetTableName('general_survey_question'));
    return $this->_loadRecords($sql, $parameters, 'question_id', $limit, $offset);
  }

  /**
  * Delete a record from the database
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0) {
      $deleted = (
        FALSE !== $this->databaseDeleteRecord(
          $this->databaseGetTableName('general_survey_question'),
          'question_id',
          $id
        )
      );
      $result = FALSE;
      if ($deleted) {
        $sql = "UPDATE %s
                   SET question_order = question_order - 1
                 WHERE questiongroup_id = %d
                   AND question_order > %d";
        $parameters = array(
          $this->databaseGetTableName('general_survey_question'),
          $this->_records[$id]['questiongroup_id'],
          $this->_records[$id]['order']
        );
        $this->databaseQueryFmtWrite($sql, $parameters);
        if (isset($tempRecords[$id])) {
          unset($tempRecords[$id]);
        }
        $result = TRUE;
      }
    }
    if (!empty($tempRecords)) {
      $this->load(array('id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Move a record up by one position
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function moveUp($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0 && $this->_records[$id]['order'] > 1) {
      $sql = "UPDATE %s
                 SET question_order = question_order + 1
               WHERE questiongroup_id = %d
                 AND question_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $this->_records[$id]['questiongroup_id'],
        $this->_records[$id]['order'] - 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET question_order = question_order - 1
               WHERE question_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('question_id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Move a record down by one position
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function moveDown($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0 &&
        $this->_records[$id]['order'] < $this->_getMaxOrder(
          $this->_records[$id]['questiongroup_id']
        )) {
      $sql = "UPDATE %s
                 SET question_order = question_order - 1
               WHERE questiongroup_id = %d
                 AND question_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $this->_records[$id]['questiongroup_id'],
        $this->_records[$id]['order'] + 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET question_order = question_order + 1
               WHERE question_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('question_id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Get the maximum order number by question group id
  *
  * @param integer $questionGroupId
  * @return integer
  */
  protected function _getMaxOrder($questionGroupId) {
    $maxOrder = 0;
    $sql = "SELECT MAX(question_order) max_order
              FROM %s
             WHERE questiongroup_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_question'),
      $questionGroupId
    );
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $maxOrder = $row['max_order'];
      }
    }
    return $maxOrder;
  }
}
