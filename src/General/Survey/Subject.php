<?php
/**
* Database access for single survey subject records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Subject.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey subject records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveySubject extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'id' => 'subject_id',
    'parent_id' => 'subject_parent_id',
    'survey_id' => 'survey_id',
    'name' => 'subject_name'
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
    $sql = "SELECT s.subject_id, s.subject_parent_id, s.survey_id, st.subject_name
              FROM %s s
             INNER JOIN %s st
                ON s.subject_id = st.subject_id
             WHERE s.subject_id = %d
               AND st.subject_language = %d
               AND s.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_subject_trans'),
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
    $data = [
      'subject_parent_id' => $this['parent_id'],
      'survey_id' => $this['survey_id']
    ];
    $id = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_subject'),
      'subject_id',
      $data
    );
    if (FALSE !== $id) {
      $data = [
        'subject_id' => $id,
        'subject_language' => $this->language(),
        'subject_name' => $this['name']
      ];
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_subject_trans'),
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
  * Update an existing record and/or add a new translation
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $data = ['subject_parent_id' => $this['parent_id']];
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_subject'),
      $data,
      'subject_id',
      $this['id']
    );
    if (FALSE !== $success) {
      $sql = "SELECT COUNT(*) num
                FROM %s
               WHERE subject_id = %d
                 AND subject_language = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_subject_trans'),
        $this['id'],
        $this->language()
      ];
      $subjectExists = FALSE;
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $subjectExists = ($row['num'] > 0);
        }
      }
      $data = ['subject_name' => $this['name']];
      if ($subjectExists) {
        $success = $this->databaseUpdateRecord(
          $this->databaseGetTableName('general_survey_subject_trans'),
          $data,
          [
            'subject_id' => $this['id'],
            'subject_language' => $this->language()
          ]
        );
      } else {
        $data['subject_id'] = $this['id'];
        $data['subject_language'] = $this->language();
        $success = $this->databaseInsertRecord(
          $this->databaseGetTableName('general_survey_subject_trans'),
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
