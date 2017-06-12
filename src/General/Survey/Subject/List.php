<?php
/**
* Database access for survey subject record lists
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: List.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey subject record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveySubjectList extends PapayaDatabaseObjectList {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fieldMapping = [
    'subject_id' => 'id',
    'subject_parent_id' => 'parent_id',
    'survey_id' => 'survey_id',
    'subject_name' => 'name'
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
    $sql = "SELECT s.subject_id, s.subject_parent_id, s.survey_id, st.subject_name
              FROM %s s
             INNER JOIN %s st
                ON s.subject_id = st.subject_id";
    $conditions = [
      $this->databaseGetSqlCondition('s.deleted', 0),
      $this->databaseGetSqlCondition('st.subject_language', $this->language())
    ];
    if (is_array($filter) && !empty($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'parent_id', 'survey_id'])) {
          $conditions[] = $this->databaseGetSqlCondition('s.'.$mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY s.survey_id, s.subject_parent_id, st.subject_name";
    $parameters = [
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_subject_trans')
    ];
    return $this->_loadRecords($sql, $parameters, 'subject_id', $limit, $offset);
  }

  /**
  * Get the full list of subjects, including those without translation in current language
  *
  * @param array $filter optional, default empty array
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return array
  */
  public function getFull($filter = [], $limit = NULL, $offset = NULL) {
    $result = [];
    $sql = "SELECT s.subject_id, s.subject_parent_id, s.survey_id, st.subject_name
              FROM %s s
             INNER JOIN %s st
                ON s.subject_id = st.subject_id";
    $conditions = [
      $this->databaseGetSqlCondition('s.deleted', 0),
      $this->databaseGetSqlCondition('st.subject_language', $this->language())
    ];
    if (is_array($filter) && !empty($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'parent_id', 'survey_id'])) {
          $conditions[] = $this->databaseGetSqlCondition('s.'.$mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY s.survey_id, s.subject_parent_id, st.subject_name";
    $parameters = [
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_subject_trans')
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, $limit, $offset)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result[$row['subject_id']] = [
          'id' => $row['subject_id'],
          'parent_id' => $row['subject_parent_id'],
          'survey_id' => $row['survey_id'],
          'name' => $row['subject_name'],
          'HAS_CURRENT_LANGUAGE' => 1
        ];
      }
    }
    $sql = "SELECT s.subject_id, s.subject_parent_id, s.survey_id, st.subject_name
              FROM %s s
             INNER JOIN %s st
                ON s.subject_id = st.subject_id
             WHERE st.subject_language != %d";
    $conditions = [
      $this->databaseGetSqlCondition('s.deleted', 0)
    ];
    if (is_array($filter) && !empty($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, ['id', 'parent_id', 'survey_id'])) {
          $conditions[] = $this->databaseGetSqlCondition('s.'.$mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " AND ".implode(" AND ", $conditions));
    $sql .= " ORDER BY s.survey_id, s.subject_parent_id, st.subject_name";
    $parameters = [
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_subject_trans'),
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, $limit, $offset)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if (!array_key_exists($row['subject_id'], $result)) {
          $result[$row['subject_id']] = [
            'id' => $row['subject_id'],
            'parent_id' => $row['subject_parent_id'],
            'survey_id' => $row['survey_id'],
            'name' => sprintf('[%s]', $row['subject_name']),
            'HAS_CURRENT_LANGUAGE' => 0
          ];
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
    $deleted = (
      FALSE !== $this->databaseUpdateRecord(
        $this->databaseGetTableName('general_survey_subject'),
        ['deleted' => time()],
        ['subject_id' => $id]
      )
    );
    if ($deleted) {
      if (isset($this->_records[$id])) {
        unset($this->_records[$id]);
      }
      $result = TRUE;
    }
    return $result;
  }

  /**
  * Get absolute count of last query
  *
  * @return integer
  */
  public function getAbsCount() {
    return $this->_recordCount;
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
