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
      's.deleted' => 0,
      'st.subject_language' => $this->language()
    ];
    if (is_array($filter) && !empty($filter)) {
      $mapping = array_flip($this->_fieldMapping);
      foreach ($filter as $field => $value) {
        if (in_array($field, array('id', 'parent_id', 'survey_id'))) {
          $conditions[] = $this->databaseGetSqlCondition($mapping[$field], $value);
        }
      }
    }
    $sql .= str_replace('%', '%%', " WHERE ".implode(" AND ", $conditions));
    $sql .= " ORDER BY survey_id, subject_parent_id, subject_name";
    $parameters = [
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_subject_trans')
    ];
    return $this->_loadRecords($sql, $parameters, 'subject_id', $limit, $offset);
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
  * Get absoulute count of last query
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
