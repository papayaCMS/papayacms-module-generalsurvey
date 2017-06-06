<?php
/**
* Database access for survey record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'survey_id' => 'id',
    'survey_title' => 'title',
    'survey_description' => 'description'
  );

  /**
  * Load records from database
  *
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($limit = NULL, $offset = NULL) {
    $sql = "SELECT survey_id, survey_title, survey_description
              FROM %s
             ORDER BY survey_title ASC";
    $parameters = array($this->databaseGetTableName('general_survey'));
    return $this->_loadRecords($sql, $parameters, 'survey_id', $limit, $offset);
  }

  /**
  * Delete a record from the database
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($id) {
    $deleted = (
      FALSE !== $this->databaseDeleteRecord(
        $this->databaseGetTableName('general_survey'),
        'survey_id',
        $id
      )
    );
    $result = FALSE;
    if ($deleted) {
      if (isset($this->_records[$id])) {
        unset($this->_records[$id]);
      }
      $result = TRUE;
    }
    return $result;
  }
}
