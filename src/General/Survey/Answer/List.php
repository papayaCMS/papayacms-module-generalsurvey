<?php
/**
* Database access for survey answer record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey answer record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyAnswerList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'answer_id' => 'id',
    'question_id' => 'question_id',
    'answer_title' => 'title',
    'answer_description' => 'description',
    'answer_order' => 'order'
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
    $sql = "SELECT answer_id, question_id, answer_title, answer_description, answer_order
              FROM %s";
    $conditions = array();
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'question_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY question_id, answer_order";
    $parameters = array($this->databaseGetTableName('general_survey_answer'));
    return $this->_loadRecords($sql, $parameters, 'answer_id', $limit, $offset);
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
          $this->databaseGetTableName('general_survey_answer'),
          'answer_id',
          $id
        )
      );
      if ($deleted) {
        $sql = "UPDATE %s
                   SET answer_order = answer_order - 1
                 WHERE question_id = %d
                   AND answer_order > %d";
        $parameters = array(
          $this->databaseGetTableName('general_survey_answer'),
          $this->_records[$id]['question_id'],
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
                 SET answer_order = answer_order + 1
               WHERE question_id = %d
                 AND answer_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $this->_records[$id]['question_id'],
        $this->_records[$id]['order'] - 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET answer_order = answer_order - 1
               WHERE answer_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('answer_id' => array_keys($tempRecords)));
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
          $this->_records[$id]['question_id']
        )) {
      $sql = "UPDATE %s
                 SET answer_order = answer_order - 1
               WHERE question_id = %d
                 AND answer_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $this->_records[$id]['question_id'],
        $this->_records[$id]['order'] + 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET answer_order = answer_order + 1
               WHERE answer_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('answer_id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Get the maximum order number by question id
  *
  * @param integer $questionId
  * @return integer
  */
  protected function _getMaxOrder($questionId) {
    $maxOrder = 0;
    $sql = "SELECT MAX(answer_order) max_order
              FROM %s
             WHERE question_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_answer'),
      $questionId
    );
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $maxOrder = $row['max_order'];
      }
    }
    return $maxOrder;
  }
}
