<?php
/**
* Database access for single survey answer records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Answer.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey answer records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyAnswer extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'id' => 'answer_id',
    'question_id' => 'question_id',
    'title' => 'answer_title',
    'description' => 'answer_description',
    'order' => 'answer_order'
  );

  /**
  * Load a record from database by id
  *
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT answer_id, question_id, answer_title, answer_description, answer_order
              FROM %s
             WHERE answer_id = %d";
    $parameters = array($this->databaseGetTableName('general_survey_answer'), $id);
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $this->convertRecordToValues($row);
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
  * Save the current record to the database
  *
  * @return mixed integer|boolean
  */
  public function save() {
    if (empty($this['id'])) {
      return $this->_insert();
    } else {
      return $this->_update();
    }
  }

  /**
  * Insert current data as new record
  *
  * @return mixed integer new id on success, boolean FALSE otherwise
  */
  private function _insert() {
    $order = $this->_getMaxOrder() + 1;
    $data = array(
      'question_id' => $this['question_id'],
      'answer_title' => $this['title'],
      'answer_description' => $this['description'],
      'answer_order' => $order
    );
    $success = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_answer'),
      'answer_id',
      $data
    );
    if (FALSE !== $success) {
      $this['order'] = $order;
      return $this['id'] = $success;
    }
    return FALSE;
  }

  /**
  * Update an existing record
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $data = array(
      'answer_title' => $this['title'],
      'answer_description' => $this['description'],
    );
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_answer'),
      $data,
      'answer_id',
      $this['id']
    );
    return FALSE !== $success;
  }

  /**
  * Get the maximum order number for the current question id
  *
  * @return integer
  */
  protected function _getMaxOrder() {
    $maxOrder = 0;
    if (isset($this['question_id'])) {
      $sql = "SELECT MAX(answer_order) max_order
                FROM %s
               WHERE question_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $this['question_id']
      );
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
          $maxOrder = $row['max_order'];
        }
      }
    }
    return $maxOrder;
  }
}
