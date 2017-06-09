<?php
/**
* Database access for survey question record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey question record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestionList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'question_id' => 'id',
    'questiongroup_id' => 'questiongroup_id',
    'question_title' => 'title',
    'question_description' => 'description',
    'question_type' => 'type',
    'question_order' => 'order'
  );

  /**
   * Language ID
   * @var integer
   */
  private $_language = 0;

  /**
  * Load records from database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = [], $limit = NULL, $offset = NULL) {
    $sql = "SELECT q.question_id, q.questiongroup_id, qt.question_title,
                   qt.question_description, q.question_type, q.question_order
              FROM %s q
             INNER JOIN %s qt
                ON q.question_id = qt.question_id";
    $conditions = [
      'deleted' => 0,
      'question_language' => $this->language()
    ];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'question_id', 'type'))) {
          $conditions[] = $this->databaseGetSqlCondition('q.'.$mapping[$field], $value);
        }
      }
    }
    if (!empty($conditions)) {
      $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    }
    $sql .= " ORDER BY q.questiongroup_id, q.question_order";
    $parameters = array(
      $this->databaseGetTableName('general_survey_question'),
      $this->databaseGetTableName('general_survey_question_trans')
    );
    return $this->_loadRecords($sql, $parameters, 'question_id', $limit, $offset);
  }

  /**
   * Get the full list of questions, with or without translation in the current language
   *
   * @param array $filter optional, default empty array
   * @param integer|NULL $limit optional, default NULL
   * @param integer|NULL $offset optional, default NULL
   * @return array
   */
  public function getFull($filter = [], $limit = NULL, $offset = NULL) {
    $result = [];
    $sql = "SELECT question_id, question_order, questiongroup_id
              FROM %s";
    $conditions = [
      $this->databaseGetSqlCondition('deleted', 0)
    ];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'questiongroup_id'])) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY questiongroup_id, question_order";
    $parameters = [$this->databaseGetTableName('general_survey_question')];
    if ($res = $this->databaseQueryFmt($sql, $parameters, $limit, $offset)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result[$row['question_id']] = [
          'id' => $row['question_id'],
          'order' => $row['question_order'],
          'questiongroup_id' => $row['questiongroup_id']
        ];
      }
    }
    if (!empty($result)) {
      $conditions = [
        $this->databaseGetSqlCondition('question_id', array_keys($result)),
        $this->databaseGetSqlCondition('question_language', $this->language())
      ];
      $sql = "SELECT question_id, question_title, question_description
                FROM %s
               WHERE ".str_replace('%', '%%', implode(" AND ", $conditions));
      $parameters = [$this->databaseGetTableName('general_survey_question_trans')];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['question_id'];
          $result[$id]['title'] = $row['question_title'];
          $result[$id]['description'] = $row['question_description'];
          $result[$id]['HAS_CURRENT_LANGUAGE'] = 1;
        }
      }
      $condition = $this->databaseGetSqlCondition('question_id', array_keys($result));
      $sql = "SELECT question_id, question_title, question_description
                FROM %s
               WHERE ".str_replace('%', '%%', $condition)
        . " AND question_language != %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_question_trans'),
        $this->language()
      ];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['question_id'];
          if (!isset($result[$id]['title'])) {
            $result[$id]['title'] = sprintf('[%s]', $row['question_title']);
            $result[$id]['description'] = $row['question_description'];
            $result[$id]['HAS_CURRENT_LANGUAGE'] = 0;
          }
        }
      }
    }
    return $result;
  }

  /**
  * Delete a record from the database
  *
  * @param integer $id
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function delete($id) {
    $result = FALSE;
    $records = $this->getFull(['id' => $id]);
    if (count($records) > 0) {
      $deleted = (
        FALSE !== $this->databaseUpdateRecord(
          $this->databaseGetTableName('general_survey_question'),
          ['deleted' => time(), 'question_order' => 0],
          ['question_id' => $id]
        )
      );
      $result = FALSE;
      if ($deleted) {
        $sql = "UPDATE %s
                   SET question_order = question_order - 1
                 WHERE questiongroup_id = %d
                   AND question_order > %d";
        $parameters = array(
          $this->databaseGetTableName('general_survey_question'),
          $records[$id]['questiongroup_id'],
          $records[$id]['order']
        );
        $this->databaseQueryFmtWrite($sql, $parameters);
        $result = TRUE;
      }
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
    $records = $this->getFull(['id' => $id]);
    if (count($records) > 0 && $records[$id]['order'] > 1) {
      $sql = "UPDATE %s
                 SET question_order = question_order + 1
               WHERE questiongroup_id = %d
                 AND question_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $records[$id]['questiongroup_id'],
        $records[$id]['order'] - 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET question_order = question_order - 1
               WHERE question_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
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
    $records = $this->getFull(['id' => $id]);
    if (count($records) > 0 &&
        $records[$id]['order'] < $this->_getMaxOrder(
          $records[$id]['questiongroup_id']
        )) {
      $sql = "UPDATE %s
                 SET question_order = question_order - 1
               WHERE questiongroup_id = %d
                 AND question_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $records[$id]['questiongroup_id'],
        $records[$id]['order'] + 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET question_order = question_order + 1
               WHERE question_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_question'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    return $result;
  }

  /**
  * Get the maximum order number by question group id
  *
  * @param integer $questionGroupId
  * @return integer
  */
  protected function _getMaxOrder($questionGroupId) {
    $maxOrder = 0;
    $sql = "SELECT MAX(question_order) max_order
              FROM %s
             WHERE questiongroup_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_question'),
      $questionGroupId
    );
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $maxOrder = $row['max_order'];
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
