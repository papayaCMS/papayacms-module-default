<?php
/**
* page module - URL-forwarding
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
* @version $Id: content_softlink.php 39827 2014-05-20 09:55:09Z weinert $
*/

/**
* page module - URL-forwarding
*
* @package Papaya-Modules
* @subpackage _Base
*/
class content_softlink extends base_content {

  /**
  * Is cacheable?
  * @var boolean $cacheable
  */
  var $cacheable = FALSE;

  /**
  * Edit fields
  * @var array $editFields
  */
  var $editFields = array(
    'page' => array('Page Id', 'isNum', TRUE, 'pageid', 5)
  );

  /**
   * @var papaya_topic
   */
  private $linkedTopic;

  /**
   * @var base_outputfilter
   */
  private $filter;

  /**
   * Exchange topic in page object.
   *
   * @access public
   * @param array|null $parseParams
   * @return bool FALSE
   */
  function getParsedData($parseParams = NULL) {
    if (isset($this->data['page']) && $this->data['page'] > 0 &&
        $this->data['page'] != $this->parentObj->topicId &&
        isset($GLOBALS['PAPAYA_PAGE'])) {
      /** @var papaya_page $page */
      $page = $GLOBALS['PAPAYA_PAGE'];
      $topicId = (int)$this->data['page'];
      if (!isset($GLOBALS['PAPAYA_PAGE_CURRENT_IDS'][$topicId])) {
        $GLOBALS['PAPAYA_PAGE_CURRENT_IDS'][$topicId] = TRUE;
        $this->linkedTopic = $page->createPage();
        if ($this->linkedTopic->topicExists($topicId) &&
            $this->linkedTopic->loadOutput(
              $topicId,
              $page->requestData['language'],
              $page->versionDateTime
            )
           ) {
          if ($this->linkedTopic->checkPublishPeriod($topicId)) {
            if ($page->validateAccess($topicId)) {
              if ($page->mode == 'xml') {
                if ($this->papaya()->options->get('PAPAYA_DBG_XML_OUTPUT', FALSE)) {
                  $page->topicId = $topicId;
                  $page->topic = $this->linkedTopic;
                  $page->filter = $this->filter;
                  $page->layout->add(
                    $this->linkedTopic->parseContent(
                      TRUE,
                      isset($this->filter->data) ? $this->filter->data : NULL
                    ),
                    'content'
                  );
                } else {
                  $page->getError(
                    403,
                    'Access forbidden',
                    PAPAYA_PAGE_ERROR_ACCESS
                  );
                }
              } else {
                $viewId = $this->linkedTopic->getViewId();
                if ($viewId > 0) {
                  if ($this->filter =
                        $page->output->getFilter($viewId)) {
                    $page->topicId = $topicId;
                    $page->topic = $this->linkedTopic;
                    $page->filter = $this->filter;
                    $page->layout->add(
                      $this->linkedTopic->parseContent(
                        TRUE,
                        isset($this->filter->data) ? $this->filter->data : NULL
                      ),
                      'content'
                    );
                  } else {
                    $page->getError(
                      500,
                      'Output mode "'.
                        papaya_strings::escapeHTMLChars(
                          basename($page->mode)
                        ).'" for page #'.$topicId.' not found',
                      PAPAYA_PAGE_ERROR_OUTPUT
                    );
                  }
                } else {
                  $page->getError(
                    500,
                    'View "'.
                      papaya_strings::escapeHTMLChars(
                        basename($page->mode)
                      ).'" for page #'.$topicId.' not found',
                    PAPAYA_PAGE_ERROR_VIEW
                  );
                }
              }
            } else {
              $page->getError(
                403,
                'Access forbidden',
                PAPAYA_PAGE_ERROR_ACCESS
              );
            }
          } else {
            $page->getError(
              404,
              'Page not published',
              PAPAYA_PAGE_ERROR_PAGE_PUBLIC
            );
          }
        } else {
          $page->getError(
            404,
            'Page not found',
            PAPAYA_PAGE_ERROR_PAGE
          );
        }
      } else {
        $page->getError(
          500,
          'Page link recursion found.',
          PAPAYA_PAGE_ERROR_PAGE_RECURSION
        );
      }
    }
    return FALSE;
  }

  /**
  * Get parsed teaser
  *
  * @access public
  * @return string
  */
  function getParsedTeaser() {
    if (isset($this->data['page']) && $this->data['page'] > 0 &&
        $this->data['page'] != $this->parentObj->topicId &&
        isset($GLOBALS['PAPAYA_PAGE'])) {
      $topicId = (int)$this->data['page'];
      /** @var papaya_page $page */
      $page = $GLOBALS['PAPAYA_PAGE'];
      $this->linkedTopic = $page->createPage();
      if ($this->linkedTopic->topicExists($topicId) &&
          $this->linkedTopic->loadOutput(
            $topicId,
            $page->requestData['language'],
            $page->versionDateTime
          )
         ) {
        return $this->linkedTopic->parseContent(FALSE, NULL, FALSE);
      }
    }
    return '';
  }
}

