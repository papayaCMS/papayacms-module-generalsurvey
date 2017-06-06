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
  * Load a record from database by id
  *
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT subject_id, subject_parent_id, survey_id, subject_name
              FROM %s
             WHERE subject_id = %d";
    $parameters = array($this->databaseGetTableName('general_survey_subject'), $id);
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
    $data = array(
      'subject_parent_id' => $this['parent_id'],
      'survey_id' => $this['survey_id'],
      'subject_name' => $this['name']
    );
    $success = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_subject'),
      'subject_id',
      $data
    );
    if (FALSE !== $success) {
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
      'subject_parent_id' => $this['parent_id'],
      'survey_id' => $this['survey_id'],
      'subject_name' => $this['name']
    );
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_subject'),
      $data,
      'subject_id',
      $this['id']
    );
    return FALSE !== $success;
  }
}
