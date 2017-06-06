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
    'title' => 'survey_title',
    'description' => 'survey_description'
  );

  /**
  * Load a record from database by id
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT survey_id, survey_title, survey_description
              FROM %s
             WHERE survey_id = %d";
    $parameters = array($this->databaseGetTableName('general_survey'), $id);
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
    $data = array(
      'survey_title' => $this['title'],
      'survey_description' => $this['description']
    );
    $success = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey'),
      'survey_id',
      $data
    );
    if (FALSE !== $success) {
      return $this['id'] = $success;
    }
    return FALSE;
  }

  /**
  * Update existing record
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $data = array(
      'survey_title' => $this['title'],
      'survey_description' => $this['description']
    );
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey'),
      $data,
      'survey_id',
      $this['id']
    );
    return FALSE !== $success;
  }
}
