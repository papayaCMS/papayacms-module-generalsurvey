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
   * Language ID
   * @var integer
   */
  private $_language = 0;

  /**
  * Load a record by id
  * 
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT q.question_id, q.questiongroup_id, qt.question_title,
                   qt.question_description, q.question_type, q.question_order
              FROM %s q
             INNER JOIN %s qt
                ON q.question_id = qt.question_id
             WHERE q.question_id = %d
               AND qt.question_language = %d
               AND q.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey_question'),
      $this->databaseGetTableName('general_survey_question_trans'),
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
   * Get alternate translation if the current language is not available
   *
   * @param integer $id
   * @return array
   */
  public function getAlternateTranslation($id)
  {
    $result = [];
    $sql = "SELECT q.question_id, q.questiongroup_id, qt.question_title,
                   qt.question_description, q.question_type, q.question_order
              FROM %s q
             INNER JOIN %s qt
                ON q.question_id = qt.question_id
             WHERE q.question_id = %d
               AND qt.question_language != %d
               AND q.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey_question'),
      $this->databaseGetTableName('general_survey_question_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, 1)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $result = [
          'id' => $row['question_id'],
          'title' => sprintf('[%s]', $row['question_title']),
          'description' => $row['question_description'],
          'type' => $row['question_type'],
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
  * @return mixed integer new id on success, boolean FALSE on failure
  */
  private function _insert() {
    $order = $this->_getMaxOrder() + 1;
    $data = [
      'questiongroup_id' => $this['questiongroup_id'],
      'question_type' => $this['type'],
      'question_order' => $order
    ];
    $id = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_question'),
      'question_id',
      $data
    );
    if (FALSE !== $id) {
      $data = [
        'question_id' => $id,
        'question_language' => $this->language(),
        'question_title' => $this['title'],
        'question_description' => $this['description']
      ];
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_question_trans'),
        NULL,
        $data
      );
      $this['order'] = $order;
      if (FALSE !== $success) {
        return $this['id'] = $id;
      }
    }
    return FALSE;
  }

  /**
  * Update existing record and/or add new translation
  *
  * @return boolean
  */
  private function _update() {
    $data = [
      'question_type' => $this['type'],
    ];
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_question'),
      $data,
      'question_id',
      $this['id']
    );
    if (FALSE !== $success) {
      $sql = "SELECT COUNT(*) num
                FROM %s
               WHERE question_id = %d
                 AND question_language = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_question_trans'),
        $this['id'],
        $this->language()
      ];
      $questionExists = FALSE;
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $questionExists = ($row['num'] > 0);
        }
      }
      $data = [
        'question_title' => $this['title'],
        'question_description' => $this['description']
      ];
      if ($questionExists) {
        $success = $this->databaseUpdateRecord(
          $this->databaseGetTableName('general_survey_question_trans'),
          $data,
          [
            'question_id' => $this['id'],
            'question_language' => $this->language()
          ]
        );
      } else {
        $data['question_id'] = $this['id'];
        $data['question_language'] = $this->language();
        $success = $this->databaseInsertRecord(
          $this->databaseGetTableName('general_survey_question_trans'),
          NULL,
          $data
        );
      }
      return FALSE !== $success;
    }
    return FALSE;
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
