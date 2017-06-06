<?php
/**
* Database access for single survey user-to-subject records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Subject.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey user-to-subject records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyUserSubject extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'user_id' => 'user_id',
    'subject_id' => 'subject_id'
  );

  /**
  * Load a record from the database
  *
  * @param array $filter
  * @throws InvalidArgumentException
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter) {
    if (!is_array($filter) || !isset($filter['user_id']) || !isset($filter['subject_id'])) {
      throw new InvalidArgumentException('Need both user and subject id to identify a record.');
    }
    $sql = "SELECT user_id, subject_id
              FROM %s
             WHERE user_id = '%s'
               AND subject_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_user_subject'),
      $filter['user_id'],
      $filter['subject_id']
    );
    $result = FALSE;
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $row;
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
  * Insert current data as new record
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function save() {
    $data = array(
      'user_id' => $this['user_id'],
      'subject_id' => $this['subject_id']
    );
    $result = TRUE;
    if (!$this->load($data)) {
      $result = (
        FALSE !== $this->databaseInsertRecord(
          $this->databaseGetTableName('general_survey_user_subject'),
          NULL,
          $data
        )
      );
    }
    return $result;
  }
}
