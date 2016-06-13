<?php
/**
 * Helper Component for Medialist plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_medialist extends DokuWiki_Plugin {


    /**
     * Renders xhtml
     */
   public function render_xhtml($data) {
        $out  = '';
        $items = array();

        list($state, $params) = $data;
        $id    = $params['id'];
        $scope = $params['scope'];

        // search option for lookup_stored_media()
        if (array_key_exists('depth', $params)) {
            $opt = array('depth' => $params['depth']);
        } else {
            $opt   = array();
        }

        switch ($scope) {
            case 'page':
                $media = $this->_lookup_linked_media($id);
                foreach ($media as $item) {
                    // note: base value is not valid for external link items
                    $items[] = array('level'=> 1, 'id'=> $item, 'base'=> getNS($item));
                }
                break;
            case 'ns':
                $media = $this->_lookup_stored_media($id, $opt);
                foreach ($media as $item) {
                    $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id);
                }
                break;
            case 'both':
                $linked_media = $this->_lookup_linked_media($id);
                $stored_media = $this->_lookup_stored_media(getNS($id), $opt);
                $media = array_unique(array_merge($stored_media, $linked_media));
                foreach ($media as $item) {
                    if (in_array($item, $linked_media)) {
                        $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id, 'linked'=> 1);
                    } else {
                        $items[] = array('level'=> 1, 'id'=> $item, 'base'=> $id);
                    }
                }
                break;
        }

        if (!empty($items)) {
            $out .= html_buildlist($items, 'medialist', array($this, '_media_item'));
        } else {
            $out .= '<div class="medialist info">';
            $out .= '<strong>'.$this->getPluginName().'</strong>'.': nothing to show here.';
            $out .= '</div>';
        }
        return $out;
    }

    /**
     * Callback function for html_buildlist()
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    public function _media_item($item) {
        global $conf, $lang;

        $out = '';

        $link = array();
        $link['url']    = ml($item['id']);
        $link['class']  = isset($item['linked']) ? 'media linked' : 'media';
        $link['target'] = $conf['target']['media'];
        $link['title']  = noNS($item['id']);

        // link text and mediainfo
        if (preg_match('#^https?://#', $item['id'])) {
            // External link
            $link['name'] = $item['id'];
            $mediainfo = $lang['qb_extlink']; // External Link
        } else {
            // Internal file
            if (array_key_exists('base', $item)) {
                $link['name'] = str_replace($item['base'].':','', $item['id']);
            } else {
                $link['name'] = $item['id'];
            }
            $fn = mediaFN($item['id']);
            $mediainfo  = strftime($conf['dformat'], filemtime($fn)).'&nbsp;';
            $mediainfo .= filesize_h(filesize($fn));
        }

        // add file icons
        list($ext,$mime) = mimetype($item['id']);
        $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
        $link['class'] .= ' mediafile mf_'.$class;

        // build the link
        $out .= '<a href="' . $link['url'] . '" ';
        $out .= 'class="' . $link['class'] . '" ';
        $out .= 'target="' . $link['target'] . '" ';
        $out .= 'title="' . $link['title'] . '">';
        $out .= $link['name'];
        $out .= '</a>';
        $out .= '&nbsp;<span class="mediainfo">('.$mediainfo.')</span>' . DOKU_LF;

        return $out;
    }


    /**
     * searches media files linked in the given page
     * returns an array of items
     */
    protected function _lookup_linked_media($id) {
        $linked_media = array();

        if (!page_exists($id)) {
            //msg('MediaList: page "'. hsc($id) . '" not exists!', -1); 
        }

        if (auth_quickaclcheck($id) >= AUTH_READ) {
            // get the instructions
            $ins = p_cached_instructions(wikiFN($id), true, $id);

            // get linked media files
            foreach ($ins as $node) {
                if ($node[0] == 'internalmedia') {
                    $linked_media[] = cleanID($node[1][0]);
                } elseif ($node[0] == 'externalmedia') {
                    $linked_media[] = $node[1][0];
                }
            }
        }
        return array_unique($linked_media);
    }

    /**
     * searches media files stored in the given namespace and sub-tiers
     * returns an array of items
     */
    protected function _lookup_stored_media($ns, $opt=array('depth'=>1)) {
        global $conf;

        $intern_media = array();

        $dir = utf8_encodeFN(str_replace(':','/', $ns));

        if (!is_dir($conf['mediadir'] . '/' . $dir)) {
            //msg('MediaList: namespace "'. hsc($ns). '" not exists!', -1);
        }

        if (auth_quickaclcheck("$ns:*") >= AUTH_READ) {
            // get mediafiles of current namespace
            $res = array(); // search result
            search($res, $conf['mediadir'], 'search_media', $opt, $dir);

            foreach ($res as $item) {
                $intern_media[] = $item['id'];
            }
        }
        return $intern_media;
    }

}

