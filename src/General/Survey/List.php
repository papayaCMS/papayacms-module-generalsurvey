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
    'survey_use_subjects' => 'use_subjects',
    'survey_use_answers' => 'use_answers',
    'survey_title' => 'title',
    'survey_description' => 'description'
  );

  /**
   * Language ID
   * @var integer
   */
  private $_language = 0;

  /**
  * Load records from database
  *
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function load($limit = NULL, $offset = NULL) {
    $sql = "SELECT s.survey_id, s.survey_use_subjects, s.survey_use_answers,
                   st.survey_title, st.survey_description
              FROM %s s
             INNER JOIN %s st
                ON s.survey_id = st.survey_id
             WHERE s.deleted = 0
               AND st.survey_language = %d
             ORDER BY survey_title ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey'),
      $this->databaseGetTableName('general_survey_trans'),
      $this->language()
    ];
    return $this->_loadRecords($sql, $parameters, 'survey_id', $limit, $offset);
  }

  /**
  * Retrieve a list of all records; current language preferred, other(s) as fallback
  *
  * @param integer|NULL $limit optional, default NULL
  * @param integer|NULL $offset optional, default NULL
  * @return array
  */
  public function getFull($limit = NULL, $offset = NULL) {
    $result = [];
    $sql = "SELECT s.survey_id, s.survey_use_subjects, s.survey_use_answers,
                   st.survey_title, st.survey_description
              FROM %s s
             INNER JOIN %s st
                ON s.survey_id = st.survey_id
             WHERE st.survey_language = %d
               AND s.deleted = 0
             ORDER BY st.survey_title ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey'),
      $this->databaseGetTableName('general_survey_trans'),
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result[$row['survey_id']] = [
          'id' => $row['survey_id'],
          'use_subjects' => $row['survey_use_subjects'],
          'use_answers' => $row['survey_use_answers'],
          'title' => $row['survey_title'],
          'description' => $row['survey_description'],
          'HAS_CURRENT_LANGUAGE' => 1
        ];
      }
    }
    $sql = "SELECT s.survey_id, s.survey_use_subjects, s.survey_use_answers,
                   st.survey_title, st.survey_description
              FROM %s s
             INNER JOIN %s st
                ON s.survey_id = st.survey_id
             WHERE st.survey_language != %d
               AND s.deleted = 0
             ORDER BY st.survey_language ASC, st.survey_title ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey'),
      $this->databaseGetTableName('general_survey_trans'),
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if (!array_key_exists($row['survey_id'], $result)) {
          $result[$row['survey_id']] = [
            'id' => $row['survey_id'],
            'use_subjects' => $row['survey_use_subjects'],
            'use_answers' => $row['survey_use_answers'],
            'title' => sprintf('[%s]', $row['survey_title']),
            'description' => $row['survey_description'],
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
    $deleted = (
      FALSE !== $this->databaseUpdateRecord(
        $this->databaseGetTableName('general_survey'),
        ['deleted' => time()],
        ['survey_id' => $id]
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
