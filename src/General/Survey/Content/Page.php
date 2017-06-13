<?php
/**
* General Survey Content Page
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: Page.php 837 2012-12-14 15:26:21Z kersken $
*/

/**
* General Survey Content Page class
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class GeneralSurveyContentPage extends base_content {
  /**
  * Parameter namespace
  * @var string
  */
  public $paramName = 'gsrv';

  /**
  * Page configuration edit fields
  * @var array
  */
  public $editFields = [
    'survey_id' => ['Survey', 'isNum', TRUE, 'function', 'callbackSurvey'],
    'use_login' => ['Use login', 'isNum', TRUE, 'radio', [0 => 'No', 1 => 'Yes'], '', 1],
    'max_subjects' => ['Max. subjects', 'isNum', TRUE, 'input', 10, '', 3],
    'text_done' => [
      'Text survey done',
      'isSomeText',
      TRUE,
      'richtext',
      7,
      '',
      'Thank you.'
    ],
    'Captions',
    'head_select_subject' => [
      'Select subject heading',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Select the subject'
    ],
    'caption_subject' => [
      'Subject',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Subject'
    ],
    'caption_current_subject' => [
      'Current subject',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Current subject'
    ],
    'caption_subject_select' => [
      'Select subject',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Please select'
    ],
    'caption_next' => [
      'Next',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Next'
    ],
    'caption_repeat' => [
      'Repeat survey',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Repeat with another subject'
    ],
    'Error messages',
    'error_no_user' => [
      'No user',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'You need to be logged in to take part in the survey.'
    ],
    'error_no_survey' => [
      'No survey',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'No survey selected.'
    ],
    'error_subjects' => [
      'Too many subjects',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'You have already answerd for too many subjects.'
    ],
    'error_input' => [
      'Input error',
      'isNoHTML',
      TRUE,
      'input',
      200,
      '',
      'Please answer the unanswered questions.'
    ]
  ];

  /**
  * Content object
  * @var GeneralSurveyContent
  */
  private $_content = NULL;

  /**
  * Get the module's XML output
  *
  * @return string XML
  */
  public function getParsedData() {
    $this->setDefaultData();
    $this->initializeParams();
    $surfer = $this->papaya()->surfer;
    $content = $this->content();
    $result = '';
    if ($surfer->isValid) {
      $content->userId($surfer->surferId);
      $result = $content->getContentXml();
    } elseif ($this->data['use_login'] == 0) {
      $content->userId($this->papaya()->session->id);
      $result = $content->getContentXml();
    } else {
      $result = $content->getMessageXml($this->data['error_no_user']);
    }
    return $result;
  }

  /**
  * Callback to select a survey
  *
  * @param string $name
  * @param array $field
  * @param integer $value
  * @return string
  */
  public function callbackSurvey($name, $field, $value) {
    return $this->content()->getSurveySelector($name, $value);
  }

  /**
  * Set/initialize/get the content object
  *
  * @param GeneralSurveyContent $content optional, default NULL
  * @return GeneralSurveyContent
  */
  public function content($content = NULL) {
    if ($content !== NULL) {
      $this->_content = $content;
    } elseif ($this->_content === NULL) {
      $this->_content = new GeneralSurveyContent();
      $this->_content->initializeContent();
      $this->_content->setOwner($this);
    }
    return $this->_content;
  }
}
