<?php
/**
 @file       linkbonus/syntax/external.php
 @brief      External syntax component for Linkbonus plugin.
 @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 @author     Luis Machuca Bezzaza <luis.machuca [at] gulix.cl>
 @version    0.04
**/

if(!defined('DW_LF')) define('DW_LF',"\n");

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN. 'linkbonus/common.php'); // for common functions

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_linkbonus_external extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo() {
        return DW_common_linkbonus::getInfo($this);
    }
 
    function getType() { 
        return DW_common_linkbonus::getType();
    }

    // priority can be increased via the 'priority' option
    function getSort() { 
        return ($this->getConf('priority') == 0) ? 290 : 240;
    }

//    function getAllowedTypes() { 
//        return array();
//    }

    function connectTo($mode) { 

        /**
        * we catch the following syntax:
        * [[http(s)://somewhere.com/some/path|Link name|Link title|(fetchname?)|]]
        *
        */
        $linkpart  = '[^\|\]]+'; // everything 'till the pipe
        $namepart  = '[^\|\](?:\{\{|\}\})]+'; // we must NOT catch image links
        $otherpart = '[^\]]+'; 
        $REGEX     = 
        '\[\[http:\/\/'.$linkpart. '\|'. $namepart . $otherpart. '\]\]';
        $this->Lexer->addSpecialPattern(
            $REGEX,$mode,'plugin_linkbonus_external'); 
    }

 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        $match = substr($match, 2, -2);
        /* split with non-escaped pipes ;)
           result should be something like:
           http://link  |  Name  | [Title] [ | parameters... ] */
        $data  = preg_split('/(?<!\\\\)\|/', $match);
        $link= array();
        $link['url']     = array_shift($data);
        $link['name']    = array_shift($data);
        $link['title']   = array_shift($data);

        /* If link-title is empty, use the same contents as link-name */
        if (empty($link['title']) ) $link['title']= $link['name'];

        /* Unescape pipes */
        $link['name']  = str_replace ('\|', '|', $link['name']);
        $link['title'] = str_replace ('\|', '|', $link['title']);

        /* Check for forced favicon usage */
        if ($this->getConf('force_link_favicons') == true && !in_array('favicon',$data)) $data[] = 'favicon';

        /* Check for forced doa usage */
        if ($this->getConf('force_doa') == true && !in_array('doa',$data)) $data[] = 'doa';

        /* normal-link sanity check:
           if at this point $data is empty, we are dealing with a
           "normal" dokuwiki link and we can let DokuWiki take care of it
        */
        if (empty($data) && empty($link['title']) ) {
          $handler->_addCall('externallink',array( $link['url'],$link['name'] ), $pos);
          return array();
          }
        /* there is still the chance that we are dealing with a media link 
        */

        /* check the rest of the parameters */
        $set      = array(); // {false} if paramenter has not been detected
        $dofetch  = false;
        $dodoa    = false;

        /* check if the page is actually a web page (eg.: not an image) */
        //if (mimetype($link['url']) !== 'text/html') {
        //  $handler->_addCall('externallink',array( $link['url'],$link['name'] ), $pos);
        //  return array();
        //  }

        // list of allowed parameters
        $keys     = array(
        'fetchname', 'doa', 'class', 'rel', 'target',
        );
        if ($this->getConf('link_favicons') == true) $keys[]= 'favicon';

        $options  = array();
        DW_common_linkbonus::parse_options ($data, $link, $keys);

        // parse fetchname
        if (array_key_exists('fetchname', $link)) {
            $dofetch= true;
            if ($link['fetchname'] === '' || empty($link['fetchname'])) $link['fetchname'] = 'title';
            }

        /* -----------------------------------------------------------
           PostProcess some requirements 
         */

        /* if there is any need to fetch external page, do so */
        $xcontents= '';
        $xstatus= array();
       
        $pagestatus= false;
        if ($link['doa'] || $link['fetchname']) {
          $pagestatus = DW_common_linkbonus::_doTestPage($link['url']);
          }

        if ($link['fetchname']) {
          if ($pagestatus == 'text/html') $ext_title= DW_common_linkbonus::_doGetPageTitle($link['url']);
          else $ext_title = $link['name']. " file $pagestatus";

          if ($link['fetchname'] == 'tooltip') $link['title'] = $ext_title;
          else if ($link['fetchname'] == 'title') $link['name']= $ext_title;
          } // end try fetch title

        if (array_key_exists('doa', $link)) {
          $link['class'].= ($pagestatus === false) ? 'doa_error' : 'doa_ok';
          $link['title'].= ' '. $this->getLang(($pagestatus===false) ? 'doa_no' : 'doa_ok');
          unset($link['doa']);
          } // end dead-or-alive check

        $link['class']= 'urlextern '. $link['class']; // default DW external-link class
        $link['format'] = $this->getConf('in_formatting');

        /* -----------------------------------------------------
          at this point we have all the information required to 
          ask the Renderer system to create the link */

        $data= array(
         'link'   => $link,
         'match'  => $match,
         'state'  => $state
         );
        return $data;
      }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
      return DW_common_linkbonus::syntax_render($mode, $renderer, $data, 'external');
      }

}

