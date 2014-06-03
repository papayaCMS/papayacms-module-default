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
* @version $Id: PagesConnector.php 39846 2014-06-02 15:28:44Z kersken $
*/

/**
* A connector with helper methods to handle data by pages.
*
* Usage:
* $pagesConnector = base_pluginloader::getPluginInstance('69db080d0bb7ce20b52b04e7192a60bf', $this);
*
* $array = $pagesConnector->getTitles($pageId(s), $languageId, $public = TRUE);
* $array = $pagesConnector->getContents($pageId(s), $languageId, $public = TRUE);
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
}
