<?php
/**
* Database access for single survey result records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Result.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey result records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyResult extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = array(
    'answer_id' => 'answer_id',
    'subject_id' => 'subject_id',
    'count' => 'result_count'
  );

  /**
  * Load a record from the database
  *
  * @param array $filter
  * @throws InvalidArgumentException
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter) {
    if (!is_array($filter) || !isset($filter['answer_id']) || !isset($filter['subject_id'])) {
      throw new InvalidArgumentException('Need both answer and subject id to identify a record.');
    }
    $sql = "SELECT answer_id, subject_id, result_count
              FROM %s
             WHERE answer_id = %d
               AND subject_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_result'),
      $filter['answer_id'],
      $filter['subject_id']
    );
    $result = FALSE;
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $this->convertRecordToValues($row);
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
  * Save record
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function save() {
    $data = array(
      'answer_id' => $this['answer_id'],
      'subject_id' => $this['subject_id']
    );
    if ($this->load($data)) {
      $result = (
        FALSE !== $this->databaseUpdateRecord(
          $this->databaseGetTableName('general_survey_result'),
          array('result_count' => $this['count'] + 1),
          $data
        )
      );
      if ($result) {
        $this->_values['count']++;
      }
      return $result;
    }
    $data['result_count'] = 1;
    $result = (
      FALSE !== $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_result'),
        NULL,
        $data
      )
    );
    if ($result) {
      $this->_values['count'] = 1;
    }
    return $result;
  }
}
