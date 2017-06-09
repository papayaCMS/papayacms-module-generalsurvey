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
  protected $_fieldMapping = [
    'questiongroup_id' => 'id',
    'survey_id' => 'survey_id',
    'questiongroup_title' => 'title',
    'questiongroup_description' => 'description',
    'questiongroup_order' => 'order'
  ];

  /**
  * Language ID
  * @var integer
  */
  private $_language = 0;

  /**
  * Load records from the database
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($filter = [], $limit = NULL, $offset = NULL) {
    $sql = "SELECT g.questiongroup_id, g.survey_id, gt.questiongroup_title,
                   gt.questiongroup_description, g.questiongroup_order
              FROM %s g
             INNER JOIN %s gt
                ON g.questiongroup_id = gt.questiongroup_id";
    $conditions = [
      $this->databaseGetSqlCondition('questiongroup_language', $this->language()),
      $this->databaseGetSqlCondition('deleted', 0)
    ];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'survey_id'])) {
          $conditions[] = $this->databaseGetSqlCondition('g.'.$mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY survey_id, questiongroup_order";
    $parameters = [
      $this->databaseGetTableName('general_survey_questiongroup'),
      $this->databaseGetTableName('general_survey_questiongroup_trans')
    ];
    return $this->_loadRecords($sql, $parameters, 'questiongroup_id', $limit, $offset);
  }

  /**
  * Get the full list of question groups, with or without translation in the current language
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return array
  */
  public function getFull($filter = [], $limit = NULL, $offset = NULL) {
    $result = [];
    $sql = "SELECT questiongroup_id, questiongroup_order, survey_id
              FROM %s";
    $conditions = [
      $this->databaseGetSqlCondition('deleted', 0)
    ];
    if (!empty($filter) && is_array($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'survey_id'])) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY survey_id, questiongroup_order";
    $parameters = [$this->databaseGetTableName('general_survey_questiongroup')];
    if ($res = $this->databaseQueryFmt($sql, $parameters, $limit, $offset)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result[$row['questiongroup_id']] = [
          'id' => $row['questiongroup_id'],
          'order' => $row['questiongroup_order'],
          'survey_id' => $row['survey_id']
        ];
      }
    }
    if (!empty($result)) {
      $conditions = [
        $this->databaseGetSqlCondition('questiongroup_id', array_keys($result)),
        $this->databaseGetSqlCondition('questiongroup_language', $this->language())
      ];
      $sql = "SELECT questiongroup_id, questiongroup_title, questiongroup_description
                FROM %s
               WHERE ".str_replace('%', '%%', implode(" AND ", $conditions));
      $parameters = [$this->databaseGetTableName('general_survey_questiongroup_trans')];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['questiongroup_id'];
          $result[$id]['title'] = $row['questiongroup_title'];
          $result[$id]['description'] = $row['questiongroup_description'];
          $result[$id]['HAS_CURRENT_LANGUAGE'] = 1;
        }
      }
      $condition = $this->databaseGetSqlCondition('questiongroup_id', array_keys($result));
      $sql = "SELECT questiongroup_id, questiongroup_title, questiongroup_description
                FROM %s
               WHERE ".str_replace('%', '%%', $condition)
             . " AND questiongroup_language != %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup_trans'),
        $this->language()
      ];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
          $id = $row['questiongroup_id'];
          if (!isset($result[$id]['title'])) {
            $result[$id]['title'] = sprintf('[%s]', $row['questiongroup_title']);
            $result[$id]['description'] = $row['questiongroup_description'];
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
          $this->databaseGetTableName('general_survey_questiongroup'),
          ['deleted' => time(), 'questiongroup_order' => 0],
          ['questiongroup_id' => $id]
        )
      );
      if ($deleted) {
        $sql = "UPDATE %s
                   SET questiongroup_order = questiongroup_order - 1
                 WHERE survey_id = %d
                   AND questiongroup_order > %d";
        $parameters = [
          $this->databaseGetTableName('general_survey_questiongroup'),
          $records[$id]['survey_id'],
          $records[$id]['order']
        ];
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
    $records = $this->getFull(['id' => $id]); var_dump($records[$id]);
    if (count($records) > 0 && $records[$id]['order'] > 1) {
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order + 1
               WHERE survey_id = %d
                 AND questiongroup_order = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup'),
        $records[$id]['survey_id'],
        $records[$id]['order'] - 1
      ]; vprintf($sql, $parameters);
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order - 1
               WHERE questiongroup_id = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup'),
        $id
      ]; vprintf($sql, $parameters);
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
        $records[$id]['order'] < $this->_getMaxOrder($records[$id]['survey_id'])) {
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order - 1
               WHERE survey_id = %d
                 AND questiongroup_order = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup'),
        $records[$id]['survey_id'],
        $records[$id]['order'] + 1
      ];
      $this->databaseQueryFmtWrite($sql, $parameters);
      $sql = "UPDATE %s
                 SET questiongroup_order = questiongroup_order + 1
               WHERE questiongroup_id = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup'),
        $id
      ];
      $result = (FALSE !== $this->databaseQueryFmtWrite($sql, $parameters));
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
    $parameters = [
      $this->databaseGetTableName('general_survey_questiongroup'),
      $surveyId
    ];
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
