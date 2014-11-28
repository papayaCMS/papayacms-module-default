<?php
/**
* Action box for richtext
*
* @copyright 2002-2009 by papaya Software GmbH - All rights reserved.
* @link http://www.papaya-cms.com/
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
*
* You can redistribute and/or modify this script under the terms of the GNU General Public
* License (GPL) version 2, provided that the copyright and license notes, including these
* lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
*
* @package Papaya-Modules
* @subpackage _Base
* @version $Id: actbox_richtextfilter.php 39262 2014-02-18 18:01:42Z weinert $
*/

/**
* Action box for richtext
*
* @package Papaya-Modules
* @subpackage _Base
*/
class actionbox_richtextfilter extends base_actionbox {

  /**
  * Preview allowed?
  * @var boolean $preview
  */
  var $preview = TRUE;

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'nl2br' => array('Automatic linebreak', 'isNum', FALSE, 'translatedcombo',
      array(0 => 'Yes', 1 => 'No'),
      'Apply linebreaks from input to the HTML output.', 0),
    'text' => array('Text', 'isSomeText', FALSE, 'richtext', 30, '', '')
  );

  /**
  * Get parsed data
  *
  * @access public
  * @return string
  */
  function getParsedData() {
    $this->setDefaultData();
    return sprintf(
      '<richtext>%s</richtext>',
      $this->getXHTMLString(
        $this->data['text'],
        !((bool)$this->data['nl2br'])
      )
    );
  }
}

