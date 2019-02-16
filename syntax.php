<?php
/**
 * Webcomics Plugin
 *
 * (The previous author was Christoph Lang <calbity@gmx.de>)
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author LarsDW223
 */

// must be run within Dokuwiki
if (! defined('DOKU_INC')) die();

if (! defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_webcomics extends DokuWiki_Syntax_Plugin
{
    private $comics = array();

    public function __construct() {
        $feeds = explode(';', $this->getConf('feeds'));
        foreach ($feeds as $feed) {
            if (preg_match('/(.*?)="(.*?)"/', $feed, $matches) == 1) {
                if (!empty($matches[1]) && !empty($matches[2])) {
                    $key = strtoupper($matches[1]);
                    $this->comics[$key] = $matches[2];
                }
            }
        }
    }

    public function connectTo ($mode)
    {
        $this->Lexer->addSpecialPattern('<comic=".*?">', $mode, 'plugin_webcomics');
    }

    public function getType ()
    {
        return 'substition';
    }

    public function getSort ()
    {
        return 667;
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $match = substr($match, 8, -2);
        $match = strtoupper($match);

        $url = $this->comics[$match];
        $message = '';
        if (!empty($url)) {
            // Download feed
            $ch = new DokuHTTPClient();
            $piece = $ch->get($url);
            $xml = simplexml_load_string($piece);

            // Get data
            preg_match('/src="(.*?)"/', (string) $xml->channel->item->description, $matches);
            $url = (string) $xml->channel->item->link;
            $src = $matches[1];
            $alt = 'Comic';
            $title = 'Comic';
            if (preg_match('/title="(.*?)"/', (string) $xml->channel->item->description, $matches) == 1) {
                $title = $matches[1];
            }
            if (preg_match('/alt="(.*?)"/', (string) $xml->channel->item->description, $matches) == 1) {
                $alt = $matches[1];
            }

            // Build link
            $link = '<a href="'.$url .'" alt=""><img src="'.$src.'" title="'.$title.'" alt="'.$alt.'"/></a>'."\n";

            return array($link);
        }

        $message = str_replace('%COMIC%', $match, $this->getLang('unknown'));
        return array('', $message);
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml')
        {
            if (!empty($data[0])) {
                $renderer->doc .= $data[0];
            } else {
                msg($data[1], -1);
            }
            return true;
        }
        return false;
    }
}
