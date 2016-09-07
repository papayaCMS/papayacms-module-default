<?php
/**
* A connector with helper methods to handle data by pages.
*
* @copyright 2010 by papaya Software GmbH - All rights reserved.
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
* @version $Id: PagesConnector.php 39866 2014-06-30 09:24:58Z kersken $
*/

/**
* A connector with helper methods to handle data by pages.
*
* Usage:
* $pagesConnector = base_pluginloader::getPluginInstance('69db080d0bb7ce20b52b04e7192a60bf', $this);
*
* $array = $pagesConnector->getTitles($pageId(s), $languageId, $public = TRUE);
* $array = $pagesConnector->getContents($pageId(s), $languageId, $public = TRUE);
* $array = $pagesConnector->getTeasers($pageId(s), $languageId, $public = TRUE);
* $array = $pagesConnector->getModuleGuids($pageId(s), $languageId, $public = TRUE);
*/
class PapayaBasePagesConnector extends base_connector {

  /**
  * Database access object.
  * @var object
  */
  private $_databaseAccess = NULL;

  /**
  * Memory cache for page titles.
  * @var array
  */
  private $_pageTitles = array();

  /**
  * Memory cache for page contents.
  * @var array
  */
  private $_pageContents = array();
  
  /**
  * Memory cache for module GUIDs.
  * @var array
  */
  private $_moduleGuids = array();

  /**
  * The module's own GUID for reading module options
  * @var string
  */
  private $_guid = '69db080d0bb7ce20b52b04e7192a60bf';

  /**
  * Settings for this module
  * @var array
  */
  public $pluginOptionFields = array(
    'NOTIFICATION_URLS' => array(
      'Notification URLs',
      'isNoHTML',
      FALSE,
      'textarea',
      5,
      'One URL per line; use {%SITEMAP%} for the local sitemap URL to use',
      'http://www.google.com/webmasters/tools/ping?sitemap={%SITEMAP%}
http://www.bing.com/webmaster/ping.aspx?sitemap={%SITEMAP%}
http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap={%SITEMAP%}'
    ),
    'SITEMAP' => array(
      'Sitemap',
      'isNum',
      FALSE,
      'pageid',
      10,
      '',
      0
    ),
    'OUTPUT_FILTER' => array(
      'Sitemap output filter',
      'isNoHTML',
      FALSE,
      'input',
      20,
      '',
      'google'
    )
  );

  /**
  * Set database access object to load pages data.
  *
  * @param PapayaDatabaseAccess $databaseAccess
  * @return boolean
  */
  public function setDatabaseAccessObject($databaseAccess) {
    if (!empty($databaseAccess) && is_object($databaseAccess)) {
      $this->_databaseAccess = $databaseAccess;
    }
  }

  /**
  * Get database access object to load pages data.
  *
  * @return PapayaDatabaseAccess
  */
  public function getDatabaseAccessObject() {
    if (!isset($this->_databaseAccess)) {
      $this->_databaseAccess = new PapayaDatabaseAccess($this);
      $this->_databaseAccess->papaya($this->papaya());
    }
    return $this->_databaseAccess;
  }

  /**
  * Get page(s) titles by id(s) and language id.
  *
  * @param array|integer $pageIds
  * @param integer $languageId
  * @param boolean $public optional, default TRUE
  * @return array
  */
  public function getTitles($pageIds, $languageId, $public = TRUE) {
    $result = array();
    $cacheKey = $public ? 'PUBLIC' : 'MODIFIED';
    if (!empty($pageIds)) {
      if (!is_array($pageIds)) {
        $pageIds = array($pageIds);
      }
      if (!isset($this->_pageTitles[$cacheKey][$languageId])) {
        $this->_pageTitles[$cacheKey][$languageId] = array();
      }
      $pageIdsToLoad = array();
      foreach ($pageIds as $pageId) {
        if ($pageId > 0) {
          if (isset($this->_pageTitles[$cacheKey][$languageId][$pageId])) {
            $result[(int)$pageId] = $this->_pageTitles[$cacheKey][$languageId][$pageId];
          } else {
            $pageIdsToLoad[] = $pageId;
          }
        }
      }
      if (!empty($pageIdsToLoad)) {
        $databaseAccess = $this->getDatabaseAccessObject();
        $filter = $databaseAccess->getSqlCondition('tt.topic_id', $pageIdsToLoad);
        $sql = "SELECT tt.topic_id, tt.topic_title
                  FROM %s tt
                 WHERE $filter
                   AND tt.lng_id = %d";
        $tableBaseName = $public ?
          PapayaContentTables::PAGE_PUBLICATION_TRANSLATIONS :
          PapayaContentTables::PAGE_TRANSLATIONS;
        $table = $databaseAccess->getTableName($tableBaseName);
        $params = array($table, $languageId);
        if ($res = $databaseAccess->queryFmt($sql, $params)) {
          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $result[(int)$row['topic_id']] = $row['topic_title'];
            $this->_pageTitles[$cacheKey][$languageId][(int)$row['topic_id']] =
              $row['topic_title'];
          }
        }
      }
      ksort($result);
    }
    return $result;
  }

  /**
  * Get page(s) titles and contents by id(s) and language id.
  *
  * @param mixed array|integer $pageIds
  * @param integer $languageId
  * @param boolean $public optional, default TRUE
  * @return array
  */
  public function getContents($pageIds, $languageId, $public = TRUE) {
    $result = array();
    $cacheKey = $public ? 'PUBLIC' : 'MODIFIED';
    if (!empty($pageIds)) {
      if (!is_array($pageIds)) {
        $pageIds = array($pageIds);
      }
      if (!isset($this->_pageContents[$cacheKey][$languageId])) {
        $this->_pageContents[$cacheKey][$languageId] = array();
      }
      if (!isset($this->_pageTitles[$cacheKey][$languageId])) {
        $this->_pageTitles[$cacheKey][$languageId] = array();
      }
      $pageIdsToLoad = array();
      foreach ($pageIds as $pageId) {
        if ($pageId > 0) {
          if (isset($this->_pageContents[$cacheKey][$languageId][$pageId])) {
            $result[(int)$pageId] = $this->_pageContents[$cacheKey][$languageId][$pageId];
          } else {
            $pageIdsToLoad[] = $pageId;
          }
        }
      }
      if (!empty($pageIdsToLoad)) {
        $databaseAccess = $this->getDatabaseAccessObject();
        $filter = $databaseAccess->getSqlCondition('tt.topic_id', $pageIdsToLoad);
        $sql = "SELECT tt.topic_id, tt.topic_title, tt.topic_content
                  FROM %s tt
                 WHERE $filter
                   AND tt.lng_id = %d";
        $tableBaseName = $public ?
          PapayaContentTables::PAGE_PUBLICATION_TRANSLATIONS :
          PapayaContentTables::PAGE_TRANSLATIONS;
        $table = $databaseAccess->getTableName($tableBaseName);
        $params = array($table, $languageId);
        if ($res = $databaseAccess->queryFmt($sql, $params)) {
          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $result[(int)$row['topic_id']] = $row;
            $this->_pageContents[$cacheKey][$languageId][(int)$row['topic_id']] = $row;
            $this->_pageTitles[$cacheKey][$languageId][(int)$row['topic_id']] =
              $row['topic_title'];
          }
        }
      }
    }
    return $result;
  }

  /**
   * Get the teasers for one or more pages
   *
   * @param papaya_page $page
   * @param array|integer $pageIds
   * @param integer $languageId
   * @param boolean $public optional, default value TRUE
   * @param boolean $asStrings optional, default value FALSE
   * @return array
   */
  public function getTeasers($page, $pageIds, $languageId, $public = TRUE, $asStrings = FALSE) {
    $result = array();
    if (!empty($pageIds)) {
      $modules = $this->getModuleGuids($pageIds, $languageId, $public);
      $contents = $this->getContents($pageIds, $languageId, $public);
      foreach ($modules as $pageId => $moduleGuid) {
        $module = $this->papaya()->plugins->get($moduleGuid, $page);
        $content = $contents[$pageId];
        if ($module instanceof base_content) {
          $module->setData($content['topic_content']);
          $teaser = $module->getParsedTeaser();
          if ($asStrings) {
            $result[$pageId] = $teaser;
          } else {
            $doc = new PapayaXmlDocument();
            $root = $doc->createElement(
              'teaser',
              '',
              array('topic_id' => $pageId, 'href' => $this->getWebLink($pageId))
            );
            $root->appendXml($teaser);
            $result[$pageId] = $root;
          }
        } elseif ($module instanceof PapayaPluginAppendable) {
          $xml = new PapayaXmlDocument();
          $xml->loadXml($content['topic_content']);
          $contentArray = [];
          $xpath = $xml->xpath();
          foreach ($xpath->evaluate('//data-element') as $dataElement) {
            $name = $dataElement->getAttribute('name');
            if (!empty($name)) {
              $contentArray[$name] = $dataElement->textContent;
            }
          }
          $module->content(new PapayaPluginEditableContent($contentArray));
          $xml = new PapayaXmlDocument();
          $root = $xml->appendElement(
            'teaser',
            array('topic_id' => $pageId, 'href' => $this->getWebLink($pageId))
          );
          $module->appendQuoteTo($root);
          if ($asStrings) {
            $result[$pageId] = $xml->saveXML($root);
          } else {
            $result[$pageId] = $root;
          }
        }
      }
    }
    return $result;
  }

  /**
  * Get module id(s) by page id(s) and language id.
  *
  * @param mixed array|integer $pageIds
  * @param integer $languageId
  * @param boolean $public optional, default TRUE
  * @return array
  */
  public function getModuleGuids($pageIds, $languageId, $public = TRUE) {
    $result = array();
    $cacheKey = $public ? 'PUBLIC' : 'MODIFIED';
    if (!empty($pageIds)) {
      if (!is_array($pageIds)) {
        $pageIds = array($pageIds);
      }
      if (!isset($this->_moduleGuids[$cacheKey][$languageId])) {
        $this->_moduleGuids[$cacheKey][$languageId] = array();
      }
      $pageIdsToLoad = array();
      foreach ($pageIds as $pageId) {
        if ($pageId > 0) {
          if (isset($this->_moduleGuids[$cacheKey][$languageId][$pageId])) {
            $result[(int)$pageId] = $this->_moduleGuids[$cacheKey][$languageId][$pageId];
          } else {
            $pageIdsToLoad[] = $pageId;
          }
        }
      }
      if (!empty($pageIdsToLoad)) {
        $databaseAccess = $this->getDatabaseAccessObject();
        $filter = $databaseAccess->getSqlCondition('topic_id', $pageIdsToLoad);
        $sql = "SELECT tt.topic_id, tt.view_id, tt.lng_id, v.module_guid
                  FROM %s tt
                 INNER JOIN %s v
                    ON tt.view_id = v.view_id
                 WHERE $filter
                   AND lng_id = %d";
        $tableBaseName = $public ?
          PapayaContentTables::PAGE_PUBLICATION_TRANSLATIONS :
          PapayaContentTables::PAGE_TRANSLATIONS;
        $table = $databaseAccess->getTableName($tableBaseName);
        $parameters = array(
          $table,
          $databaseAccess->getTableName(PapayaContentTables::VIEWS),
          $languageId
        );
        if ($res = $databaseAccess->queryFmt($sql, $parameters)) {
          while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $result[(int)$row['topic_id']] = $row['module_guid'];
            $this->_moduleGuids[$cacheKey][$languageId][(int)$row['topic_id']] =
              $row['module_guid'];
          }
        }
      }
    }
    return $result;
  }

  /**
  * Notify search engines, sending them a Google Sitemaps document
  *
  * To be invoked via the Action Dispatcher using the default/onPublishPage action
  *
  * @param type $data
  */
  function onPublishPage($data) {
    if (!empty($data['languages']) && $data['published_from'] <= time() &&
        $data['published_to'] >= time()) {
      $options = $this->papaya()->plugins->options[$this->_guid];
      $urlString = $options->get('NOTIFICATION_URLS', '');
      $sitemap = $options->get('SITEMAP', 0);
      $filter = $options->get('OUTPUT_FILTER', '');
      if ($sitemap > 0 && $filter != '' && $urlString != '') {
        $template = new base_simpletemplate();
        $client = new PapayaHttpClient();
        $urls = preg_split('(\s+)Dix', $urlString);
        $attempts = 0;
        $successes = 0;
        foreach ($data['languages'] as $language) {
          $languageIdentifier = $this
            ->papaya()
            ->languages
            ->getLanguage($language)
            ->identifier;
          $reference = $this
            ->papaya()
            ->pageReferences
            ->get($languageIdentifier, $sitemap);
          $reference->setPreview(FALSE);
          $link = $reference->get();
          $replace = array(
            'SITEMAP' => urlencode($link)
          );
          foreach ($urls as $url) {
            if (!empty($url)) {
              $attempts++;
              $url = $template->parse($url, $replace);
              $client->setUrl($url);
              $client->setMethod('get');
              $client->send();
              if ($client->getResponseStatus() == 200) {
                $successes++;
              }
            }
          }
        }
        if ($attempts > 0) {
          $severity = $successes < $attempts ?
            PapayaMessage::SEVERITY_WARNING :
            PapayaMessage::SEVERITY_INFO;
          $this->papaya()->messages->dispatch(
            new PapayaMessageLog(
              PapayaMessageLogable::GROUP_CONTENT,
              $severity,
              sprintf(
                "Sent %d sitemap pings out of a total of %d.",
                $successes,
                $attempts
              )
            )
          );
        }
      }
    }
  }
}
