<?php
/**
* Database access class providing statistics of survey results
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Statistic.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* Database access class for survey result record lists
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyStatistic extends PapayaDatabaseObject {
  /**
  * Get the statistics
  *
  * @param integer $surveyId
  * @param string $orderBy optional, default 'subject_name'
  * @return array
  */
  public function getData($surveyId, $orderBy = 'subject_name') {
    $sql = "SELECT s.subject_id, s.subject_name, COUNT(*) result_count
              FROM %s s
             INNER JOIN %s us
                ON s.subject_id = us.subject_id
             WHERE s.survey_id = %d
             GROUP BY us.subject_id
             ORDER BY";
    $sql .= ($orderBy == 'result_count' ? " COUNT(*) DESC," : "");
    $sql .= " s.subject_name ASC";
    $parameters = array(
      $this->databaseGetTableName('general_survey_subject'),
      $this->databaseGetTableName('general_survey_user_subject'),
      $surveyId
    );
    $subjects = array();
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      while ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $subjects[$row['subject_id']] = $row;
      }
    }
    if (!empty($subjects)) {
      $sql = "SELECT qg.questiongroup_id, q.question_id,
                     a.answer_id,
                     r.subject_id, r.result_count
                FROM %s qg
               INNER JOIN %s q ON qg.questiongroup_id = q.questiongroup_id
               INNER JOIN %s a ON q.question_id = a.question_id
               INNER JOIN %s r ON a.answer_id = r.answer_id
               WHERE qg.survey_id = %d";
      $parameters = array(
        $this->databaseGetTableName('general_survey_questiongroup'),
        $this->databaseGetTableName('general_survey_question'),
        $this->databaseGetTableName('general_survey_answer'),
        $this->databaseGetTableName('general_survey_result'),
        $surveyId
      );
      if ($res = $this->databaseQueryFmt($sql, $parameters)) {
        while ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
          $sub = $row['subject_id'];
          $qg = $row['questiongroup_id'];
          $q = $row['question_id'];
          $a = $row['answer_id'];
          if (!isset($subjects[$sub]['RESULTS'])) {
            $subjects[$sub]['RESULTS'] = array();
          }
          if (!isset($subjects[$sub]['RESULTS'][$qg])) {
            $subjects[$sub]['RESULTS'][$qg] = array();
          }
          if (!isset($subjects[$sub]['RESULTS'][$qg][$q])) {
            $subjects[$sub]['RESULTS'][$qg][$q] = array();
          }
          $subjects[$sub]['RESULTS'][$qg][$q][$a] = $row['result_count'];
        }
      }
    }
    return $subjects;
  }

  /**
  * Get structure and names of a survey's components
  *
  * @param integer $surveyId
  */
  public function getStructure($surveyId) {
    $sql = "SELECT qg.questiongroup_id, qg.questiongroup_title, qg.questiongroup_order,
                   q.question_id, q.question_title, q.question_order,
                   a.answer_id, a.answer_title, a.answer_order
              FROM %s qg
             INNER JOIN %s q ON qg.questiongroup_id = q.questiongroup_id
             INNER JOIN %s a ON q.question_id = a.question_id
             WHERE qg.survey_id = %d
             ORDER BY qg.questiongroup_order, q.question_order, a.answer_order";
    $parameters = array(
      $this->databaseGetTableName('general_survey_questiongroup'),
      $this->databaseGetTableName('general_survey_question'),
      $this->databaseGetTableName('general_survey_answer'),
      $surveyId
    );
    $result = array();
    if ($res = $this->databaseQueryFmt($sql, $parameters)) {
      while ($row = $res->fetchRow(PapayaDatabaseResult::FETCH_ASSOC)) {
        $qg = $row['questiongroup_id'];
        $q = $row['question_id'];
        $a = $row['answer_id'];
        if (!isset($result[$qg])) {
          $result[$qg] = array(
            'TITLE' => $row['questiongroup_title'],
            'QUESTIONS' => array()
          );
        }
        if (!isset($result[$qg]['QUESTIONS'][$q])) {
          $result[$qg]['QUESTIONS'][$q] = array(
            'TITLE' => $row['question_title'],
            'ANSWERS' => array()
          );
        }
        $result[$qg]['QUESTIONS'][$q]['ANSWERS'][$a] = $row['answer_title'];
      }
    }
    return $result;
  }
}
