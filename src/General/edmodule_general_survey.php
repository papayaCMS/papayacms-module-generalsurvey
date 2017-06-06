<?php
/**
* General Survey Administration Module
*
* @copyright by papaya Software GmbH, Cologne, Germany - All rights reserved.
* @license Papaya Closed License (PCL)
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
* @version $Id: edmodule_general_survey.php 820 2012-11-22 13:11:55Z kersken $
*/

/**
* General Survey Administration Module base class
*
* @package Papaya-Modules
* @subpackage GeneralSurvey
*/
class edmodule_general_survey extends base_module {

  /**
  * Parameter group name.
  * @var string
  */
  public $paramName = 'gsrv';

  /**
  * Module permissions.
  *
  * @var array
  */
  public $permissions = array(
    1 => 'Edit'
  );

  /**
  * Initialized administration object.
  *
  * @var GeneralSurveyAdministration
  */
  private $_adminObject = NULL;

  /**
  * Function for execute module.
  */
  public function execModule() {
    if ($this->hasPerm(1, TRUE)) {
      $adminObject = $this->getAdminObject();
      $adminObject->module = &$this;
      $adminObject->images = $this->images;
      $adminObject->msgs = &$this->msgs;
      $adminObject->layout = &$this->layout;
      $adminObject->authUser = &$this->authUser;
      $adminObject->paramName = $this->paramName;
      $adminObject->initialize();
      $adminObject->execute();
      $adminObject->getXml();
      $adminObject->getMenuBarXml();
    }
  }

  /**
  * Initialize the administration object for the booklets.
  *
  * @return GeneralSurveyAdministration
  */
  public function getAdminObject() {
    if (!(isset($this->_adminObject) && is_object($this->_adminObject))) {
      $this->_adminObject = new GeneralSurveyAdministration();
    }
    return $this->_adminObject;
  }
}
