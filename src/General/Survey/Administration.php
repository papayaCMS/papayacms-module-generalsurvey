<?php
/**
* General Survey Administration Module
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Administration.php 837 2012-12-14 15:26:21Z kersken $
*/

/**
* General Survey Administration Module class
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
class GeneralSurveyAdministration extends base_object {
  /**
  * The module editor class
  * @var edmodule_general_survey
  */
  public $module = NULL;

  /**
  * Icons
  * @var array
  */
  public $images = array();

  /**
  * Messages to be displayed
  * @var array
  */
  public $msgs = array();

  /**
  * The layout instance to display the UI
  * @var papaya_xsl
  */
  public $layout = NULL;

  /**
  * The current backend user
  * @var auth_user
  */
  public $authUser = NULL;

  /**
  * Parameter namespace
  * @var string
  */
  public $paramName = 'gsrv';

  /**
  * Request parameters
  * @var array
  */
  public $params = array();

  /**
  * Basic link without parameters
  * @var string
  */
  public $baseLink = '';

  /**
  * Mode of operations
  * @var string
  */
  protected $_mode = 'edit';

  /**
  * Dialog to add or edit surveys
  * @var base_dialog
  */
  protected $_surveyDialog = NULL;

  /**
  * Dialog to add or edit question groups
  * @var base_dialog
  */
  protected $_questionGroupDialog = NULL;

  /**
  * Dialog to add or edit subjects
  * @var base_dialog
  */
  protected $_subjectDialog = NULL;

  /**
  * Dialog to add or edit questions
  * @var base_dialog
  */
  protected $_questionDialog = NULL;

  /**
  * Dialog to add or edit answers
  * @var base_dialog
  */
  protected $_answerDialog = NULL;

  /**
  * The dialog object to be used
  * @var base_dialog
  */
  protected $_dialogObject = NULL;

  /**
  * The message dialog object to be used
  * @var base_msgdialog
  */
  protected $_msgDialogObject = NULL;

  /**
  * List of available limit settings
  * @var array
  */
  private $_availableLimits = array(
    10 => 10,
    20 => 20,
    50 => 50
  );

  /**
  * Current limit for subjects
  * @var integer
  */
  protected $_subjectLimit = 10;

  /**
  * Instances of GeneralSurvey* classes
  * @var array
  */
  protected $_instances = array();

  /**
  * Initialize the module
  */
  public function initialize() {
    spl_autoload_register(array($this, 'generalSurveyAutoload'));
    $this->initializeParams();
    if (isset($this->params['subject_limit']) &&
        in_array($this->params['subject_limit'], $this->_availableLimits)) {
      $this->_subjectLimit = $this->params['subject_limit'];
    }
    if (isset($this->params['mode']) && $this->params['mode'] == 'stat') {
      $this->_mode = 'stat';
    }
  }

  /**
  * Execute the module's commands
  */
  public function execute() {
    $cmd = isset($this->params['cmd']) ? $this->params['cmd'] : '';
    switch ($cmd) {
    case 'save_survey':
      $this->saveSurvey();
      break;
    case 'save_questiongroup':
      $this->saveQuestionGroup();
      break;
    case 'save_subject':
      $this->saveSubject();
      break;
    case 'save_question':
      $this->saveQuestion();
      break;
    case 'save_answer':
      $this->saveAnswer();
      break;
    case 'delete_survey':
      $this->deleteSurvey();
      break;
    case 'delete_questiongroup':
      $this->deleteQuestionGroup();
      break;
    case 'delete_question':
      $this->deleteQuestion();
      break;
    case 'delete_answer':
      $this->deleteAnswer();
      break;
    case 'delete_subject':
      $this->deleteSubject();
      break;
    case 'move_up_questiongroup':
      $this->moveUpQuestionGroup();
      break;
    case 'move_down_questiongroup':
      $this->moveDownQuestionGroup();
      break;
    case 'move_up_question':
      $this->moveUpQuestion();
      break;
    case 'move_down_question':
      $this->moveDownQuestion();
      break;
    case 'move_up_answer':
      $this->moveUpAnswer();
      break;
    case 'move_down_answer':
      $this->moveDownAnswer();
      break;
    case 'export_stat':
      $this->exportStatisticCsv();
      break;
    }
  }

  /**
  * Get the module's XML output
  */
  public function getXml() {
    $this->layout->addLeft($this->getSurveyListXml());
    if (isset($this->params['survey_id'])) {
      $survey = $this->loadSurvey($this->params['survey_id']);
      if ($this->_mode == 'stat') {
        $this->layout->addCenter($this->getStatisticXml());
      } else {
        $this->layout->addLeft($this->getQuestionGroupListXml());
        if ($survey['use_subjects']) {
          $this->layout->addLeft($this->getSubjectListXml());
          if (isset($this->params['subject_id'])) {
            $subject = $this->generalSurveySubject;
            if ($subject->load($this->params['subject_id'])) {
              $subjectData = iterator_to_array($subject);
            } else {
              $subjectData = $subject->getAlternateTranslation($this->params['subject_id']);
            }
            if ($subjectData['parent_id'] > 0) {
              $parentId = $subjectData['parent_id'];
            } else {
              $parentId = $this->params['subject_id'];
            }
            $this->layout->addLeft(
              $this->getSubjectListXml($parentId, 'Child subjects')
            );
          }
        }
        if (isset($this->params['questiongroup_id'])) {
          $this->layout->addCenter($this->getQuestionListXml());
          if ($survey['use_answers'] && isset($this->params['question_id'])) {
            $this->layout->addCenter($this->getAnswerListXml());
          }
        }
      }
    }
    $cmd = isset($this->params['cmd']) ? $this->params['cmd'] : '';
    switch ($cmd) {
    case 'add_survey':
    case 'edit_survey':
      $this->layout->addCenter($this->getSurveyDialog());
      break;
    case 'add_questiongroup':
    case 'edit_questiongroup':
      $this->layout->addCenter($this->getQuestionGroupDialog());
      break;
    case 'add_subject':
    case 'edit_subject':
      $this->layout->addCenter($this->getSubjectDialog());
      break;
    case 'add_question':
    case 'edit_question':
      $this->layout->addCenter($this->getQuestionDialog());
      break;
    case 'add_answer':
    case 'edit_answer':
      $this->layout->addCenter($this->getAnswerDialog());
      break;
    case 'delete_survey':
      $this->layout->addCenter($this->getDeleteSurveyDialog());
      break;
    case 'delete_questiongroup':
      $this->layout->addCenter($this->getDeleteQuestionGroupDialog());
      break;
    case 'delete_question':
      $this->layout->addCenter($this->getDeleteQuestionDialog());
      break;
    case 'delete_answer':
      $this->layout->addCenter($this->getDeleteAnswerDialog());
      break;
    case 'delete_subject':
      $this->layout->addCenter($this->getDeleteSubjectDialog());
      break;
    }
  }

  /**
  * Get the list of existing surveys
  *
  * @return string XML
  */
  public function getSurveyListXml() {
    $result = '';
    $list = $this->generalSurveyList->getFull();
    if (count($list) > 0) {
      $result = sprintf('<listview title="%s">'.LF, $this->_gt('Surveys'));
      $result .= '<cols>'.LF;
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Survey'));
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      foreach ($list as $id => $record) {
        $selected = '';
        if (isset($this->params['survey_id']) && $this->params['survey_id'] == $id) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf(
          '<listitem href="%s" title="%s"%s />'.LF,
          $this->_getLinkWithParams(
            array('cmd' => 'edit_survey', 'survey_id' => $id),
            $this->paramName
          ),
          papaya_strings::escapeHTMLChars($record['title']),
          $selected
        );
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
    }
    return $result;
  }

  /**
  * Get the list of existing question groups for current survey
  *
  * @return string XML
  */
  public function getQuestionGroupListXml() {
    $result = '';
    if (isset($this->params['survey_id'])) {
      $array = $this->generalSurveyQuestionGroupList->getFull(
        ['survey_id' => $this->params['survey_id']]
      );
      if (count($array) > 0) {
        $result = sprintf('<listview title="%s">' . LF, $this->_gt('Question groups'));
        $result .= '<cols>' . LF;
        $result .= sprintf('<col>%s</col>' . LF, $this->_gt('Group'));
        $result .= '<col />' . LF;
        $result .= '<col />' . LF;
        $result .= '</cols>' . LF;
        $result .= '<items>' . LF;
        $counter = 0;
        foreach ($array as $id => $record) {
          $counter++;
          $selected = '';
          if (isset($this->params['questiongroup_id']) &&
            $this->params['questiongroup_id'] == $id
          ) {
            $selected = ' selected="selected"';
          }
          $result .= sprintf(
            '<listitem href="%s" title="%s"%s>' . LF,
            $this->_getLinkWithParams(
              array(
                'cmd' => 'edit_questiongroup',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $id
              )
            ),
            papaya_strings::escapeHTMLChars($record['title']),
            $selected
          );
          if ($record['order'] > 1) {
            $result .= sprintf(
              '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>' . LF,
              $this->_getLinkWithParams(
                array(
                  'cmd' => 'move_up_questiongroup',
                  'survey_id' => $this->params['survey_id'],
                  'questiongroup_id' => $id
                )
              ),
              papaya_strings::escapeHTMLChars($this->images['actions-go-up']),
              $this->_gt('Move up')
            );
          } else {
            $result .= '<subitem />' . LF;
          }
          if ($counter < count($array)) {
            $result .= sprintf(
              '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>' . LF,
              $this->_getLinkWithParams(
                array(
                  'cmd' => 'move_down_questiongroup',
                  'survey_id' => $this->params['survey_id'],
                  'questiongroup_id' => $id
                )
              ),
              papaya_strings::escapeHTMLChars($this->images['actions-go-down']),
              $this->_gt('Move down')
            );
          } else {
            $result .= '<subitem />' . LF;
          }
          $result .= '</listitem>' . LF;
        }
        $result .= '</items>' . LF;
        $result .= '</listview>' . LF;
      }
    }
    return $result;
  }

  /**
  * Get the list of existing subjects for the current query
  *
  * @param integer $parentId optional, default 0
  * @param string $listTitle optional, default 'Subjects'
  * @return string XML
  */
  public function getSubjectListXml($parentId = 0, $listTitle = 'Subjects') {
    $result = '';
    $limit = ($parentId == 0) ? $this->_subjectLimit : NULL;
    $offset = ($parentId == 0 && isset($this->params['subject_offset'])) ?
      $this->params['subject_offset'] :
      0;
    $list = $this->generalSurveySubjectList->getFull(
          [
            'survey_id' => $this->params['survey_id'],
            'parent_id' => $parentId
          ],
          $limit,
          $offset
        );
    if (!empty($list)) {
      $result .= sprintf('<listview title="%s">'.LF, $this->_gt($listTitle));
      if ($parentId == 0) {
        $result .= '<buttons>' . LF;
        $result .= $this->getSubjectPagingXml(
          $this->generalSurveySubjectList->getAbsCount()
        );
        $result .= $this->getSubjectLimitButtonsXml();
        $result .= '</buttons>' . LF;
      }
      $result .= '<cols>'.LF;
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Subject'));
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      foreach ($list as $id => $record) {
        $selected = '';
        if (isset($this->params['subject_id']) &&
            $this->params['subject_id'] == $id) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf(
          '<listitem href="%s" title="%s"%s />'.LF,
          $this->_getLinkWithParams(
            array(
              'cmd' => 'edit_subject',
              'survey_id' => $this->params['survey_id'],
              'subject_id' => $id
            )
          ),
          papaya_strings::escapeHTMLChars($record['name']),
          $selected
        );
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
    }
    return $result;
  }

  /**
  * Get the list of existing questions for the current question group
  *
  * @return string XML
  */
  public function getQuestionListXml() {
    $result = '';
    $array = $this->generalSurveyQuestionList->getFull(
      ['questiongroup_id' => $this->params['questiongroup_id']]
    );
    if (count($array) > 0) {
      $result = sprintf('<listview title="%s">'.LF, $this->_gt('Questions'));
      $result .= '<cols>'.LF;
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Question'));
      $result .= '<col />'.LF;
      $result .= '<col />'.LF;
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      $counter = 0;
      foreach ($array as $id => $record) {
        $counter++;
        $selected = '';
        if (isset($this->params['question_id']) &&
            $this->params['question_id'] == $id) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf(
          '<listitem href="%s" title="%s"%s>'.LF,
          $this->_getLinkWithParams(
            array(
              'cmd' => 'edit_question',
              'survey_id' => $this->params['survey_id'],
              'questiongroup_id' => $this->params['questiongroup_id'],
              'question_id' => $id
            )
          ),
          papaya_strings::escapeHTMLChars($record['title']),
          $selected
        );
        if ($record['order'] > 1) {
          $result .= sprintf(
            '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>'.LF,
            $this->_getLinkWithParams(
              array(
                'cmd' => 'move_up_question',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id'],
                'question_id' => $id
              )
            ),
            papaya_strings::escapeHTMLChars($this->images['actions-go-up']),
            $this->_gt('Move up')
          );
        } else {
          $result .= '<subitem />'.LF;
        }
        if ($counter < count($array)) {
          $result .= sprintf(
            '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>'.LF,
            $this->_getLinkWithParams(
              array(
                'cmd' => 'move_down_question',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id'],
                'question_id' => $id
              )
            ),
            papaya_strings::escapeHTMLChars($this->images['actions-go-down']),
            $this->_gt('Move down')
          );
        } else {
          $result .= '<subitem />'.LF;
        }
        $result .= '</listitem>'.LF;
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
    }
    return $result;
  }

  /**
  * Get the list of existing answers for the current question
  *
  * @return string XML
  */
  public function getAnswerListXml() {
    $result = '';
    $array = $this->generalSurveyAnswerList->getFull(
      ['question_id' => $this->params['question_id']]
    );
    if (!empty($array)) {
      if (count($array) > 0) {
      $result = sprintf('<listview title="%s">'.LF, $this->_gt('Answers'));
      $result .= '<cols>'.LF;
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Answer'));
      $result .= '<col />'.LF;
      $result .= '<col />'.LF;
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      $counter = 0;
      foreach ($array as $id => $record) {
        $counter++;
        $selected = '';
        if (isset($this->params['answer_id']) &&
            $this->params['answer_id'] == $id) {
          $selected = ' selected="selected"';
        }
        $result .= sprintf(
          '<listitem href="%s" title="%s"%s>'.LF,
          $this->_getLinkWithParams(
            array(
              'cmd' => 'edit_answer',
              'survey_id' => $this->params['survey_id'],
              'questiongroup_id' => $this->params['questiongroup_id'],
              'question_id' => $this->params['question_id'],
              'answer_id' => $id
            )
          ),
          papaya_strings::escapeHTMLChars($record['title']),
          $selected
        );
        if ($record['order'] > 1) {
          $result .= sprintf(
            '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>'.LF,
            $this->_getLinkWithParams(
              array(
                'cmd' => 'move_up_answer',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id'],
                'question_id' => $this->params['question_id'],
                'answer_id' => $id
              )
            ),
            papaya_strings::escapeHTMLChars($this->images['actions-go-up']),
            $this->_gt('Move up')
          );
        } else {
          $result .= '<subitem />'.LF;
        }
        if ($counter < count($array)) {
          $result .= sprintf(
            '<subitem><a href="%s"><glyph src="%s" hint="%s" /></a></subitem>'.LF,
            $this->_getLinkWithParams(
              array(
                'cmd' => 'move_down_answer',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id'],
                'question_id' => $this->params['question_id'],
                'answer_id' => $id
              )
            ),
            papaya_strings::escapeHTMLChars($this->images['actions-go-down']),
            $this->_gt('Move down')
          );
        } else {
          $result .= '<subitem />'.LF;
        }
        $result .= '</listitem>'.LF;
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
      }
    }
    return $result;
  }

  /**
  * Get the dialog to add/edit surveys
  *
  * @return string XML
  */
  public function getSurveyDialog() {
    $result = '';
    $this->initializeSurveyDialog();
    if (is_object($this->_surveyDialog)) {
      $result = $this->_surveyDialog->getDialogXML();
    }
    return $result;
  }

  /**
  * Initialize the dialog to add/edit surveys
  */
  public function initializeSurveyDialog() {
    if (!is_object($this->_surveyDialog)) {
      $fields = [
        'use_subjects' => [
          'Use subjects',
          'isNum',
          TRUE,
          'radio',
          [0 => 'No', 1 => 'Yes'],
          '', 1
        ],
        'use_answers' => [
          'Use answers',
          'isNum',
          TRUE,
          'radio',
          [0 => 'No', 1 => 'Yes'],
          '', 1
        ],
        'title' => ['Title', 'isNoHTML', TRUE, 'input', 255],
        'description' => ['Description', 'isSomeText', FALSE, 'richtext', 7]
      ];
      if (isset($this->params['survey_id'])) {
        $data = $this->loadSurvey($this->params['survey_id']);
        $hidden = array('cmd' => 'save_survey');
        if (!empty($data)) {
          $hidden['survey_id'] = $this->params['survey_id'];
        }
        $hidden = $this->_getLinkParams($hidden);
        $this->_surveyDialog = $this->_getDialogObject($fields, $data, $hidden);
        if (is_object($this->_surveyDialog)) {
          if (empty($data)) {
            $this->_surveyDialog->dialogTitle = $this->_gt('Add survey');
            $this->_surveyDialog->buttonTitle = $this->_gt('Add');
          } elseif (isset($data['ADD_TRANSLATION'])) {
            $this->_surveyDialog->dialogTitle = $this->_gt('Add translation');
            $this->_surveyDialog->buttonTitle = $this->_gt('Save');
          } else {
            $this->_surveyDialog->dialogTitle = $this->_gt('Edit survey');
            $this->_surveyDialog->buttonTitle = $this->_gt('Save');
          }
          $this->_surveyDialog->loadParams();
        }
      }
    }
  }

  /**
  * Get the dialog to add/edit question groups
  */
  public function getQuestionGroupDialog() {
    $result = '';
    $this->initializeQuestionGroupDialog();
    if (is_object($this->_questionGroupDialog)) {
      $result = $this->_questionGroupDialog->getDialogXML();
    }
    return $result;
  }

  /**
  * Initialize the dialog to add/edit question groups
  */
  public function initializeQuestionGroupDialog() {
    if (!is_object($this->_questionGroupDialog)) {
      $fields = array(
        'title' => array('Title', 'isNoHTML', TRUE, 'input', 255),
        'description' => array('Description', 'isSomeText', FALSE, 'richtext', 7)
      );
      $data = array();
      if (isset($this->params['questiongroup_id'])) {
        $questionGroup = $this->generalSurveyQuestionGroup;
        if ($questionGroup->load($this->params['questiongroup_id'])) {
          $data['title'] = $questionGroup['title'];
          $data['description'] = $questionGroup['description'];
        } else {
          $data = $questionGroup->getAlternateTranslation($this->params['questiongroup_id']);
        }
      }
      $hidden = array('cmd' => 'save_questiongroup', 'survey_id' => $this->params['survey_id']);
      if (!empty($data)) {
        $hidden['questiongroup_id'] = $this->params['questiongroup_id'];
      }
      $hidden = $this->_getLinkParams($hidden);
      $this->_questionGroupDialog = $this->_getDialogObject($fields, $data, $hidden);
      if (is_object($this->_questionGroupDialog)) {
        if (empty($data)) {
          $this->_questionGroupDialog->dialogTitle = $this->_gt('Add question group');
          $this->_questionGroupDialog->buttonTitle = $this->_gt('Add');
        } elseif (isset($data['ADD_TRANSLATION'])) {
          $this->_questionGroupDialog->dialogTitle = $this->_gt('Add translation');
          $this->_questionGroupDialog->buttonTitle = $this->_gt('Save');
        } else {
          $this->_questionGroupDialog->dialogTitle = $this->_gt('Edit question group');
          $this->_questionGroupDialog->buttonTitle = $this->_gt('Save');
        }
        $this->_questionGroupDialog->loadParams();
      }
    }
  }

  /**
  * Get the dialog to add/edit subjects
  *
  * @return string XML
  */
  public function getSubjectDialog() {
    $result = '';
    $this->initializeSubjectDialog();
    if (is_object($this->_subjectDialog)) {
      $result = $this->_subjectDialog->getDialogXML();
    }
    return $result;
  }

  /**
  * Initialize the dialog to add/edit subjects
  */
  public function initializeSubjectDialog() {
    if (!is_object($this->_subjectDialog)) {
      $subjects = [];
      $subjectList = $this->generalSurveySubjectList->getFull(
        [
          'survey_id' => $this->params['survey_id'],
          'parent_id' => 0
        ]
      );
      if (count($subjectList) > 0) {
        foreach ($subjectList as $id => $data) {
          $subjects[$id] = $data['name'];
        }
      }
      if (isset($this->params['subject_id']) && isset($subjects[$this->params['subject_id']])) {
        unset($subjects[$this->params['subject_id']]);
      }
      $subjects = array_merge(
        array(0 => sprintf('[%s]', $this->_gt('None'))),
        $subjects
      );
      $fields = array(
        'name' => array('Name', 'isNoHTML', TRUE, 'input', 255),
        'parent_id' => array('Parent id', 'isNum', TRUE, 'combo', $subjects, '', 0)
      );
      $data = [];
      if (isset($this->params['subject_id'])) {
        $subject = $this->generalSurveySubject;
        if ($subject->load($this->params['subject_id'])) {
          $data['name'] = $subject['name'];
          $data['parent_id'] = $subject['parent_id'];
        } else {
          $data = $subject->getAlternateTranslation($this->params['subject_id']);
        }
      }
      $hidden = array(
        'cmd' => 'save_subject',
        'survey_id' => $this->params['survey_id']
      );
      if (!empty($data)) {
        $hidden['subject_id'] = $this->params['subject_id'];
      }
      $hidden = $this->_getLinkParams($hidden);
      $this->_subjectDialog = $this->_getDialogObject($fields, $data, $hidden);
      if (is_object($this->_subjectDialog)) {
        if (empty($data)) {
          $this->_subjectDialog->dialogTitle = $this->_gt('Add subject');
          $this->_subjectDialog->buttonTitle = $this->_gt('Add');
        } elseif (isset($data['ADD_TRANSLATION'])) {
          $this->_subjectDialog->dialogTitle = $this->_gt('Add translation');
          $this->_subjectDialog->buttonTitle = $this->_gt('Save');
        } else {
          $this->_subjectDialog->dialogTitle = $this->_gt('Edit subject');
          $this->_subjectDialog->buttonTitle = $this->_gt('Save');
        }
        $this->_subjectDialog->loadParams();
      }
    }
  }

  /**
  * Get the dialog to add/edit questions
  */
  public function getQuestionDialog() {
    $result = '';
    $this->initializeQuestionDialog();
    if (is_object($this->_questionDialog)) {
      $result = $this->_questionDialog->getDialogXML();
    }
    return $result;
  }

  /**
  * Initialize the dialog to add/edit questions
  */
  public function initializeQuestionDialog() {
    if (!is_object($this->_questionDialog)) {
      $fields = array(
        'title' => array('Title', 'isNoHTML', TRUE, 'input', 255),
        'description' => array('Description', 'isSomeText', FALSE, 'richtext', 7),
        'type' => array(
          'Type',
          '(single|multiple)',
          TRUE,
          'radio',
          array('single' => 'single', 'multiple' => 'multiple'),
          '',
          'single'
        )
      );
      $data = array();
      if (isset($this->params['question_id'])) {
        $question = $this->generalSurveyQuestion;
        if ($question->load($this->params['question_id'])) {
          $data['title'] = $question['title'];
          $data['description'] = $question['description'];
          $data['type'] = $question['type'];
        } else {
          $data = $question->getAlternateTranslation($this->params['question_id']);
        }
      }
      $hidden = array(
        'cmd' => 'save_question',
        'survey_id' => $this->params['survey_id'],
        'questiongroup_id' => $this->params['questiongroup_id']
      );
      if (!empty($data)) {
        $hidden['question_id'] = $this->params['question_id'];
      }
      $hidden = $this->_getLinkParams($hidden);
      $this->_questionDialog = $this->_getDialogObject($fields, $data, $hidden);
      if (is_object($this->_questionDialog)) {
        if (empty($data)) {
          $this->_questionDialog->dialogTitle = $this->_gt('Add question');
          $this->_questionDialog->buttonTitle = $this->_gt('Add');
        } elseif (isset($data['ADD_TRANSLATION'])) {
          $this->_questionDialog->dialogTitle = $this->_gt('Add translation');
          $this->_questionDialog->buttonTitle = $this->_gt('Save');
        } else {
          $this->_questionDialog->dialogTitle = $this->_gt('Edit question');
          $this->_questionDialog->buttonTitle = $this->_gt('Save');
        }
        $this->_questionDialog->loadParams();
      }
    }
  }

  /**
  * Get the dialog to add/edit answers
  */
  public function getAnswerDialog() {
    $result = '';
    $this->initializeAnswerDialog();
    if (is_object($this->_answerDialog)) {
      $result = $this->_answerDialog->getDialogXML();
    }
    return $result;
  }

  /**
  * Initialize the dialog to add/edit answers
  */
  public function initializeAnswerDialog() {
    if (!is_object($this->_answerDialog)) {
      $fields = array(
        'title' => array('Title', 'isNoHTML', TRUE, 'input', 255),
        'description' => array('Description', 'isSomeText', FALSE, 'richtext', 7)
      );
      $data = [];
      if (isset($this->params['answer_id'])) {
        $answer = $this->generalSurveyAnswer;
        if ($answer->load($this->params['answer_id'])) {
          $data['title'] = $answer['title'];
          $data['description'] = $answer['description'];
        } else {
          $data = $this->generalSurveyAnswer->getAlternateTranslation(
            $this->params['answer_id']
          );
        }
      }
      $hidden = array(
        'cmd' => 'save_answer',
        'survey_id' => $this->params['survey_id'],
        'questiongroup_id' => $this->params['questiongroup_id'],
        'question_id' => $this->params['question_id']
      );
      if (!empty($data)) {
        $hidden['answer_id'] = $this->params['answer_id'];
      }
      $hidden = $this->_getLinkParams($hidden);
      $this->_answerDialog = $this->_getDialogObject($fields, $data, $hidden);
      if (is_object($this->_answerDialog)) {
        if (empty($data)) {
          $this->_answerDialog->dialogTitle = $this->_gt('Add answer');
          $this->_answerDialog->buttonTitle = $this->_gt('Add');
        } elseif (isset($data['ADD_TRANSLATION'])) {
          $this->_answerDialog->dialogTitle = $this->_gt('Add translation');
          $this->_answerDialog->buttonTitle = $this->_gt('Save');
        } else {
          $this->_answerDialog->dialogTitle = $this->_gt('Edit answer');
          $this->_answerDialog->buttonTitle = $this->_gt('Save');
        }
        $this->_answerDialog->loadParams();
      }
    }
  }

  /**
  * Get the confirmation dialog to delete a survey
  *
  * @return string XML
  */
  public function getDeleteSurveyDialog() {
    $result = '';
    if (isset($this->params['survey_id']) && !isset($this->params['confirm_delete'])) {
      $survey = $this->generalSurvey;
      if ($survey->load($this->params['survey_id'])) {
        $data = iterator_to_array($survey);
      } else {
        $data = $survey->getAlternateTranslation($this->params['survey_id']);
      }
      if (!empty($data)) {
        $hidden = $this->_getLinkParams(
          array(
            'cmd' => 'delete_survey',
            'survey_id' => $this->params['survey_id'],
            'confirm_delete' => 1
          )
        );
        $dialog = $this->_getMsgDialogObject(
          $hidden,
          sprintf(
            $this->_gt('Really delete survey "%s"?'),
            papaya_strings::escapeHTMLChars($data['title'])
          )
        );
        if (is_object($dialog)) {
          $dialog->buttonTitle = 'Delete';
          $result = $dialog->getMsgDialog();
        }
      }
    }
    return $result;
  }

  /**
  * Get the confirmation dialog to delete a question group
  *
  * @return string XML
  */
  public function getDeleteQuestionGroupDialog() {
    $result = '';
    if (isset($this->params['questiongroup_id']) && !isset($this->params['confirm_delete'])) {
      $questionGroup = $this->generalSurveyQuestionGroup;
      if ($questionGroup->load($this->params['questiongroup_id'])) {
        $data = iterator_to_array($questionGroup);
      } else {
        $data = $questionGroup->getAlternateTranslation($this->params['questiongroup_id']);
      }
      if (!empty($data)) {
        $hidden = $this->_getLinkParams(
          array(
            'cmd' => 'delete_questiongroup',
            'survey_id' => $this->params['survey_id'],
            'questiongroup_id' => $this->params['questiongroup_id'],
            'confirm_delete' => 1
          )
        );
        $dialog = $this->_getMsgDialogObject(
          $hidden,
          sprintf(
            $this->_gt('Really delete question group "%s"?'),
            papaya_strings::escapeHTMLChars($data['title'])
          )
        );
        if (is_object($dialog)) {
          $dialog->buttonTitle = 'Delete';
          $result = $dialog->getMsgDialog();
        }
      }
    }
    return $result;
  }

  /**
  * Get the confirmation dialog to delete a question
  *
  * @return string XML
  */
  public function getDeleteQuestionDialog() {
    $result = '';
    if (isset($this->params['question_id']) && !isset($this->params['confirm_delete'])) {
      $question = $this->generalSurveyQuestion;
      if ($question->load($this->params['question_id'])) {
        $data = iterator_to_array($question);
      } else {
        $data = $question->getAlternateTranslation($this->params['question_id']);
      }
      if (!empty($data)) {
        $hidden = $this->_getLinkParams(
          array(
            'cmd' => 'delete_question',
            'survey_id' => $this->params['survey_id'],
            'questiongroup_id' => $this->params['questiongroup_id'],
            'question_id' => $this->params['question_id'],
            'confirm_delete' => 1
          )
        );
        $dialog = $this->_getMsgDialogObject(
          $hidden,
          sprintf(
            $this->_gt('Really delete question "%s"?'),
            papaya_strings::escapeHTMLChars($data['title'])
          )
        );
        if (is_object($dialog)) {
          $dialog->buttonTitle = 'Delete';
          $result = $dialog->getMsgDialog();
        }
      }
    }
    return $result;
  }

  /**
  * Get the confirmation dialog to delete an answer
  *
  * @return string XML
  */
  public function getDeleteAnswerDialog() {
    $result = '';
    if (isset($this->params['answer_id']) && !isset($this->params['confirm_delete'])) {
      $answer = $this->generalSurveyAnswer;
      if ($answer->load($this->params['answer_id'])) {
        $data = iterator_to_array($answer);
      } else {
        $data = $answer->getAlternateTranslation($this->params['answer_id']);
      }
      if (!empty($data)) {
        $hidden = $this->_getLinkParams(
          array(
            'cmd' => 'delete_answer',
            'survey_id' => $this->params['survey_id'],
            'questiongroup_id' => $this->params['questiongroup_id'],
            'question_id' => $this->params['question_id'],
            'answer_id' => $this->params['answer_id'],
            'confirm_delete' => 1
          )
        );
        $dialog = $this->_getMsgDialogObject(
          $hidden,
          sprintf(
            $this->_gt('Really delete answer "%s"?'),
            papaya_strings::escapeHTMLChars($data['title'])
          )
        );
        if (is_object($dialog)) {
          $dialog->buttonTitle = 'Delete';
          $result = $dialog->getMsgDialog();
        }
      }
    }
    return $result;
  }

  /**
  * Get the confirmation dialog to delete a subject
  *
  * @return string XML
  */
  public function getDeleteSubjectDialog() {
    $result = '';
    if (isset($this->params['subject_id']) && !isset($this->params['confirm_delete'])) {
      $subject = $this->generalSurveySubject;
      if ($subject->load($this->params['subject_id'])) {
        $data = iterator_to_array($subject);
      } else {
        $data = $subject->getAlternateTranslation($this->params['subject_id']);
      }
      if (!empty($data)) {
        $hidden = $this->_getLinkParams(
          array(
            'cmd' => 'delete_subject',
            'survey_id' => $this->params['survey_id'],
            'subject_id' => $this->params['subject_id'],
            'confirm_delete' => 1
          )
        );
        $dialog = $this->_getMsgDialogObject(
          $hidden,
          sprintf(
            $this->_gt('Really delete subject "%s"?'),
            papaya_strings::escapeHTMLChars($data['name'])
          )
        );
        if (is_object($dialog)) {
          $dialog->buttonTitle = 'Delete';
          $result = $dialog->getMsgDialog();
        }
      }
    }
    return $result;
  }

  /**
  * Save a survey
  */
  public function saveSurvey() {
    $this->initializeSurveyDialog();
    if ($this->_surveyDialog->checkDialogInput()) {
      $survey = $this->generalSurvey;
      $survey['use_subjects'] = $this->params['use_subjects'];
      $survey['use_answers'] = $this->params['use_answers'];
      $survey['title'] = $this->params['title'];
      $survey['description'] = $this->params['description'];
      if (isset($this->params['survey_id'])) {
        $survey['id'] = $this->params['survey_id'];
      }
      $success = $survey->save();
      if (FALSE !== $success) {
        if (!isset($this->params['survey_id'])) {
          $this->params['survey_id'] = $success;
        }
        $this->addMsg(MSG_INFO, $this->_gt('Survey successfully saved.'));
      } else {
        $this->addMsg(MSG_ERROR, $this->_gt('Error: could not save survey.'));
      }
    } else {
      $this->layout->addCenter($this->getSurveyDialog());
    }
  }

  /**
  * Save a question group
  */
  public function saveQuestionGroup() {
    $this->initializeQuestionGroupDialog();
    if ($this->_questionGroupDialog->checkDialogInput()) {
      $questionGroup = $this->generalSurveyQuestionGroup;
      $questionGroup['title'] = $this->params['title'];
      $questionGroup['description'] = $this->params['description'];
      $questionGroup['survey_id'] = $this->params['survey_id'];
      if (isset($this->params['questiongroup_id'])) {
        $questionGroup['id'] = $this->params['questiongroup_id'];
      }
      $success = $questionGroup->save();
      if (FALSE !== $success) {
        if (!isset($this->params['questiongroup_id'])) {
          $this->params['questiongroup_id'] = $success;
        }
        $this->addMsg(MSG_INFO, $this->_gt('Question group successfully saved.'));
      } else {
        $this->addMsg(MSG_ERROR, $this->_gt('Error: could not save question group.'));
      }
    } else {
      $this->layout->addCenter($this->getQuestionGroupDialog());
    }
  }

  /**
  * Save a subject
  */
  public function saveSubject() {
    $this->initializeSubjectDialog();
    if ($this->_subjectDialog->checkDialogInput()) {
      $subject = $this->generalSurveySubject;
      $subject['name'] = $this->params['name'];
      $subject['parent_id'] = $this->params['parent_id'];
      $subject['survey_id'] = $this->params['survey_id'];
      if (isset($this->params['subject_id'])) {
        $subject['id'] = $this->params['subject_id'];
      }
      $success = $subject->save();
      if (FALSE !== $success) {
        if (!isset($this->params['subject_id'])) {
          $this->params['subject_id'] = $success;
        }
        $this->addMsg(MSG_INFO, $this->_gt('Subject successfully saved.'));
      } else {
        $this->addMsg(MSG_ERROR, $this->_gt('Error: could not save subject.'));
      }
    } else {
      $this->layout->addCenter($this->getSubjectDialog());
    }
  }

  /**
  * Save a question
  */
  public function saveQuestion() {
    $this->initializeQuestionDialog();
    if ($this->_questionDialog->checkDialogInput()) {
      $question = $this->generalSurveyQuestion;
      $question['title'] = $this->params['title'];
      $question['description'] = $this->params['description'];
      $question['type'] = $this->params['type'];
      $question['questiongroup_id'] = $this->params['questiongroup_id'];
      if (isset($this->params['question_id'])) {
        $question['id'] = $this->params['question_id'];
      }
      $success = $question->save();
      if (FALSE !== $success) {
        if (!isset($this->params['question_id'])) {
          $this->params['question_id'] = $success;
        }
        $this->addMsg(MSG_INFO, $this->_gt('Question successfully saved.'));
      } else {
        $this->addMsg(MSG_ERROR, $this->_gt('Error: could not save question.'));
      }
    } else {
      $this->layout->addCenter($this->getQuestionDialog());
    }
  }

  /**
  * Save an answer
  */
  public function saveAnswer() {
    $this->initializeAnswerDialog();
    if ($this->_answerDialog->checkDialogInput()) {
      $answer = $this->generalSurveyAnswer;
      $answer['title'] = $this->params['title'];
      $answer['description'] = $this->params['description'];
      $answer['question_id'] = $this->params['question_id'];
      if (isset($this->params['answer_id'])) {
        $answer['id'] = $this->params['answer_id'];
      }
      $success = $answer->save();
      if (FALSE !== $success) {
        if (!isset($this->params['answer_id'])) {
          $this->params['answer_id'] = $success;
        }
        $this->addMsg(MSG_INFO, $this->_gt('Answer successfully saved.'));
      } else {
        $this->addMsg(MSG_ERROR, $this->_gt('Error: could not save answer.'));
      }
    } else {
      $this->layout->addCenter($this->getAnswerDialog());
    }
  }

  /**
  * Delete a survey
  */
  public function deleteSurvey() {
    if (isset($this->params['survey_id']) && isset($this->params['confirm_delete'])) {
      $questionGroupList = $this->generalSurveyQuestionGroupList;
      $questionGroupList->load(array('survey_id' => $this->params['survey_id']));
      if (count(iterator_to_array($questionGroupList)) > 0) {
        $questionGroupIds = array();
        foreach ($questionGroupList as $questionGroupId => $questionGroupData) {
          $questionGroupIds[] = $questionGroupId;
          $questionGroupList->delete($questionGroupId);
        }
        $questionList = $this->generalSurveyQuestionList;
        $questionList->load(array('questiongroup_id' => $questionGroupIds));
        if (count(iterator_to_array($questionList)) > 0) {
          $questionIds = array();
          foreach ($questionList as $questionId => $questionData) {
            $questionIds[] = $questionId;
            $questionList->delete($questionId);
          }
          $answerList = $this->generalSurveyAnswerList;
          $answerList->load(array('question_id' => $questionIds));
          if (count(iterator_to_array($answerList)) > 0) {
            foreach ($answerList as $answerId => $answerData) {
              $answerList->delete($answerId);
            }
          }
        }
      }
      $subjectList = $this->generalSurveySubjectList;
      $subjectList->load(array('survey_id' => $this->params['survey_id']));
      if (count(iterator_to_array($subjectList)) > 0) {
        foreach ($subjectList as $subjectId => $subjectData) {
          $subjectList->delete($subjectId);
        }
      }
      $list = $this->generalSurveyList;
      if ($list->delete($this->params['survey_id'])) {
        unset($this->params['survey_id']);
        $this->addMsg(MSG_INFO, 'Survey successfully deleted.');
      } else {
        $this->addMsg(MSG_ERROR, 'Error: could not delete survey.');
      }
    }
  }

  /**
  * Delete a question group
  */
  public function deleteQuestionGroup() {
    if (isset($this->params['questiongroup_id']) && isset($this->params['confirm_delete'])) {
      $questionList = $this->generalSurveyQuestionList;
      $questionList->load(array('questiongroup_id' => $this->params['questiongroup_id']));
      if (count(iterator_to_array($questionList)) > 0) {
        $questionIds = array();
        foreach ($questionList as $questionId => $questionData) {
          $questionIds[] = $questionId;
          $questionList->delete($questionId);
        }
        $answerList = $this->generalSurveyAnswerList;
        $answerList->load(array('question_id' => $questionIds));
        if (count(iterator_to_array($answerList)) > 0) {
          foreach ($answerList as $answerId => $answerData) {
            $answerList->delete($answerId);
          }
        }
      }
      $list = $this->generalSurveyQuestionGroupList;
      if ($list->delete($this->params['questiongroup_id'])) {
        unset($this->params['questiongroup_id']);
        $this->addMsg(MSG_INFO, 'Question group successfully deleted.');
      } else {
        $this->addMsg(MSG_ERROR, 'Error: could not delete question group.');
      }
    }
  }

  /**
  * Delete a question
  */
  public function deleteQuestion() {
    if (isset($this->params['question_id']) && isset($this->params['confirm_delete'])) {
      $answerList = $this->generalSurveyAnswerList;
      $answerList->load(array('question_id' => $this->params['question_id']));
      if (count(iterator_to_array($answerList)) > 0) {
        foreach ($answerList as $answerId => $answerData) {
          $answerList->delete($answerId);
        }
      }
      $list = $this->generalSurveyQuestionList;
      if ($list->delete($this->params['question_id'])) {
        unset($this->params['question_id']);
        $this->addMsg(MSG_INFO, 'Question successfully deleted.');
      } else {
        $this->addMsg(MSG_ERROR, 'Error: could not delete question.');
      }
    }
  }

  /**
  * Delete an answer
  */
  public function deleteAnswer() {
    if (isset($this->params['answer_id']) && isset($this->params['confirm_delete'])) {
      $list = $this->generalSurveyAnswerList;
      if ($list->delete($this->params['answer_id'])) {
        unset($this->params['answer_id']);
        $this->addMsg(MSG_INFO, 'Answer successfully deleted.');
      } else {
        $this->addMsg(MSG_ERROR, 'Error: could not delete answer.');
      }
    }
  }

  /**
  * Delete a subject
  */
  public function deleteSubject() {
    if (isset($this->params['subject_id']) && isset($this->params['confirm_delete'])) {
      $list = $this->generalSurveySubjectList;
      $countChildren = 0;
      if ($list->load(array('parent_id' => $this->params['subject_id']))) {
        $countChildren = count(iterator_to_array($list));
      }
      if ($countChildren > 0) {
        $this->addMsg(MSG_ERROR, 'Error: cannot delete subject; has child subjects.');
      } else {
        if ($list->delete($this->params['subject_id'])) {
          unset($this->params['subject_id']);
          $this->addMsg(MSG_INFO, 'Subject successfully deleted.');
        } else {
          $this->addMsg(MSG_ERROR, 'Error: could not delete subject.');
        }
      }
    }
  }

  /**
  * Move a question group up by one position
  */
  public function moveUpQuestionGroup() {
    if (isset($this->params['questiongroup_id'])) {
      $questionGroupList = $this->generalSurveyQuestionGroupList;
      $questionGroupList->moveUp($this->params['questiongroup_id']);
    }
  }

  /**
  * Move a question group down by one position
  */
  public function moveDownQuestionGroup() {
    if (isset($this->params['questiongroup_id'])) {
      $questionGroupList = $this->generalSurveyQuestionGroupList;
      $questionGroupList->moveDown($this->params['questiongroup_id']);
    }
  }

  /**
  * Move a question up by one position
  */
  public function moveUpQuestion() {
    if (isset($this->params['question_id'])) {
      $questionList = $this->generalSurveyQuestionList;
      $questionList->moveUp($this->params['question_id']);
    }
  }

  /**
  * Move a question down by one position
  */
  public function moveDownQuestion() {
    if (isset($this->params['question_id'])) {
      $questionList = $this->generalSurveyQuestionList;
      $questionList->moveDown($this->params['question_id']);
    }
  }

  /**
  * Move an answer up by one position
  */
  public function moveUpAnswer() {
    if (isset($this->params['answer_id'])) {
      $answerList = $this->generalSurveyAnswerList;
      $answerList->moveUp($this->params['answer_id']);
    }
  }

  /**
  * Move an answer down by one position
  */
  public function moveDownAnswer() {
    if (isset($this->params['answer_id'])) {
      $answerList = $this->generalSurveyAnswerList;
      $answerList->moveDown($this->params['answer_id']);
    }
  }

  /**
  * Get a survey statistic
  *
  * @return string XML
  */
  public function getStatisticXml() {
    $result = '';
    $statistic = $this->generalSurveyStatistic;
    $orderBy = 'subject_name';
    if (isset($this->params['order_by']) && $this->params['order_by'] == 'result_count') {
      $orderBy = 'result_count';
    }
    $data = $statistic->getData($this->params['survey_id'], $orderBy);
    $structure = $statistic->getStructure($this->params['survey_id']);
    if (!empty($data) && !empty($structure)) {
      $result = sprintf('<listview title="%s">'.LF, $this->_gt('Statistic'));
      $result .= '<cols>'.LF;
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Subject'));
      $result .= sprintf('<col>%s</col>'.LF, $this->_gt('Count'));
      $questionGroupConter = 0;
      foreach ($structure as $questionGroupId => $questiongroupData) {
        $questionGroupConter++;
        $questionCounter = 0;
        foreach ($questiongroupData['QUESTIONS'] as $questionId => $questionData) {
          $questionCounter++;
          $answerCounter = 0;
          foreach ($questionData['ANSWERS'] as $answerId => $answerData) {
            $answerCounter++;
            $result .= sprintf(
              '<col hint="%s | %s | %s">%d.%d.%d</col>'.LF,
              papaya_strings::escapeHTMLChars($questiongroupData['TITLE']),
              papaya_strings::escapeHTMLChars($questionData['TITLE']),
              papaya_strings::escapeHTMLChars($answerData['TITLE']),
              $questionGroupConter,
              $questionCounter,
              $answerCounter
            );
          }
        }
      }
      $result .= '</cols>'.LF;
      $result .= '<items>'.LF;
      foreach ($data as $subjectId => $subjectData) {
        $result .= sprintf(
          '<listitem title="%s">'.LF,
          papaya_strings::escapeHTMLChars($subjectData['subject_name'])
        );
        $result .= sprintf(
          '<subitem align="right">%d</subitem>'.LF,
          $subjectData['result_count']
        );
        $results = $subjectData['RESULTS'];
        foreach ($structure as $questionGroupId => $questiongroupData) {
          foreach ($questiongroupData['QUESTIONS'] as $questionId => $questionData) {
            foreach ($questionData['ANSWERS'] as $answerId => $answerCount) {
              if (isset($results[$questionGroupId]) &&
                  isset($results[$questionGroupId][$questionId]) &&
                  isset($results[$questionGroupId][$questionId][$answerId])) {
                $answerCount = $results[$questionGroupId][$questionId][$answerId];
                $percentage = 0;
                $percentageString = '-';
                if ((int)$subjectData['result_count'] > 0) {
                  $percentage = 100 / (int)$subjectData['result_count'] * (int)$answerCount;
                  $percentageString = sprintf('%.2f%%', $percentage);
                }
                $result .= sprintf(
                  '<subitem align="right"><b>%d</b><br />%s</subitem>'.LF,
                  $answerCount,
                  $percentageString
                );
              } else {
                $result .= '<subitem align="right">0</subitem>'.LF;
              }
            }
          }
        }
        $result .= '</listitem>'.LF;
      }
      $result .= '</items>'.LF;
      $result .= '</listview>'.LF;
    } else {
      $this->addMsg(MSG_INFO, 'This survey has not been answered yet.');
    }
    return $result;
  }

  /**
  * Prepare CSV data
  *
  * @return string XML
  */
  public function prepareStatisticCsv() {
    $result = '';
    $statistic = $this->generalSurveyStatistic;
    $data = $statistic->getData($this->params['survey_id']);
    $structure = $statistic->getStructure($this->params['survey_id']);
    if (!empty($data) && !empty($structure)) {
      $questionGroupsLine = ',,';
      $questionsLine = ',,';
      foreach ($structure as $questionGroupId => $questiongroupData) {
        $questionGroupsLine .= $questiongroupData['TITLE'];
        foreach ($questiongroupData['QUESTIONS'] as $questionId => $questionData) {
          $questionsLine .= $questionData['TITLE'];
          foreach ($questionData['ANSWERS'] as $answerId => $answerData) {
            $questionGroupsLine .= ',';
            $questionsLine .= ',';
          }
        }
      }
      $result .= sprintf("%s\n%s\nSubject,Count", $questionGroupsLine, $questionsLine);
      $questionGroupConter = 0;
      foreach ($structure as $questionGroupId => $questiongroupData) {
        $questionGroupConter++;
        $questionCounter = 0;
        foreach ($questiongroupData['QUESTIONS'] as $questionId => $questionData) {
          $questionCounter++;
          $answerCounter = 0;
          foreach ($questionData['ANSWERS'] as $answerId => $answerData) {
            $answerCounter++;
            $result .= sprintf(',%d', $answerCounter);
          }
        }
      }
      $result .= "\n";
      foreach ($data as $subjectId => $subjectData) {
        $line = sprintf('%s,%d', $subjectData['subject_name'], $subjectData['result_count']);
        $results = $subjectData['RESULTS'];
        foreach ($structure as $questionGroupId => $questiongroupData) {
          foreach ($questiongroupData['QUESTIONS'] as $questionId => $questionData) {
            foreach ($questionData['ANSWERS'] as $answerId => $answerCount) {
              $current = 0;
              if (isset($results[$questionGroupId]) &&
                  isset($results[$questionGroupId][$questionId]) &&
                  isset($results[$questionGroupId][$questionId][$answerId])) {
                $current = $results[$questionGroupId][$questionId][$answerId];
              }
              $line .= sprintf(',%d', $current);
            }
          }
        }
        $line .= "\n";
        $result .= $line;
      }
    }
    return $result;
  }

  /**
  * Export current statistic as CSV
  *
  */
  public function exportStatisticCsv() {
    if (isset($this->params['survey_id'])) {
      $data = $this->prepareStatisticCsv();
      if (!empty($data)) {
        $agentString = strtolower(@$_SERVER["HTTP_USER_AGENT"]);
        if (strpos($agentString, 'opera') !== FALSE) {
          $agent = 'OPERA';
        } elseif (strpos($agentString, 'msie') !== FALSE) {
          $agent = 'IE';
        } else {
          $agent = 'STD';
        }
        $mimeType = ($agent == 'IE' || $agent == 'OPERA')
          ? 'application/octetstream' : 'application/octet-stream';
        $fileName = sprintf(
          'survey_%d_%s.csv',
          $this->params['survey_id'], date('Y-m-d', time())
        );
        if ($agent == 'IE') {
          header('Content-Disposition: inline; filename="'.$fileName.'"');
        } else {
          header('Content-Disposition: attachment; filename="'.$fileName.'"');
        }
        header('Content-type: ' . $mimeType);
        echo ($data);
        exit;
      }
    }
  }

  /**
  * Get the menu bar
  */
  public function getMenuBarXml() {
    $toolbar = new base_btnbuilder();
    $cmd = isset($this->params['cmd']) ? $this->params['cmd'] : '';
    $modeLinkParams = array();
    if (isset($this->params['survey_id'])) {
      $modeLinkParams['survey_id'] = $this->params['survey_id'];
    }
    $modeLinkParams['mode'] = 'edit';
    $toolbar->addButton(
      'Edit',
      $this->getLink($modeLinkParams),
      $this->images['actions-edit'],
      'Edit surveys',
      $this->_mode == 'edit'
    );
    $modeLinkParams['mode'] = 'stat';
    $toolbar->addButton(
      'Statistic',
      $this->getLink($modeLinkParams),
      $this->images['items-statistic'],
      'View survey statistics',
      $this->_mode == 'stat'
    );
    if ($this->_mode == 'edit') {
      $toolbar->addSeparator();
      $toolbar->addButton(
        'Add Survey',
        $this->_getLinkWithParams(array('cmd' => 'add_survey')),
        $this->images['actions-folder-add'],
        'Add a new survey',
        $cmd == 'add_survey'
      );
      if (isset($this->params['survey_id'])) {
        $survey = $this->loadSurvey($this->params['survey_id']);
        $toolbar->addButton(
          'Delete survey',
          $this->_getLinkWithParams(
            array('cmd' => 'delete_survey', 'survey_id' => $this->params['survey_id'])
          ),
          $this->images['actions-folder-delete'],
          'Delete current survey',
          $cmd == 'delete_survey'
        );
        $toolbar->addSeparator();
        $toolbar->addButton(
          'Add group',
          $this->_getLinkWithParams(
            array(
              'cmd' => 'add_questiongroup',
              'survey_id' => $this->params['survey_id']
            )
          ),
          $this->images['actions-table-add'],
          'Add a new question group',
          $cmd == 'add_questiongroup'
        );
        if (isset($this->params['questiongroup_id'])) {
          $toolbar->addButton(
            'Delete group',
            $this->_getLinkWithParams(
              array(
                'cmd' => 'delete_questiongroup',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id']
              )
            ),
            $this->images['actions-table-delete'],
            'Delete current question group',
            $cmd == 'delete_questiongroup'
          );
          $toolbar->addSeparator();
          $toolbar->addButton(
            'Add question',
            $this->_getLinkWithParams(
              array(
                'cmd' => 'add_question',
                'survey_id' => $this->params['survey_id'],
                'questiongroup_id' => $this->params['questiongroup_id']
              )
            ),
            $this->images['actions-table-row-add'],
            'Add a question',
            $cmd == 'add_question'
          );
          if (isset($this->params['question_id'])) {
            $toolbar->addButton(
              'Delete question',
              $this->_getLinkWithParams(
                array(
                  'cmd' => 'delete_question',
                  'survey_id' => $this->params['survey_id'],
                  'questiongroup_id' => $this->params['questiongroup_id'],
                  'question_id' => $this->params['question_id']
                )
              ),
              $this->images['actions-table-row-delete'],
              'Delete current question',
              $cmd == 'delete_question'
            );
            if ($survey['use_answers']) {
              $toolbar->addSeparator();
              $toolbar->addButton(
                'Add answer',
                $this->_getLinkWithParams(
                  array(
                    'cmd' => 'add_answer',
                    'survey_id' => $this->params['survey_id'],
                    'questiongroup_id' => $this->params['questiongroup_id'],
                    'question_id' => $this->params['question_id']
                  )
                ),
                $this->images['actions-generic-add'],
                'Add a new answer',
                $cmd == 'add_answer'
              );
              if (isset($this->params['answer_id'])) {
                $toolbar->addButton(
                  'Delete answer',
                  $this->_getLinkWithParams(
                    array(
                      'cmd' => 'delete_answer',
                      'survey_id' => $this->params['survey_id'],
                      'questiongroup_id' => $this->params['questiongroup_id'],
                      'question_id' => $this->params['question_id'],
                      'answer_id' => $this->params['answer_id']
                    )
                  ),
                  $this->images['actions-generic-delete'],
                  'Delete current answer',
                  $cmd == 'delete_answer'
                );
              }
            }
          }
        }
        if ($survey['use_subjects']) {
          $toolbar->addSeparator();
          $toolbar->addButton(
            'Add subject',
            $this->_getLinkWithParams(
              array(
                'cmd' => 'add_subject',
                'survey_id' => $this->params['survey_id']
              )
            ),
            $this->images['actions-table-column-add'],
            'Add a new subject',
            $cmd == 'add_subject'
          );
          if (isset($this->params['subject_id'])) {
            $toolbar->addButton(
              'Delete subject',
              $this->_getLinkWithParams(
                array(
                  'cmd' => 'delete_subject',
                  'survey_id' => $this->params['survey_id'],
                  'subject_id' => $this->params['subject_id']
                )
              ),
              $this->images['actions-table-column-delete'],
              'Delete current subject',
              $cmd == 'delete_subject'
            );
          }
        }
      }
    } else {
      if (isset($this->params['survey_id'])) {
        $orderBy = 'subject_name';
        if (isset($this->params['order_by']) && $this->params['order_by'] == 'result_count') {
          $orderBy = 'result_count';
        }
        $toolbar->addSeparator();
        $toolbar->addButton(
          'By subject',
          $this->getLink(
            array(
              'mode' => 'stat',
              'order_by' => 'subject_name',
              'survey_id' => $this->params['survey_id']
            )
          ),
          $this->images['actions-go-up'],
          'Order alphabetically by subject, ascending',
          $orderBy == 'subject_name'
        );
        $toolbar->addButton(
          'By result count',
          $this->getLink(
            array(
              'mode' => 'stat',
              'order_by' => 'result_count',
              'survey_id' => $this->params['survey_id']
            )
          ),
          $this->images['actions-go-down'],
          'Order by number of results, descending',
          $orderBy == 'result_count'
        );
        $toolbar->addSeparator();
        $toolbar->addButton(
          'Export CSV',
          $this->getLink(
            array(
              'mode' => 'stat',
              'order_by' => $orderBy,
              'cmd' => 'export_stat',
              'survey_id' => $this->params['survey_id']
            )
          ),
          $this->images['actions-download'],
          'Export statistic for current survey as CSV',
          isset($this->params['cmd']) && $this->params['cmd'] == 'export_stat'
        );
      }
    }
    if ($xml = $toolbar->getXML()) {
      $this->layout->addMenu(sprintf('<menu>%s</menu>'.LF, $xml));
    }
  }

  /**
  * Get paging links for the subject list
  *
  * @param integer $subjectCount
  * @return string XML
  */
  public function getSubjectPagingXml($subjectCount) {
    $params = array();
    $fields = array(
      'cmd',
      'survey_id',
      'subject_id',
      'subject_limit',
      'questiongroup_id',
      'question_id',
      'answer_id'
    );
    foreach ($fields as $field) {
      if (isset($this->params[$field])) {
        $params[$field] = $this->params[$field];
      }
    }
    return papaya_paging_buttons::getPagingButtons(
      $this,
      $params,
      isset($this->params['subject_offset']) ? $this->params['subject_offset'] : 0,
      $this->_subjectLimit,
      $subjectCount,
      3,
      'subject_offset',
      'left'
    );
  }

  /**
  * Get limit links for the subject list
  *
  * @return string XML
  */
  public function getSubjectLimitButtonsXml() {
    $params = array();
    $fields = array(
      'cmd',
      'survey_id',
      'subject_id',
      'questiongroup_id',
      'question_id',
      'answer_id'
    );
    foreach ($fields as $field) {
      if (isset($this->params[$field])) {
        $params[$field] = $this->params[$field];
      }
    }
    $pageValues = array();
    foreach ($this->_availableLimits as $limit) {
      $pageValues[$limit] = $limit;
    }
    return papaya_paging_buttons::getButtons(
      $this,
      $params,
      $pageValues,
      $this->_subjectLimit,
      'subject_limit',
      'right'
    );
  }

  // Helper methods

  /**
  * Get a link with additional parameters
  *
  * @param array $parameters
  * @return string
  */
  protected function _getLinkWithParams($parameters) {
    return $this->getLink($this->_getLinkParams($parameters));
  }

  /**
  * Get additional parameters for a link
  *
  * @param array $parameters
  * @return array
  */
  protected function _getLinkParams($parameters) {
    $parameters['mode'] = $this->_mode;
    if (isset($this->params['subject_offset']) && !isset($parameters['subject_offset'])) {
      $parameters['subject_offset'] = $this->params['subject_offset'];
    }
    if (isset($this->params['subject_limit']) && !isset($parameters['subject_limit'])) {
      $parameters['subject_limit'] = $this->params['subject_limit'];
    }
    return $parameters;
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
    if (!is_object($this->_dialogObject)) {
      $this->_dialogObject = new base_dialog($this, $this->paramName, $fields, $data, $hidden);
    }
    return $this->_dialogObject;
  }

  /**
  * Get a base_msgdialog object
  *
  * @param array $hidden
  * @param string $message
  * @return base_msgdialog
  */
  protected function _getMsgDialogObject($hidden, $message) {
    if (!is_object($this->_msgDialogObject)) {
      $this->_msgDialogObject = new base_msgdialog(
        $this,
        $this->paramName,
        $hidden,
        $message,
        'question'
      );
    }
    return $this->_msgDialogObject;
  }

  /**
  * Load survey for current ID - with or without current translation
  *
  * @param integer $id
  * @return array
  */
  public function loadSurvey($id) {
    $survey = $this->generalSurvey;
    if ($survey->load($this->params['survey_id'])) {
      $data['use_subjects'] = $survey['use_subjects'];
      $data['use_answers'] = $survey['use_answers'];
      $data['title'] = $survey['title'];
      $data['description'] = $survey['description'];
    } else {
      $data = $survey->getAlternateTranslation($this->params['survey_id']);
    }
    return $data;
  }

  //Helpers

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
      if (!isset($this->_instances[$name])) {
        $className = preg_replace('(^g)', 'G', $name);
        $this->_instances[$name] = new $className;
      }
      $this->_instances[$name]->language($this->papaya()->administrationLanguage->id);
      return $this->_instances[$name];
    } else {
      throw new InvalidArgumentException('Class name "'.$name.'" not allowed.');
    }
  }
}
