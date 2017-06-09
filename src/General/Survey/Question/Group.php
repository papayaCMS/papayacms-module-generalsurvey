<?php
/**
* Database access for single survey question group records
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Group.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for single survey question group records
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyQuestionGroup extends PapayaDatabaseObjectRecord {
  /**
  * Mapping array to assign simple names to fields
  * @var array
  */
  protected $_fields = [
    'id' => 'questiongroup_id',
    'survey_id' => 'survey_id',
    'title' => 'questiongroup_title',
    'description' => 'questiongroup_description',
    'order' => 'questiongroup_order'
  ];

  /**
  * Language ID
  * @var integer
  */
  private $_language = 0;

  /**
  * Load a record by id
  *
  * @param integer $id
  * @return boolean
  */
  public function load($id) {
    $result = FALSE;
    $sql = "SELECT g.questiongroup_id, g.survey_id, gt.questiongroup_title,
                   gt.questiongroup_description, g.questiongroup_order
              FROM %s g
             INNER JOIN %s gt
                ON g.questiongroup_id = gt.questiongroup_id
             WHERE g.questiongroup_id = %d
               AND gt.questiongroup_language = %d
               AND g.deleted = 0";
    $parameters = [
      $this->databaseGetTableName('general_survey_questiongroup'),
      $this->databaseGetTableName('general_survey_questiongroup_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $this->_values = $this->convertRecordToValues($row);
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Get another translation if the current language is not available
   *
   * @param integer $id
   * @return array
   */
  public function getAlternateTranslation($id) {
    $result = [];
    $sql = "SELECT g.questiongroup_id, g.survey_id, gt.questiongroup_title,
                   gt.questiongroup_description, g.questiongroup_order
              FROM %s g
             INNER JOIN %s gt
                ON g.questiongroup_id = gt.questiongroup_id
             WHERE g.questiongroup_id = %d
               AND gt.questiongroup_language != %d
               AND g.deleted = 0
             ORDER BY gt.questiongroup_language ASC";
    $parameters = [
      $this->databaseGetTableName('general_survey_questiongroup'),
      $this->databaseGetTableName('general_survey_questiongroup_trans'),
      $id,
      $this->language()
    ];
    if ($res = $this->databaseQueryFmt($sql, $parameters, 1)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $result = [
          'id' => $row['survey_id'],
          'title' => sprintf('[%s]', $row['questiongroup_title']),
          'description' => $row['questiongroup_description'],
          'ADD_TRANSLATION' => 1
        ];
      }
    }
    return $result;
  }

  /**
  * Save the current record to the database
  *
  * @return mixed integer|boolean
  */
  public function save() {
    if (empty($this['id'])) {
      return $this->_insert();
    } else {
      return $this->_update();
    }
  }

  /**
  * Insert current data as new record
  *
  * @return mixed integer new id on success, boolean FALSE otherwise
  */
  private function _insert() {
    $order = $this->_getMaxOrder() + 1;
    $data = [
      'survey_id' => $this['survey_id'],
      'questiongroup_order' => $order,
      'active' => 1,
      'deleted' => 0
    ];
    $id = $this->databaseInsertRecord(
      $this->databaseGetTableName('general_survey_questiongroup'),
      'questiongroup_id',
      $data
    );
    if (FALSE !== $id) {
      $data = [
        'questiongroup_id' => $id,
        'questiongroup_language' => $this->language(),
        'questiongroup_title' => $this['title'],
        'questiongroup_description' => $this['description'],
      ];
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_questiongroup_trans'),
        NULL,
        $data
      );
      $this['order'] = $order;
      if (FALSE !== $success) {
        return $this['id'] = $id;
      }
    }
    return FALSE;
  }

  /**
  * Update existing record or add new translation
  *
  * @return boolean TRUE on success, FALSE otherwise
  */
  private function _update() {
    $sql = "SELECT COUNT(*) num
              FROM %s
             WHERE questiongroup_id = %d
               AND questiongroup_language = %d";
    $parameters = [
      $this->databaseGetTableName('general_survey_questiongroup_trans'),
      $this['id'],
      $this->language()
    ];
    $groupExists = FALSE;
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      if ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $groupExists = ($row['num'] > 0);
      }
    }
    $data = [
      'questiongroup_title' => $this['title'],
      'questiongroup_description' => $this['description']
    ];
    if ($groupExists) {
      $success = $this->databaseUpdateRecord(
        $this->databaseGetTableName('general_survey_questiongroup_trans'),
        $data,
        [
          'questiongroup_id' => $this['id'],
          'questiongroup_language' => $this->language()
        ]
      );
    } else {
      $data['questiongroup_id'] = $this['id'];
      $data['questiongroup_language'] = $this->language();
      $success = $this->databaseInsertRecord(
        $this->databaseGetTableName('general_survey_questiongroup_trans'),
        NULL,
        $data
      );
    }
    return FALSE !== $success;
  }

  /**
  * Get the maximum order number for the current survey id
  *
  * @return integer
  */
  protected function _getMaxOrder() {
    $maxOrder = 0;
    if (isset($this['survey_id'])) {
      $sql = "SELECT MAX(questiongroup_order) max_order
                FROM %s
               WHERE survey_id = %d";
      $parameters = [
        $this->databaseGetTableName('general_survey_questiongroup'),
        $this['survey_id']
      ];
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        if ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
          $maxOrder = $row['max_order'];
        }
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
