<?php
/**
* Action box for Category teaser
*
* Show teasers for one or more subpages of a category.
*
* @copyright 2002-2007 by papaya Software GmbH - All rights reserved.
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
* @version $Id: actbox_categteaser.php 39600 2014-03-18 11:43:38Z weinert $
*/

/**
* Action box for Category teaser
*
* Show teasers for one or more subpages of a category.
*
* @package Papaya-Modules
* @subpackage _Base
*/
class actionbox_categteaser extends base_actionbox {

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'page' => array('Page', 'isNum', TRUE, 'pageid', 5, '', 0),
    'count' => array('Count', 'isNum', TRUE, 'input', 5, '', 1),
    'sort' => array(
      'Sort',
      'isNum',
      TRUE,
      'translatedcombo',
      array(
        base_topiclist::SORT_WEIGHT_ASCENDING => 'Position Ascending',
        base_topiclist::SORT_WEIGHT_DESCENDING => 'Position Descending',
        base_topiclist::SORT_CREATED_ASCENDING => 'Created Ascending',
        base_topiclist::SORT_CREATED_DESCENDING => 'Created Descending',
        base_topiclist::SORT_PUBLISHED_ASCENDING => 'Modified/Published Ascending',
        base_topiclist::SORT_PUBLISHED_DESCENDING => 'Modified/Published Descending',
        base_topiclist::SORT_RANDOM => 'Random'
      ),
      '',
      0
    ),
    'perm' => array(
      'Surfer permission',
      'isNum',
      FALSE,
      'function',
      'callbackSurferPerm',
      'Permission to view this or "none" if generally allowed',
      -1
    )
  );

  /**
  * Get parsed data
  *
  * @access public
  * @return string $result
  */
  function getParsedData() {
    $this->setDefaultData();
    $result = '';
    $permission = TRUE;
    if (isset($this->data['perm']) && $this->data['perm'] > 0) {
      $surfer = $this->papaya()->surfer;
      if (!$surfer->hasPerm($this->data['perm'])) {
        $permission = FALSE;
      }
    }
    if ($permission) {
      $topicList = new base_topiclist;
      $topicList->databaseURI = $this->parentObj->databaseURI;
      $topicList->databaseURIWrite = $this->parentObj->databaseURIWrite;
      $topicList->tableTopics = $this->parentObj->tableTopics;
      $topicList->tableTopicsTrans = $this->parentObj->tableTopicsTrans;
      $topicClass = get_class($this->parentObj);
      $topicList->loadList(
        (int)$this->data['page'],
        (int)$this->parentObj->topic['TRANSLATION']['lng_id'],
        is_a($this->parentObj, 'papaya_publictopic'),
        (int)$this->data['sort'],
        (int)$this->data['count']
      );
      $result = $topicList->getList($topicClass, (int)$this->data['count']);
    }
    return $result;
  }

  /**
  * Get a selector for surfer permissions
  *
  * @param string $name
  * @param array $field
  * @param integer $data
  * @return string form XML
  */
  function callbackSurferPerm($name, $field, $data) {
    $surfersObj = $this->papaya()->plugins->get('06648c9c955e1a0e06a7bd381748c4e4', $this);
    $result = '';
    if (is_object($surfersObj)) {
      $result = $surfersObj->getPermCombo($name, $field, $data, $this->paramName);
    }
    return $result;
  }
}
