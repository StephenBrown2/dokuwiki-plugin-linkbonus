<?php
/**
 * @file       linkbonus/common.php
 * @brief      Common functions for the linkbonus plugin
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    0.2
 * @author     Luis Machuca Bezzaza <luis.machuca [at] gulix.cl>
 */

/* must be invoked from syntax/*.php
 */
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) die();
@require_once (DOKU_INC. 'inc/confutils.php'); // for mimetype()

/**
 * @class DW_common_linkbonus
 * @brief Common interface for linkbonus preliminar
 */

class DW_common_linkbonus {

    static public function getInfo ($phandle=null) {
        return array(
        'author' => 'Luis Machuca Bezzaza',
        'email'  => 'luis.machuca [at] gulix.cl',
        'date'   => '2010-09-30',
        'name'   => 'Linkbonus Plugin [preliminar]',
        'desc'   => $phandle->getLang('descr'),
        'url'    => 'http://ryan.gulix.cl/tests/doku.php/playground:linkbonus',
        );
      }

    static public function getType () {
      return 'formatting'; 
      }

    static public function getAllowedTypes() { 
      return array('formatting');
      }

    static public function getPType () {
      return 'normal';
      }


    static public function syntax_render ($mode, &$renderer, $data, $type='external') {
      if ($mode == 'xhtml') {
        if (!empty($data) ) {
          // do XHTML rendering
          $info= array();
          $link = $data['link'];
          $link['url']  = hsc($link['url']);
          // the following lines allow for rendering of internal syntax
          // but it is done the "wrong" way -- weird things may happen!
          //$link['name']= htmlentities($link['name'], ENT_NOQUOTES);
          //$link['name']= p_render('xhtml', p_get_instructions($link['name']) , $info);
          //$link['name']= substr($link['name'], 4, -5); // remove surrounding <p>..</p>

          if (array_key_exists('favicon', $link) && $type==='external' && !DW_common_linkbonus::_isSSL()) {
            $fvicon= DW_common_linkbonus::_getFavicon($link['url']);
            $link['more'].= ' style="background-image: url('. $fvicon. '); background-size: 16px 16px;"';
            }
          $fmt_enabled= $link['format'];
          if ($fmt_enabled) {
              $link['name']= preg_replace('#//(.+?)//#', '<em>$1</em>', $link['name']);
              $link['name']= preg_replace('#\*\*(.+?)\*\*#', '<b>$1</b>', $link['name']);
              //$link['name']= preg_replace('#//(.+?)//#', '<em>$1</em>', $link['name']);
          }
          $outp= $renderer->_formatLink($link);
          // substitute //italics// and **bold**
          
          //$renderer->doc .= '<pre>'. htmlspecialchars($outp). '</pre>';
          $renderer->doc .= '<!-- linkbonus to ['. $link['url']. '] -->';
          $renderer->doc .= $outp;
          } 
        return true;
        } // end XHTML parsing
      return false;
      }



    /**
     * Helper functions from this point 
     */

    /**
     * @fn     _getServerBase
     * @brief  Returns the base URL of the server
     * For use of the external syntax mode, maybe others.
     */
    static public function _getServerBase () {
        return ($_SERVER['HTTPS']) ? 'https' : 'http'. '://'. $_SERVER['SERVER_NAME'];
        //return $ans;
        }

    static public function _isSSL () {
        return ($_SERVER['HTTPS']) ? true : false;
        }

    /**
     * @fn     _doTestPage
     * @brief  Tests if the page is available
     * @return false if there is a retrieve error; the target type otherwise
     */
    function _doTestPage ($url) {

      $context = stream_context_create(DW_common_linkbonus::_getDLContext());
      $fc      = @file_get_contents ($url, false, $context, 0, 2048);
      if ($fc === false) return false; // can not retrieve;
      else {
        // store the contents in a file to identify
        $tmpfname = tempnam('/tmp', 'dwlb_');
        //echo "**$tmpfname*";
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $fc);
        fclose($handle);
        $type= '';
        // use fileinfo to identify, we want a "text/html" file
        $type = trim(shell_exec("file -bi " . $tmpfname));
        return $type;

        }
      } // end function


    function _doGetPageTitle ($url) {
        $fetchsz  = 2048; // will try to locate the title in the first 2kib
        $context  = stream_context_create(DW_common_linkbonus::_getDLContext());
        $fc       = @file_get_contents ($url, NULL , $context , 0, $fetchsz);
        $title    = array();
        // try find the title inside
        $res      = preg_match ("/<title>(.+)<\/title>/is", $fc, $title);
        if ($res == 1) {
            $title= trim($title[1]);
            }
        else {
            $title= false;
            }
      // if there is a title limit, we return only the indicated number of words
/*
      $limit= $this->getConf('titlelimit');
      if ($limit != 'no') {
        $words= explode (' ', $title);
        $wc   = count($words);
        for ($i= $limit; $i < $wc; $i++) {
          array_pop($words);
          }
        $title= implode(' ', $words);
        }
*/

      return $title;
    }

    /**
     @brief parse a "key=value|key2=value2" string into an options map
     @param data the array containing the input data
     @param[Out] options a { key=>value } map that will contain the parsed values
     @param keys the list of allowed keys
    **/
    static public function parse_options ($data, &$options, $keys) {
        $allow_all = ($keys === null);
        foreach ($data as $D) {
            list ($key, $val) = explode ('=', $D, 2);
            if (!empty($val)) $val= substr($val, 1, -1); // unquote
            else $val= ''; // explicit empty string
            // if the key can be added and check in, add it in with its value
            if ($allow_all || in_array($key, $keys, true)) {
                $options[$key] = $val;
                }
            } 
        // exhausted all keys at this point
        
    }

    static private function _getDLContext () {
        $timeout= 1.0;
        if ($timeout > 5.0) $timeout= 5.0;
        else if ($timeout < 0.5) $timeout= 0.5;
        $copts = array(
        'http' => array(
                 'method' => 'GET',
                 'max_redirects' => '2',
                 'timeout' => "$timeout",
                 )
        );
        return $copts;
    } // end function

    static public function _getFavicon($domain) {
        // Get the root of the domain
        $elems  = parse_url($domain);
        $domain = $elems['scheme'].'://'.$elems['host'];

        // Check if the favicon.ico is where it usually is.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$domain.'/favicon.ico');
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_exec($ch);
        $rcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ( $rcode == '200' ) {
            $favicon = $domain . '/favicon.ico';
            return $favicon;
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$domain);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            $output = curl_exec($ch);
            curl_close($ch);

            //$output = file_get_contents("$domain");
            // If not, we'll parse the DOM and find it
            $dom = new DOMDocument;
            $dom->loadHTML($output);

            // Get all 'link' elements that are children of 'head'
            $linkElements = $dom
                            ->getElementsByTagName('head')
                            ->item(0)
                            ->getElementsByTagName('link');

            foreach($linkElements as $element) {
                if ( ! $element->hasAttribute('rel')) {
                    continue;
                }

                $rel = $element->getAttribute('rel');

                if ( $rel == 'shortcut icon' || $rel == 'icon' ) {
                    $favicon = $element->getAttribute('href');

                    $favicon_elems = parse_url($favicon);

                    # if relative
                    if(!isset($favicon_elems['host'])){
                       $favicon = $domain . '/' . $favicon;
                    }

                    return $favicon;
                }
            }
        }
        return '/lib/images/external-link.png';
    }


} // end class


