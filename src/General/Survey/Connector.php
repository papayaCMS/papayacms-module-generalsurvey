<?php
/**
 * General Survey Connector Module
 *
 * @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
 * @license Papaya Closed License (PCL)
 *
 * @package Papaya-Modules
 * @subpackage GeneralSurvey
 * @version $Id: Administration.php 837 2012-12-14 15:26:21Z kersken $
 */

/**
 * General Survey Connector Module class
 *
 * @package Papaya-Modules
 * @subpackage GeneralSurvey
 *
 * @property GeneralSurveyList $generalSurveyList
 * @property GeneralSurveyQuestionGroupList $generalSurveyQuestionGroupList
 * @property GeneralSurveySubjectList $generalSurveySubjectList
 * @property GeneralSurveyQuestionList $generalSurveyQuestionList
 * @property GeneralSurveyAnswerList $generalSurveyAnswerList
 * @property GeneralSurvey $generalSurvey
 * @property GeneralSurveyQuestionGroup $generalSurveyQuestionGroup
 * @property GeneralSurveySubject $generalSurveySubject
 * @property GeneralSurveyQuestion $generalSurveyQuestion
 * @property GeneralSurveyAnswer $generalSurveyAnswer
 * @property GeneralSurveyStatistic $generalSurveyStatistic
 */
class GeneralSurveyConnector extends base_connector {
  /**
  * Instances of GeneralSurvey* classes
  * @var array
  */
  protected $_instances = [];

  /**
  * Current language
  * @var integer
  */
  private $_language = 0;

  /**
  * Get/set current language
  *
  * @param integer $language optional, default value NULL
  * @return integer
  */
  public function language($language = NULL) {
    if (!is_null($language)) {
      $this->_language = $language;
    }
    return $this->_language;
  }

  /**
  * Get the list of surveys
  *
  * @param integer $limit optional, default value NULL
  * @param integer $offset optional, default value NULL
  * @return array
  */
  public function getSurveys($limit = NULL, $offset = NULL) {
    $result = [];
    $surveys = $this->generalSurveyList;
    if ($surveys->load($limit, $offset)) {
      $result = iterator_to_array($surveys);
    }
    return $result;
  }

  /**
  * Get a list of subjects by survey and optional parent ID
  *
  * @param integer $surveyId
  * @param integer $parentId optional, default value NULL
  * @param integer $limit optional, default value NULL
  * @param integer $offset optional, default value NULL
  * @return array
  */
  public function getSubjects($surveyId, $parentId = NULL, $limit = NULL, $offset = NULL) {
    $result = [];
    $survey = $this->generalSurvey;
    if ($survey->load($surveyId) && $survey['use_subjects'] == 1) {
      $subjects = $this->generalSurveySubjectList;
      $filter = ['survey_id' => $surveyId];
      if ($parentId !== NULL) {
        $filter['parent_id'] = $parentId;
      }
      if ($subjects->load($filter, $limit, $offset)) {
        $result = iterator_to_array($subjects);
      }
    }
    return $result;
  }

  /**
  * Get a list of question groups by survey
  *
  * @param integer $surveyId
  * @param boolean $withQuestions optional, default value FALSE
  * @param boolean $withAnswers optional, default value FALSE
  * @param integer $limit optional, default value NULL
  * @param integer $offset optional, default value NULL
  * @return array
  */
  public function getQuestionGroups($surveyId, $withQuestions = FALSE,
                                    $withAnswers = FALSE, $limit = NULL, $offset = NULL) {
    $result = [];
    $questionGroups = $this->generalSurveyQuestionGroupList;
    $filter = ['survey_id' => $surveyId];
    if ($questionGroups->load($filter, $limit, $offset)) {
      $result = iterator_to_array($questionGroups);
      if ($withQuestions) {
        foreach ($result as $id => $data) {
          $result[$id]['QUESTIONS'] = $this->getQuestions($id, $withAnswers);
        }
      }
    }
    return $result;
  }

  /**
  * Get a list of questions for a question group
  *
  * @param integer $questionGroupId
  * @param boolean $withAnswers optional, default value FALSE
  * @param integer $limit optional, default value NULL
  * @param integer $offset optional, default value NULL
  * @return array
  */
  public function getQuestions($questionGroupId, $withAnswers = FALSE,
                               $limit = NULL, $offset = NULL) {
    $result = [];
    $questions = $this->generalSurveyQuestionList;
    $filter = ['questiongroup_id' => $questionGroupId];
    if ($questions->load($filter, $limit, $offset)) {
      $result = iterator_to_array($questions);
      if ($withAnswers) {
        $questionGroup = $this->generalSurveyQuestionGroup;
        if ($questionGroup->load($questionGroupId)) {
          $survey = $this->generalSurvey;
          if ($survey->load($questionGroup['survey_id']) &&
              $survey['use_answers']) {
            foreach ($result as $id => $data) {
              $answers = $this->generalSurveyAnswerList;
              if ($answers->load(['question_id' => $id])) {
                $result[$id]['ANSWERS'] = iterator_to_array($answers);
              }
            }
          }
        }
      }
    }
    return $result;
  }

  // Helpers

  /**
   * Special Autoloader for the General Survey classes
   *
   * @param string $className
   */
  public function generalSurveyAutoload($className) {
    if ($className == 'GeneralSurvey') {
      include_once(substr(dirname(__FILE__), 0, -7).'/Survey.php');
    } elseif (preg_match('(^GeneralSurvey)', $className)) {
      $fileName = preg_replace('(([A-Z]))', '/$1', substr($className, 13));
      include_once(dirname(__FILE__).'/'.$fileName.'.php');
    }
  }

  /**
   * Magic setter to set GeneralSurvey* instances
   *
   * @param string $name
   * @throws InvalidArgumentException
   * @param object $value
   */
  public function __set($name, $value) {
    if (preg_match('(^generalSurvey)', $name)) {
      $this->_instances[$name] = $value;
    } else {
      throw new InvalidArgumentException('Class name "'.$name.'" not allowed.');
    }
  }

  /**
   * Magic getter to get/initialize GeneralSurvey* instances
   *
   * @param string $name
   * @throws InvalidArgumentException
   * @return object
   */
  public function __get($name) {
    if (preg_match('(^generalSurvey)', $name)) {
      if ($this->language() > 0) {
        if (!isset($this->_instances[$name])) {
          $className = preg_replace('(^g)', 'G', $name);
          $this->_instances[$name] = new $className;
        }
        $this->_instances[$name]->language($this->_language);
        return $this->_instances[$name];
      } else {
        throw new RuntimeException(
          'Language needs to be set in order to instantiate a survey class.'
        );
      }
    } else {
      throw new InvalidArgumentException('Class name "'.$name.'" not allowed.');
    }
  }
}
