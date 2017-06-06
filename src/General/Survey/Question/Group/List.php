<?php
/**
* Database access for survey question group record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey question group record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestionGroupList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'questiongroup_id' => 'id',
    'survey_id' => 'survey_id',
    'questiongroup_title' => 'title',
    'questiongroup_description' => 'description',
    'questiongroup_order' => 'order'
  );

  /**
  * Load records from the database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = array(), $limit = NULL, $offset = NULL) {
    $sql = "SELECT questiongroup_id, survey_id, questiongroup_title,
                   questiongroup_description, questiongroup_order
              FROM %s";
    $conditions = array();
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'survey_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY survey_id, questiongroup_order";
    $parameters = array($this->databaseGetTableName('general_survey_questiongroup'));
    return $this->_loadRecords($sql, $parameters, 'questiongroup_id', $limit, $offset);
  }

  /**
  * Delete a record from the database
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0) {
      $deleted = (
        FALSE !== $this->databaseDeleteRecord(
          $this->databaseGetTableName('general_survey_questiongroup'),
          'questiongroup_id',
          $id
        )
      );
      if ($deleted) {
        $sql = "UPDATE %s
                   SET questiongroup_order = questiongroup_order - 1
                 WHERE survey_id = %d
                   AND questiongroup_order > %d";
        $parameters = array(
          $this->databaseGetTableName('general_survey_questiongroup'),
          $this->_records[$id]['survey_id'],
          $this->_records[$id]['order']
        );
        $this->databaseQueryFmtWrite($sql, $parameters);
        if (isset($tempRecords[$id])) {
          unset($tempRecords[$id]);
        }
        $result = TRUE;
      }
    }
    if (!empty($tempRecords)) {
      $this->load(array('id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Move a record up by one position
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function moveUp($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0 && $this->_records[$id]['order'] > 1) {
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order + 1
               WHERE survey_id = %d
                 AND questiongroup_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $this->_records[$id]['survey_id'],
        $this->_records[$id]['order'] - 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order - 1
               WHERE questiongroup_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('questiongroup_id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Move a record down by one position
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function moveDown($id) {
    $result = FALSE;
    $tempRecords = $this->_records;
    $this->load(array('id' => $id));
    if (count($this->_records) > 0 &&
        $this->_records[$id]['order'] < $this->_getMaxOrder($this->_records[$id]['survey_id'])) {
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order - 1
               WHERE survey_id = %d
                 AND questiongroup_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $this->_records[$id]['survey_id'],
        $this->_records[$id]['order'] + 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order + 1
               WHERE questiongroup_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    if (!empty($tempRecords)) {
      $this->load(array('questiongroup_id' => array_keys($tempRecords)));
    }
    return $result;
  }

  /**
  * Get the maximum order number by survey id
  *
  * @param integer $surveyId
  * @return integer
  */
  protected function _getMaxOrder($surveyId) {
    $maxOrder = 0;
    $sql = "SELECT MAX(questiongroup_order) max_order
              FROM %s
             WHERE survey_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_questiongroup'),
      $surveyId
    );
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $maxOrder = $row['max_order'];
      }
    }
    return $maxOrder;
  }
}
