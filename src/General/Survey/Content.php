<?php
/**
* General Survey Content
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Content.php 859 2013-01-02 12:32:12Z kersken $
*/

/**
* General Survey Content class
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*
* @property GeneralSurveyQuestionList $generalSurveyQuestionList
* @property GeneralSurveyUserSubject $generalSurveyUserSubject
* @property GeneralSurveyResult $generalSurveyResult
* @property GeneralSurveySubject $generalSurveySubject
* @property GeneralSurveySubjectList $generalSurveySubjectList
* @property GeneralSurveyUserSubjectList $generalSurveyUserSubjectList
* @property GeneralSurveyQuestionGroupList $generalSurveyQuestionGroupList
* @property GeneralSurveyQuestionGroup $generalSurveyQuestionGroup
* @property GeneralSurveyAnswerList $generalSurveyAnswerList
* @property GeneralSurvey $generalSurvey
* @property GeneralSurveyList $generalSurveyList
*/
class GeneralSurveyContent extends base_object {
  /**
  * Owner object
  * @var GeneralSurveyContentPage
  */
  private $_owner = NULL;

  /**
  * Current user id
  * @var string
  */
  private $_userId = '';

  /**
  * Unanswered questions (to mark as errors)
  * @var array
  */
  protected $_unanswered = array();

  /**
  * Instances of GeneralSurvey* classes
  * @var array
  */
  protected $_instances = array();

  /**
  * Dialog object to be used
  * @var base_dialog
  */
  protected $_dialog = NULL;

  /**
  * Use subject
  * @var boolean
  */
  protected $_useSubjects = TRUE;

  /**
  * Set the owner object
  *
  * @param GeneralSurveyContentPage $owner
  */
  public function setOwner($owner) {
    $this->_owner = $owner;
  }

  /**
  * Set/get the user id
  *
  * @param string $userId optional, default NULL
  * @return string
  */
  public function userId($userId = NULL) {
    if ($userId !== NULL) {
      $this->_userId = $userId;
    }
    return $this->_userId;
  }

  /**
  * Initialize the content class
  */
  public function initializeContent() {
    spl_autoload_register(array($this, 'generalSurveyAutoload'));
  }

  /**
  * Check answers for current question group
  *
  * @param integer $step
  * @return array|boolean 
  */
  public function checkAnswers($step) {
    $result = FALSE;
    $answers = array();
    $questionList = $this->generalSurveyQuestionList;
    if ($questionList->load(array('questiongroup_id' => $step))) {
      foreach ($questionList as $questionId => $data) {
        if (isset($this->_owner->params['question_'.$questionId])) {
          $givenAnswers = $this->_owner->params['question_'.$questionId];
          if (!is_array($givenAnswers)) {
            $givenAnswers = array($givenAnswers);
          }
          foreach ($givenAnswers as $answer) {
            $answers[] = $answer;
          }
        } else {
          $this->_unanswered[] = $questionId;
        }
      }
    }
    if (empty($this->_unanswered)) {
      $result = $answers;
    }
    return $result;
  }

  /**
  * Save the complete survey
  *
  * @param integer $surveyId
  * @param integer $step
  * @return boolean TRUE on success, FALSE otherwise
  */
  public function saveSurvey($surveyId, $step) {
    if ($answers = $this->checkAnswers($step)) {
      $sessionKey = [
        $this,
        'survey_'.$surveyId,
        'subject_'.$this->subject()
      ];
      $answers = array_merge(
        $answers,
        $this->papaya()->session->values->get($sessionKey)
      );
      $result = $this->generalSurveyResult;
      $result['subject_id'] = $this->subject();
      foreach ($answers as $answerId) {
        $result['answer_id'] = $answerId;
        if (!$result->save()) {
          return FALSE;
        }
      }
      if ($this->_useSubjects) {
        $userSubject = $this->generalSurveyUserSubject;
        $userSubject['user_id'] = $this->userId();
        $userSubject['subject_id'] = $this->_owner->params['subject_id'];
        $userSubject->save();
      }
      unset($this->papaya()->session->values[$sessionKey]);
      return TRUE;
    }
    return FALSE;
  }

  /**
  * Save current step's data to session
  *
  * @param integer $surveyId
  * @param integer $step
  * @return boolean
  */
  public function saveToSession($surveyId, $step) {
    $result = FALSE;
    if ($answers = $this->checkAnswers($step)) {
      $result = TRUE;
      $sessionKey = [
        $this,
        'survey_'.$surveyId,
        'subject_'.$this->subject()
      ];
      $sessionValues = $this->papaya()->session->values->get($sessionKey);
      if (empty($sessionValues)) {
        $sessionValues = array();
      }
      foreach ($answers as $answerId) {
        $sessionValues[] = $answerId;
      }
      $this->papaya()->session->values->set($sessionKey, $sessionValues);
    }
    return $result;
  }

  /**
  * Get the dialog to select a subject
  *
  * @param integer $surveyId
  * @return string XML
  */
  public function getSubjectSelector($surveyId) {
    $result = '';
    $subjectList = $this->generalSurveySubjectList;
    if ($subjectList->load(array('survey_id' => $surveyId))) {
      $subjects = [0 => sprintf('[%s]', $this->_owner->data['caption_subject_select'])];
      foreach ($subjectList as $id => $data) {
        $subjects[$id] = papaya_strings::escapeHTMLChars($data['name']);
      }
      $allowed = TRUE;
      if (isset($this->_owner->data['use_login']) &&
          $this->_owner->data['use_login']) {
        $userSubjectList = $this->generalSurveyUserSubjectList;
        if ($userSubjectList->load(array('user_id' => $this->userId()))) {
          foreach ($userSubjectList as $data) {
            unset($subjects[$data['subject_id']]);
          }
        }
        $allowed = count($userSubjectList) < $this->_owner->data['max_subjects'];
      }
      if (!$allowed) {
        $result = $this->getMessageXml($this->_owner->data['error_subjects'], 'info');
      } else {
        $result = '<subject-select>'.LF;
        $fields = array(
          'subject_id' => array(
            $this->_owner->data['caption_subject'],
            'isNum',
            TRUE,
            'combo',
            $subjects
          )
        );
        $data = array();
        if (isset($this->_owner->params['subject_id'])) {
          $data['subject_id'] = $this->_owner->params['subject_id'];
        }
        $subjectDialog = $this->_getDialogObject($fields, $data, array());
        if (is_object($subjectDialog)) {
          $subjectDialog->dialogTitle = papaya_strings::escapeHTMLChars(
            $this->_owner->data['head_select_subject']
          );
          $subjectDialog->buttonTitle = papaya_strings::escapeHTMLChars(
            $this->_owner->data['caption_next']
          );
          $result .= $subjectDialog->getDialogXML();
        }
        $result .= '<subjects>'.LF;
        foreach ($subjects as $id => $name) {
          $result .= sprintf(
            '<subject id="%d">%s</subject>'.LF,
            $id,
            papaya_strings::escapeHTMLChars($name)
          );
        }
        $result .= '</subjects>'.LF;
        $result .= '</subject-select>'.LF;
      }
    }
    return $result;
  }

  /**
  * Get the content of the current survey question group
  *
  * @param integer $surveyId
  * @param string $step
  * @param boolean $error
  * @return string XML
  */
  public function getSurveyContent($surveyId, $step, $error) {
    $result = '';
    if ($error) {
      $result .= $this->getMessageXml($this->_owner->data['error_input']);
    }
    if ($step == 0 &&
        $this->_useSubjects &&
        (!isset($this->_owner->params['subject_id'])  ||
         $this->_owner->params['subject_id'] == 0)) {
      $result .= $this->getSubjectSelector($surveyId);
      return $result;
    }
    if ($this->_useSubjects) {
      $subject = $this->generalSurveySubject;
      if ($subject->load($this->_owner->params['subject_id'])) {
        $result .= sprintf(
          '<subject caption="%s">%s</subject>' . LF,
          papaya_strings::escapeHTMLChars($this->_owner->data['caption_current_subject']),
          $subject['name']
        );
      }
    }
    $questionGroupList = $this->generalSurveyQuestionGroupList;
    if ($questionGroupList->load(array('survey_id' => $surveyId))) {
      $questionGroupId = 0;
      $nextStep = 0;
      foreach ($questionGroupList as $id => $data) {
        if ($questionGroupId == 0 && ($step == 0 || $step == $id)) {
          $questionGroupId = $id;
          continue;
        }
        if ($questionGroupId != 0) {
          $nextStep = $id;
          break;
        }
      }
      $questionGroup = $this->generalSurveyQuestionGroup;
      $questionGroup->load($questionGroupId);
      $result .= '<questiongroup>'.LF;
      $result .= sprintf(
        '<title>%s</title>'.LF,
        $this->_owner->getXHTMLString($questionGroup['title'])
      );
      $result .= sprintf(
        '<text>%s</text>'.LF,
        $this->_owner->getXHTMLString($questionGroup['description'])
      );
      $questionList = $this->generalSurveyQuestionList;
      if ($questionList->load(array('questiongroup_id' => $questionGroupId))) {
        $fields = array();
        $result .= '<questions>'.LF;
        foreach ($questionList as $questionId => $questionData) {
          $answerList = $this->generalSurveyAnswerList;
          if ($answerList->load(array('question_id' => $questionId))) {
            $result .= sprintf('<question id="question_%d">'.LF, $questionId);
            $result .= sprintf(
              '<description>%s</description>'.LF,
              $this->_owner->getXHTMLString($questionData['description'])
            );
            $answers = array();
            foreach ($answerList as $answerId => $answerData) {
              $answers[$answerId] = $answerData['title'];
              $result .= sprintf(
                '<answer id="%d">%s</answer>'.LF,
                $answerId,
                $this->_owner->getXHTMLString($answerData['description'])
              );
            }
            $result .= '</question>'.LF;
            $fieldName = sprintf(
              '%s%s',
              $this->_owner->getXHTMLString($questionData['title']),
              $questionData['type'] == 'single' ? '' : '[]'
            );
            $fields['question_'.$questionId] = array(
              $fieldName,
              'isNoHTML',
              TRUE,
              $questionData['type'] == 'single' ? 'radio' : 'checkgroup',
              $answers
            );
          }
        }
        $result .= '</questions>'.LF;
        if (count($fields) > 0) {
          $hidden = array(
            'step' => $questionGroupId,
            'subject_id' => $this->subject(),
            'save' => 1
          );
          if ($nextStep > 0) {
            $hidden['next-step'] = $nextStep;
          } else {
            $hidden['finalize'] = 1;
          }
          $questionGroupDialog = $this->_getDialogObject($fields, array(), $hidden);
          if (is_object($questionGroupDialog)) {
            $questionGroupDialog->buttonTitle = papaya_strings::escapeHTMLChars(
              $this->_owner->data['caption_next']
            );
            $questionGroupDialog->loadParams();
            $result .= $questionGroupDialog->getDialogXML();
          }
        }
      }
      $result .= '</questiongroup>'.LF;
    }
    return $result;
  }

  /**
  * Get the link to select one more turn
  *
  * @return string XML
  */
  public function getRepeatXml() {
    $allowed = TRUE;
    if (!$this->_useSubjects) {
      $allowed = FALSE;
    } else {
      $subjectCount = 0;
      $userSubjectList = $this->generalSurveyUserSubjectList;
      if ($userSubjectList->load(array('user_id' => $this->userId()))) {
        $subjectCount = count($userSubjectList);
      }
      $allowed = ($subjectCount < $this->_owner->data['max_subjects']);
    }
    if ($allowed) {
      $result = $this->getMessageXml($this->_owner->data['error_subjects'], 'info');
    } else {
      $result = sprintf(
        '<repeat href="%s">%s</repeat>'.LF,
        $this->_owner->getBaseLink(),
        papaya_strings::escapeHTMLChars($this->_owner->data['caption_repeat'])
      );
    }
    return $result;
  }

  /**
  * Get error message output
  *
  * @param string $message
  * @param string $type optional, default 'error'
  * @return string XML
  */
  public function getMessageXml($message, $type = 'error') {
    return sprintf(
      '<message type="%s">%s</message>'.LF,
      $type,
      papaya_strings::escapeHTMLChars($message)
    );
  }

  /**
  * Get the main content output
  *
  * @return string XML
  */
  public function getContentXml() {
    $result = '';
    $surveyId = $this->_owner->data['survey_id'];
    $survey = $this->generalSurvey;
    $step = 0;
    if (isset($this->_owner->params['step'])) {
      $step = $this->_owner->params['step'];
    }
    if ($survey->load($surveyId)) {
      $this->_useSubjects = ($survey['use_subjects'] > 0);
      $finalized = FALSE;
      $saved = FALSE;
      $submitted = isset($this->_owner->params['save']);
      if ($submitted) {
        if (isset($this->_owner->params['finalize']) && $this->_owner->params['finalize'] == 1) {
          $saved = $this->saveSurvey($surveyId, $step);
          if ($saved) {
            $finalized = TRUE;
          }
        } else {
          $saved = $this->saveToSession($surveyId, $step);
        }
        if (!$saved && !empty($this->_unanswered)) {
          $result .= '<unanswered>'.LF;
          foreach ($this->_unanswered as $questionId) {
            $result .= sprintf('<question id="question_%d" />'.LF, $questionId);
          }
          $result .= '</unanswered>'.LF;
        }
      }
      $result .= sprintf(
        '<title>%s</title>'.LF,
        papaya_strings::escapeHTMLChars($survey['title'])
      );
      if ($finalized) {
        $result .= sprintf(
          '<text>%s</text>'.LF,
          $this->_owner->getXHTMLString($this->_owner->data['text_done'])
        );
        $result .= $this->getRepeatXml();
      } else {
        $result .= sprintf(
          '<text>%s</text>'.LF,
          $this->_owner->getXHTMLString($survey['description'])
        );
        if ($saved) {
          $step = $this->_owner->params['next-step'];
        }
        $result .= $this->getSurveyContent($surveyId, $step, $submitted && !$saved);
      }
    } else {
      $result = $this->getMessageXml($this->_owner->data['error_no_survey']);
    }
    return $result;
  }

  /**
  * Get a survey selector
  *
  * @param string $name
  * @param integer $value
  * @return string
  */
  public function getSurveySelector($name, $value) {
    $result = '';
    $surveyList = $this->generalSurveyList;
    if ($surveyList->load()) {
      $result = sprintf(
        '<select name="%s[%s]" class="dialogSelect dialogScale">'.LF,
        $this->_owner->paramName,
        $name
      );
      foreach ($surveyList as $id => $data) {
        $selected = ($value == $id) ? ' selected="selected"' : '';
        $result .= sprintf(
          '<option value="%d"%s>%s</option>'.LF,
          $id,
          $selected,
          papaya_strings::escapeHTMLChars($data['title'])
        );
      }
      $result .= '</select>'.LF;
    }
    return $result;
  }

  /**
  * Get a base_dialog object
  *
  * @param array $fields
  * @param array $data
  * @param array $hidden
  * @return base_dialog
  */
  protected function _getDialogObject($fields, $data, $hidden) {
    if (!is_object($this->_dialog)) {
      $this->_dialog = new base_dialog($this, $this->_owner->paramName, $fields, $data, $hidden);
    }
    return $this->_dialog;
  }

  /**
  * Get current subject or 0 if not using subjects
  *
  * @return int
  */
  private function subject() {
    return $this->_useSubjects ? $this->_owner->params['subject_id'] : 0;
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
      throw new InvalidArgumentException('Class name not allowed.');
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
      if (!isset($this->_instances[$name])) {
        $className = preg_replace('(^g)', 'G', $name);
        $this->_instances[$name] = new $className;
      }
      if (isset($this->_owner) && isset($this->_owner->parentObj) &&
          isset($this->_owner->parentObj->currentLanguage) &&
          is_object($this->_owner->parentObj->currentLanguage)) {
        $this->_instances[$name]->language(
          $this->_owner->parentObj->currentLanguage->id
        );
      } elseif (isset($this->papaya()->administrationLanguage)) {
        $this->_instances[$name]->language(
          $this->papaya()->administrationLanguage->id
        );
      } else {
        throw new RuntimeException('Unable to set language for instance of '.$className);
      }
      return $this->_instances[$name];
    } else {
      throw new InvalidArgumentException('Class name not allowed.');
    }
  }
}
