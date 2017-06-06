<?php
/**
* Database access for single survey question records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Question.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey question records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestion extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'id' => 'question_id',
    'questiongroup_id' => 'questiongroup_id',
    'title' => 'question_title',
    'description' => 'question_description',
    'type' => 'question_type',
    'order' => 'question_order'
  );

  /**
  * Load a record by id
  * 
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT question_id, questiongroup_id, question_title,
                   question_description, question_type, question_order
              FROM %s
             WHERE question_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_question'),
      $id
    );
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
  * @return mixed integer new id on success, boolean FALSE on failure
  */
  private function _insert() {
    $order = $this->_getMaxOrder() + 1;
    $data = array(
      'questiongroup_id' => $this['questiongroup_id'],
      'question_title' => $this['title'],
      'question_description' => $this['description'],
      'question_type' => $this['type'],
      'question_order' => $order
    );
    $success = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_question'),
      'question_id',
      $data
    );
    if (FALSE !== $success) {
      $this['order'] = $order;
      return $this['id'] = $success;
    }
    return FALSE;
  }

  /**
  * Update existing record
  *
  * @return boolean
  */
  private function _update() {
    $data = array(
      'question_title' => $this['title'],
      'question_description' => $this['description'],
      'question_type' => $this['type'],
    );
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_question'),
      $data,
      'question_id',
      $this['id']
    );
    return FALSE !== $success;
  }

  /**
  * Get the maximum order number for the current question group id
  *
  * @return integer
  */
  protected function _getMaxOrder() {
    $maxOrder = 0;
    if (isset($this['questiongroup_id'])) {
      $sql = "SELECT MAX(question_order) max_order
                FROM %s
               WHERE questiongroup_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $this['questiongroup_id']
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
