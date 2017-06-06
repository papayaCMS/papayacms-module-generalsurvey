<?php
/**
* Database access for single survey question group records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Group.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey question group records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestionGroup extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'id' => 'questiongroup_id',
    'survey_id' => 'survey_id',
    'title' => 'questiongroup_title',
    'description' => 'questiongroup_description',
    'order' => 'questiongroup_order'
  );

  /**
  * Load a record by id
  *
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT questiongroup_id, survey_id, questiongroup_title,
                   questiongroup_description, questiongroup_order
              FROM %s
             WHERE questiongroup_id = %d";
    $parameters = array($this->databaseGetTableName('general_survey_questiongroup'), $id);
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
      'survey_id' => $this['survey_id'],
      'questiongroup_title' => $this['title'],
      'questiongroup_description' => $this['description'],
      'questiongroup_order' => $order
    );
    $success = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_questiongroup'),
      'questiongroup_id',
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
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $data = array(
      'questiongroup_title' => $this['title'],
      'questiongroup_description' => $this['description']
    );
    $success = $this->databaseUpdateRecord(
      $this->databaseGetTableName('general_survey_questiongroup'),
      $data,
      'questiongroup_id',
      $this['id']
    );
    return FALSE !== $success;
  }

  /**
  * Get the maximum order number for the current survey id
  *
  * @return integer
  */
  protected function _getMaxOrder() {
    $maxOrder = 0;
    if (isset($this['survey_id'])) {
      $sql = "SELECT MAX(questiongroup_order) max_order
                FROM %s
               WHERE survey_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $this['survey_id']
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
