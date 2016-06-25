<?php
/**
 * DokuWiki Action Plugin Medialist
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_medialist extends DokuWiki_Action_Plugin {

    /**
     * Register event handlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook(
            'TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'handle_content_display', array()
        );
    }


    /**
     * handler of content display : Post process the XHTML output
     *
     * replace medialst placeholders in xhtml of the page
     */
    public function handle_content_display(Doku_Event $event, $param) {
        global $ACT;

        if ($ACT != 'show') return;

        $pattern = '#<!-- MEDIALIST:([^\r\n]+?) -->#';
        if (strpos($event->data, substr($pattern, 1, 14)) !== false) {

            // regular expression search and replace using anonymous function callback
            $event->data = preg_replace_callback( $pattern,
                function ($matches) {
                    $medialist = $this->loadHelper('medialist');
                    $data = '{{medialist>'.$matches[1].'}}';
                    $params = $medialist->parse($data);
                    return $medialist->render_xhtml($params);
                },
                $event->data
            );
            return true;
        }
    }

}

