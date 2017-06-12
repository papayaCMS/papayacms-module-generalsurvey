<?php
/**
* Database access for survey answer record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey answer record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyAnswerList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = array(
    'answer_id' => 'id',
    'question_id' => 'question_id',
    'answer_title' => 'title',
    'answer_description' => 'description',
    'answer_order' => 'order'
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
    $sql = "SELECT a.answer_id, a.question_id,
                   atr.answer_title, atr.answer_description, a.answer_order
              FROM %s a
             INNER JOIN %s atr";
    $conditions = [
      $this->databaseGetSqlCondition('deleted', 0),
      $this->databaseGetSqlCondition('answer_language', $this->language())
    ];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'question_id'))) {
          $conditions[] = $this->databaseGetSqlCondition('a.'.$mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY a.question_id, a.answer_order";
    $parameters = [
      $this->databaseGetTableName('general_survey_answer'),
      $this->databaseGetTableName('general_survey_answer_trans')
    ];
    return $this->_loadRecords($sql, $parameters, 'answer_id', $limit, $offset);
  }

  /**
  * Get the full list of answers - with or without translations in the current language
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return array
  */
  public function getFull($filter = [], $limit = NULL, $offset = NULL) {
    $result = [];
    $sql = "SELECT answer_id, question_id, answer_order
              FROM %s";
    $conditions = [$this->databaseGetSqlCondition('deleted', 0)];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'question_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY question_id, answer_order";
    $parameters = [
      $this->databaseGetTableName('general_survey_answer')
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, $limit, $offset)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result[$row['answer_id']] = [
          'question_id' => $row['question_id'],
          'order' => $row['answer_order']
        ];
      }
    }
    if (!empty($result)) {
      $conditions = [
        $this->databaseGetSqlCondition('answer_id', array_keys($result)),
        $this->databaseGetSqlCondition('answer_language', $this->language())
      ];
      $sql = "SELECT answer_id, answer_title, answer_description
                FROM %s
               WHERE ".str_replace('%', '%%', implode(" AND ", $conditions));
      $parameters = [$this->databaseGetTableName('general_survey_answer_trans')];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['answer_id'];
          $result[$id]['title'] = $row['answer_title'];
          $result[$id]['description'] = $row['answer_description'];
          $result[$id]['HAS_CURRENT_LANGUAGE'] = 1;
        }
      }
      $condition = $this->databaseGetSqlCondition('answer_id', array_keys($result));
      $sql = "SELECT answer_id, answer_title, answer_description
                FROM %s
               WHERE ".str_replace('%', '%%', $condition)
        . " AND answer_language != %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_answer_trans'),
        $this->language()
      ];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['answer_id'];
          if (!isset($result[$id]['title'])) {
            $result[$id]['title'] = sprintf('[%s]', $row['answer_title']);
            $result[$id]['description'] = $row['answer_description'];
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
          $this->databaseGetTableName('general_survey_answer'),
          ['deleted' => time(), 'answer_order' => 0],
          ['answer_id' => $id]
        )
      );
      if ($deleted) {
        $sql = "UPDATE %s
                   SET answer_order = answer_order - 1
                 WHERE question_id = %d
                   AND answer_order > %d";
        $parameters = array(
          $this->databaseGetTableName('general_survey_answer'),
          $records[$id]['question_id'],
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
                 SET answer_order = answer_order + 1
               WHERE question_id = %d
                 AND answer_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $records[$id]['question_id'],
        $records[$id]['order'] - 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET answer_order = answer_order - 1
               WHERE answer_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
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
          $records[$id]['question_id']
        )) {
      $sql = "UPDATE %s
                 SET answer_order = answer_order - 1
               WHERE question_id = %d
                 AND answer_order = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $records[$id]['question_id'],
        $records[$id]['order'] + 1
      );
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET answer_order = answer_order + 1
               WHERE answer_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_answer'),
        $id
      );
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
    }
    return $result;
  }

  /**
  * Get the maximum order number by question id
  *
  * @param integer $questionId
  * @return integer
  */
  protected function _getMaxOrder($questionId) {
    $maxOrder = 0;
    $sql = "SELECT MAX(answer_order) max_order
              FROM %s
             WHERE question_id = %d";
    $parameters = array(
      $this->databaseGetTableName('general_survey_answer'),
      $questionId
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
