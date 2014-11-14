<?php
/**
* Action box for HTML, depending on a server variable
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
* @version $Id: actbox_html_servervar.php 39931 2014-11-14 10:27:24Z kersken $
*/

/**
* Action box for HTML, depending on a server variable
*
* @package Papaya-Modules
* @subpackage _Base
*/
class actionbox_html_servervar extends base_actionbox {

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
    'server_var' => array(
      'Server variable',
      'isNoHTML',
      TRUE,
      'combo',
      array(
        'HTTP_HOST' => 'HTTP_HOST',
        'HTTP_USER_AGENT' => 'HTTP_USER_AGENT',
        'HTTP_ACCEPT' => 'HTTP_ACCEPT',
        'HTTP_ACCEPT_LANGUAGE' => 'HTTP_ACCEPT',
        'HTTP_ACCEPT_ENCODING' => 'HTTP_ACCEPT_ENCODING',
        'HTTP_REFERER' => 'HTTP_REFERER',
        'HTTP_COOKIE' => 'HTTP_COOKIE',
        'HTTP_CONNECTION' => 'HTTP_CONNECTION',
        'PATH' => 'PATH',
        'REDIRECT_STATUS' => 'REDIRECT_STATUS',
        'SERVER_SIGNATURE' => 'SERVER_SIGNATURE',
        'SERVER_SOFTWARE' => 'SERVER_SOFTWARE',
        'SERVER_NAME' => 'SERVER_NAME',
        'SERVER_ADDR' => 'SERVER_ADDR',
        'SERVER_PORT' => 'SERVER_PORT',
        'REMOTE_ADDR' => 'REMOTE_ADDR',
        'DOCUMENT_ROOT' => 'DOCUMENT_ROOT',
        'REQUEST_SCHEME' => 'REQUEST_SCHEME',
        'CONTEXT_PREFIX' => 'CONTEXT_PREFIX',
        'CONTEXT_DOCUMENT_ROOT' => 'CONTEXT_DOCUMENT_ROOT',
        'SERVER_ADMIN' => 'SERVER_ADMIN',
        'SCRIPT_FILENAME' => 'SCRIPT_FILENAME',
        'REMOTE_PORT' => 'REMOTE_PORT',
        'REDIRECT_QUERY_STRING' => 'REDIRECT_QUERY_STRING',
        'REDIRECT_URL' => 'REDIRECT_URL',
        'GATEWAY_INTERFACE' => 'GATEWAY_INTERFACE',
        'SERVER_PROTOCOL' => 'SERVER_PROTOCOL',
        'REQUEST_METHOD' => 'REQUEST_METHOD',
        'QUERY_STRING' => 'QUERY_STRING',
        'REQUEST_URI' => 'REQUEST_URI',
        'SCRIPT_NAME' => 'SCRIPT_NAME',
        'PHP_SELF' => 'PHP_SELF'
      ),
      '',
      'HTTP_HOST'
    ),
    'match_type' => array(
      'Match type',
      'isNoHTML',
      TRUE,
      'combo',
      array(
        'is' => 'is',
        'start' => 'starts with',
        'end' => 'ends with',
        'contain' => 'contains'
      ),
      '',
      'is'
    ),
    'match_value' => array('Match value', 'isNoHTML', TRUE, 'input', 200, ''),
    'text_match' => array('Text on match', 'isSomeText', FALSE, 'textarea', 20),
    'text_nomatch' => array('Text on no match', 'isSomeText', FALSE, 'textarea', 20)
  );

  /**
  * Get parsed data
  *
  * @access public
  * @return string
  */
  function getParsedData() {
    $this->setDefaultData();
    $result = '';
    $match = FALSE;
    if (isset($_SERVER[$this->data['server_var']])) {
      $value = $_SERVER[$this->data['server_var']];
      switch ($this->data['match_type']) {
      case 'is':
        $match = ($value == $this->data['match_value']);
        break;
      case 'start':
        $match = (strpos($value, $this->data['match_value']) === 0);
        break;
      case 'end':
        $match = (
          substr(
            $value,
            -strlen($this->data['match_value'])
          ) === $this->data['match_value']);
        break;
      case 'contain':
        $match = (strpos($value, $this->data['match_value']) !== FALSE);
        break;
      }
      if ($match && isset($this->data['text_match'])) {
        $result = $this->getXHTMLString($this->data['text_match']);
      }
    }
    if (!$match && isset($this->data['text_nomatch'])) {
      $result = $this->getXHTMLString($this->data['text_nomatch']);
    }
    return $result;
  }
}
