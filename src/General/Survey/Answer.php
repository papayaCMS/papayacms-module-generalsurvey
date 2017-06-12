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
   * Language ID
   * @var integer
   */
  private $_language = 0;

  /**
  * Load a record from database by id
  *
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT a.answer_id, a.question_id,
                   atr.answer_title, atr.answer_description, a.answer_order
              FROM %s a
             INNER JOIN %s atr
                ON a.answer_id = atr.answer_id
             WHERE a.answer_id = %d
               AND atr.answer_language = %d
               AND a.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey_answer'),
      $this->databaseGetTableName('general_survey_answer_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $this->convertRecordToValues($row);
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
  * Get an alternate translation if the current language is not available
  *
  * @param integer $id
  * @return array
  */
  public function getAlternateTranslation($id) {
    $result = [];
    $sql = "SELECT a.answer_id, a.question_id,
                   atr.answer_title, atr.answer_description, a.answer_order
              FROM %s a
             INNER JOIN %s atr
                ON a.answer_id = atr.answer_id
             WHERE a.answer_id = %d
               AND atr.answer_language != %d
               AND a.deleted = 0
             ORDER BY atr.answer_language ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey_answer'),
      $this->databaseGetTableName('general_survey_answer_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, 1)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $this->convertRecordToValues($row);
        $result = [
          'id' => $id,
          'question_id' => $row['question_id'],
          'title' => sprintf('[%s]', $row['answer_title']),
          'description' => $row['answer_description'],
          'order' => $row['answer_order'],
          'ADD_TRANSLATION' => 1
        ];
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
      'answer_order' => $order
    );
    $id = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_answer'),
      'answer_id',
      $data
    );
    if (FALSE !== $id) {
      $data = [
        'answer_id' => $id,
        'answer_language' => $this->language(),
        'answer_title' => $this['title'],
        'answer_description' => $this['description']
      ];
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_answer_trans'),
        NULL,
        $data
      );
      if (FALSE !== $success) {
        $this['order'] = $order;
        return $this['id'] = $id;
      }
    }
    return FALSE;
  }

  /**
  * Update an existing record or add a new translation
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $sql = "SELECT COUNT(*) num
              FROM %s
             WHERE answer_id = %d
               AND answer_language = %d";
    $parameters = [
      $this->databaseGetTableName('general_survey_answer_trans'),
      $this['id'],
      $this->language()
    ];
    $answerExists = FALSE;
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $answerExists = ($row['num'] > 0);
      }
    }
    $data = [
      'answer_title' => $this['title'],
      'answer_description' => $this['description'],
    ];
    if ($answerExists) {
      $success = $this->databaseUpdateRecord(
        $this->databaseGetTableName('general_survey_answer_trans'),
        $data,
        ['answer_id' => $this['id'], 'answer_language' => $this->language()]
      );
    } else {
      $data['answer_id'] = $this['id'];
      $data['answer_language'] = $this->language();
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_answer_trans'),
        NULL,
        $data
      );
    }
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

  /**
   * Get/set current language
   *
   * @param integer $language optional, default NULL
   * @return integer
   */
  public function language($language = NULL) {
    if ($language !== NULL) {
      $this->_language = $language;
    }
    return $this->_language;
  }
}
