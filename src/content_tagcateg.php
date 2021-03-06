<?php
/**
* Page module - tags category
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
* @version $Id: content_tagcateg.php 39730 2014-04-07 21:05:30Z weinert $
*/

/**
* Page module - tags category
*
* Display teaser of pages with common tag
*
* @package Papaya-Modules
* @subpackage _Base
*/
class content_tagcateg extends base_content {

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'tag_id_output' => array('Tag', NULL, FALSE, 'disabled_function',
      'callbackSelectedTags', 'Go to "Tag Selection" to modify.'),
    'max' => array('Count', 'isNum', TRUE, 'input', 5, '', 10000),
    'columns' => array('Column count', 'isNum', TRUE, 'input', 1, '', 2),
    'sort' => array(
      'Sort',
      'isNum',
      TRUE,
      'translatedcombo',
      array(
        base_topiclist::SORT_CREATED_ASCENDING => 'Created Ascending',
        base_topiclist::SORT_CREATED_DESCENDING => 'Created Descending',
        base_topiclist::SORT_PUBLISHED_ASCENDING => 'Modified/Published Ascending',
        base_topiclist::SORT_PUBLISHED_DESCENDING => 'Modified/Published Descending',
        base_topiclist::SORT_RANDOM => 'Random'
      ),
      '',
      0
    ),
    'Image',
    'image' => array('Image', 'isSomeText', FALSE, 'image', 400, '', ''),
    'imgalign' => array('Image align', 'isAlpha', FALSE, 'combo',
      array('left' => 'left', 'right' => 'right', 'top' => 'top', ), '', 'left'),
    'breakstyle' => array('Text float', 'isAlpha', TRUE, 'combo',
      array('none' => 'None', 'side' => 'Side', 'center' => 'Center'), '', 'none'),
    'Texts',
    'nl2br' => array('Automatic linebreak', 'isNum', FALSE, 'translatedcombo',
      array(0 => 'Yes', 1 => 'No'),
      'Papaya will apply your linebreaks to the output page.', 0),
    'title' => array('Title', 'isSomeText', FALSE, 'input', 400, '', ''),
    'subtitle' => array('Subtitle', 'isSomeText', FALSE, 'input', 400, '', ''),
    'teaser' => array('Teaser', 'isSomeText', FALSE, 'simplerichtext', 10, '', ''),
    'text' => array('Text', 'isSomeText', FALSE, 'richtext', 20, '', ''),
    'Thumbnails',
    'thumbwidth' => array('Width', 'isNum', TRUE, 'input', 5, '', 100),
    'thumbheight' => array('Height', 'isNum', TRUE, 'input', 5, '', 100),
    'resizemode' => array('Resize mode', 'isAlpha', TRUE, 'combo',
      array('max' => 'Maximal', 'min' => 'Minimum', 'mincrop' => 'Minimum crop',
        'abs' => 'Absolute'), '', 'max'),
  );

  var $tagSelectorForm = '';

  var $modified = FALSE;

  /**
   * @var papaya_tagselector
   */
  private $tagSelector;

  /**
  * initialize session params and tag object
  */
  function initialize() {
    parent::initialize();

    $this->sessionParamName = 'PAPAYA_SESS_'.$this->paramName;
    $this->initializeParams();

    $this->sessionParams = $this->getSessionValue($this->sessionParamName);
    $this->initializeSessionParam('contentmode');
    $this->setSessionValue($this->sessionParamName, $this->sessionParams);
  }

  /**
  * execute tag objects execute
  */
  function execute() {
    $this->tagSelector = papaya_tagselector::getInstance($this->parentObj);
    $this->setDefaultData();
    if (isset($this->tagSelector) && $this->tagSelector instanceof papaya_tagselector) {
      $currentTagIds = $this->getCurrentTagIds();
      $this->tagSelectorForm = $this->tagSelector->getTagSelector($currentTagIds);
      $selectedTags = $this->tagSelector->getSelectedTags();
      if ($selectedTags != $currentTagIds) {
        $this->data['tag_ids'] = $selectedTags;
        $this->modified = TRUE;
      }
    }
  }

  /**
  * callback function for tag selection
  */
  function callbackSelectedTags($name, $element, $data) {
    $result = '';
    $tagIds = $this->getCurrentTagIds();
    $tags = $this->tagSelector->getTags($tagIds, $this->parentObj->getContentLanguageId());
    $result .= sprintf(
      '<input type="text" name="%s[%s]"'.
      ' class="dialogInput dialogScale" value="%s" disabled="disabled"></input>'.LF,
      papaya_strings::escapeHTMLChars($this->paramName),
      papaya_strings::escapeHTMLChars($name),
      papaya_strings::escapeHTMLChars(
        implode(
          ',',
          array_map(
            function($tag) {
              return $tag['tag_title'];
            },
            $tags
          )
        )
      )
    );
    return $result;
  }

  /**
   * generate edit form
   * @param string $dialogTitlePrefix
   * @param string $dialogIcon
   * @return string
   */
  function getForm($dialogTitlePrefix = '', $dialogIcon = '') {
    $result = '';
    $result .= $this->getContentToolbar();

    if (empty($this->params['contentmode'])) {
      $this->params['contentmode'] = 0;
    }
    switch ($this->params['contentmode']) {
    case 1:
      $result .= $this->tagSelectorForm;
      break;
    default:
      $result .= parent::getForm();
      break;
    }
    return $result;
  }

  /**
  * generate content toolbar
  */
  function getContentToolbar() {
    $toolbar = new base_btnbuilder;
    $toolbar->images = $GLOBALS['PAPAYA_IMAGES'];

    $toolbar->addButton(
      'General',
      $this->getLink(array('contentmode' => 0)),
      $toolbar->images['categories-content'],
      '',
      $this->params['contentmode'] == 0
    );
    $toolbar->addButton(
      'Tag Selection',
      $this->getLink(array('contentmode' => 1)),
      $toolbar->images['actions-tag-add'],
      '',
      $this->params['contentmode'] == 1
    );
    $toolbar->addSeperator();

    if ($str = $toolbar->getXML()) {
      return '<toolbar>'.$str.'</toolbar>';
    }
    return '';
  }

  /**
  * check input data
  */
  function checkData() {
    if (empty($this->params['contentmode'])) {
      $this->params['contentmode'] = 0;
    }
    switch($this->params['contentmode']) {
    case 1  :
      return TRUE;
    default :
      return parent::checkData();
    }
  }

  /**
   * get modified state
   * @param string $marker
   * @return bool
   */
  function modified($marker = 'save') {
    if (empty($this->params['contentmode'])) {
      $this->params['contentmode'] = 0;
    }
    switch($this->params['contentmode']) {
    case 1:
      return $this->modified;
    default:
      return parent::modified();
    }
  }

  /**
   * Get parsed data
   *
   * @access public
   * @param array|null $parseParams
   * @return string
   */
  function getParsedData($parseParams = NULL) {
    $this->setDefaultData();
    $result = sprintf(
      '<title encoded="%s">%s</title>'.LF,
      rawurlencode($this->data['title']),
      papaya_strings::escapeHTMLChars($this->data['title'])
    );
    $result .= sprintf(
      '<subtitle>%s</subtitle>'.LF,
      papaya_strings::escapeHTMLChars($this->data['subtitle'])
    );
    if (!empty($this->data['image'])) {
      $result .= sprintf(
        '<image align="%s" break="%s">%s</image>'.LF,
        papaya_strings::escapeHTMLChars($this->data['imgalign']),
        papaya_strings::escapeHTMLChars($this->data['breakstyle']),
        $this->getPapayaImageTag($this->data['image'])
      );
    }
    $result .= sprintf(
      '<teaser>%s</teaser>',
      $this->getXHTMLString($this->data['teaser'], !((bool)$this->data['nl2br']))
    );
    $result .= sprintf(
      '<text>%s</text>',
      $this->getXHTMLString($this->data['text'], !((bool)$this->data['nl2br']))
    );
    $result .= sprintf(
      '<columns>%d</columns>'.LF,
      (int)$this->data['columns']
    );
    $topicList = new base_topiclist;
    $topicList->databaseURI = $this->parentObj->databaseURI;
    $topicList->databaseURIWrite = $this->parentObj->databaseURIWrite;
    $topicList->tableTopics = $this->parentObj->tableTopics;
    $topicList->tableTopicsTrans = $this->parentObj->tableTopicsTrans;
    $topicClass = get_class($this->parentObj);
    $tagIds = $this->getCurrentTagIds();
    $topicList->loadListByTag(
      $tagIds,
      (int)$this->parentObj->getContentLanguageId(),
      is_a($this->parentObj, 'papaya_publictopic'),
      (int)$this->data['sort']
    );
    if ($this->data['max'] < 1) {
      $this->data['max'] = 1000;
    }
    $subTopicString = papaya_strings::entityToXML(
      $topicList->getList($topicClass, (int)$this->data['max'])
    );
    $result .= $subTopicString;
    if (trim($subTopicString) != '') {
      $dom = new PapayaXmlDocument();
      $dom->loadXml($subTopicString);
      $thumbnails = new PapayaUiContentTeaserImages(
        $dom->documentElement,
        (int)$this->data['thumbwidth'],
        (int)$this->data['thumbheight'],
        $this->data['resizemode']
      );
      $result .= $thumbnails->getXml();
    }
    return $result;
  }

  /**
  * Get parsed teaser
  *
  * @access public
  * @return string
  */
  function getParsedTeaser($parseParams = NULL) {
    $this->setDefaultData();
    $result = sprintf(
      '<title>%s</title>'.LF,
      papaya_strings::escapeHTMLChars($this->data['title'])
    );
    $result .= sprintf(
      '<subtitle>%s</subtitle>'.LF,
      papaya_strings::escapeHTMLChars($this->data['subtitle'])
    );
    if (trim($this->data['teaser'] != '')) {
      $result .= sprintf(
        '<text>%s</text>',
        $this->getXHTMLString($this->data['teaser'], !((bool)$this->data['nl2br']))
      );
    } elseif (trim($this->data['text']) != '') {
      $teaser = str_replace("\r\n", "\n", $this->data['text']);
      if (preg_match("/^(.+)([\n]{2})/sU", $teaser, $regs)) {
        $teaser = $regs[1];
      }
      $result .= sprintf(
        '<text>%s</text>'.LF,
        $this->getXHTMLString($teaser, !((bool)$this->data['nl2br']))
      );
    }
    if (!empty($this->data['image'])) {
      $result .= sprintf(
        '<image align="%s" break="%s">%s</image>'.LF,
        papaya_strings::escapeHTMLChars($this->data['imgalign']),
        papaya_strings::escapeHTMLChars($this->data['breakstyle']),
        $this->getPapayaImageTag($this->data['image'])
      );
    }
    return $result;
  }

  private function getCurrentTagIds() {
    if (
      isset($this->data['tag_ids']) &&
      is_array($this->data['tag_ids']) &&
      count($this->data['tag_ids'])
    ) {
      return $this->data['tag_ids'];
    } elseif (!empty($this->data['tag_id'])) {
      return [$this->data['tag_id']];
    } else {
      return [0];
    }
  }
}


