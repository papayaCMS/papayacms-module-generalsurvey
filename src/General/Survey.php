<?php
/**
* Database access for single survey records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Survey.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurvey extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'id' => 'survey_id',
    'use_subjects' => 'survey_use_subjects',
    'use_answers' => 'survey_use_answers',
    'title' => 'survey_title',
    'description' => 'survey_description'
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
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT s.survey_id, s.survey_use_subjects, s.survey_use_answers,
                   st.survey_title, st.survey_description
              FROM %s s
             INNER JOIN %s st
                ON s.survey_id = st.survey_id
             WHERE s.survey_id = %d
               AND st.survey_language = %d
               AND s.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey'),
      $this->databaseGetTableName('general_survey_trans'),
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
  * Get another translation if the current language is not available
  *
  * @param integer $id
  * @return array
  */
  public function getAlternateTranslation($id) {
    $result = [];
    $sql = "SELECT s.survey_id, s.survey_use_subjects, s.survey_use_answers,
                   st.survey_title, st.survey_description
              FROM %s s
             INNER JOIN %s st
                ON s.survey_id = st.survey_id
             WHERE s.survey_id = %d
               AND st.survey_language != %d
               AND s.deleted = 0
             ORDER BY st.survey_language ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey'),
      $this->databaseGetTableName('general_survey_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, 1)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result = [
          'id' => $row['survey_id'],
          'use_subjects' => $row['survey_use_subjects'],
          'use_answers' => $row['survey_use_answers'],
          'title' => sprintf('[%s]', $row['survey_title']),
          'description' => $row['survey_description'],
          'ADD_TRANSLATION' => 1
        ];
      }
    }
    return $result;
  }

  /**
  * Save the current record to the database
  *
  * @return mixed integer | boolean
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
    $id = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey'),
      'survey_id',
      [
        'survey_use_subjects' => $this['use_subjects'],
        'survey_use_answers' => $this['use_answers'],
        'deleted' => 0
      ]
    );
    if (FALSE !== $id) {
      $data = array(
        'survey_id' => $id,
        'survey_language' => $this->language(),
        'survey_title' => $this['title'],
        'survey_description' => $this['description']
      ); var_dump($data);
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_trans'),
        NULL,
        $data
      );
      if (FALSE !== $success) {
        return $this['id'] = $id;
      }
    }
    return FALSE;
  }

  /**
  * Update existing record and/or add a new translation
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $data = [
      'survey_use_subjects' => $this['use_subjects'],
      'survey_use_answers' => $this['use_answers']
    ];
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey'),
      $data,
      ['survey_id' => $this['id']]
    );
    if (FALSE !== $success) {
      $sql = "SELECT COUNT(*) num
              FROM %s
             WHERE survey_id = %d
               AND survey_language = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_trans'),
        $this['id'],
        $this->language()
      ];
      $recordExists = FALSE;
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $recordExists = ($row['num'] > 0);
        }
      }
      $data = array(
        'survey_title' => $this['title'],
        'survey_description' => $this['description']
      );
      if ($recordExists) {
        $success = $this->databaseUpdateRecord(
          $this->databaseGetTableName('general_survey_trans'),
          $data,
          ['survey_id' => $this['id'], 'survey_language' => $this->language()]
        );
      } else {
        $data['survey_id'] = $this['id'];
        $data['survey_language'] = $this->language();
        $success = $this->databaseInsertRecord(
          $this->databaseGetTableName('general_survey_trans'),
          NULL,
          $data
        );
      }
      return FALSE !== $success;
    }
    return FALSE;
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
