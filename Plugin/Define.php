<?php

/**
 * Looks up a word on Google Define.
 */
class Phergie_Plugin_Define extends Phergie_Plugin_Abstract_Base
{
    /**
     * Returns whether or not the plugin's dependencies are met.
     *
     * @param Phergie_Driver_Abstract $client Client instance
     * @param array $plugins List of short names for plugins that the
     *                       bootstrap file intends to instantiate
     * @see Phergie_Plugin_Abstract_Base::checkDependencies()
     * @return bool TRUE if dependencies are met, FALSE otherwise
     */
    public static function checkDependencies(Phergie_Driver_Abstract $client, array $plugins)
    {
        if (!self::staticPluginLoaded('TinyUrl', $client, $plugins)) {
            return 'TinyUrl plugin must be enabled';
        }
#        if (!extension_loaded('tidy')) {
#            return 'tidy extension must be enabled';
#        }
        return true;
    }
    
    public function onPrivmsg() 
    {
        $text = $this->event->getText();
        if (strpos($text, ',define ') !== 0 && strpos($text, ',def ') !== 0) {
            return;
        }

        $split = explode(' ', $text);
        array_shift($split);
        if (ctype_digit(end($split))) {
            $offset = array_pop($split);
        } else {
            $offset = 1;
        }
        $term = implode(' ', $split); 

        $url = "http://www.google.com/search?q=define%3A" . urlencode($term) . "&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:official&client=firefox-a";
        $result = file_get_contents($url);

#        $config = array(
#            'indent'       => true,
#            'output-html'  => true,
#            'wrap'         => 200,
#        );
#        $tidy = new tidy();
#        $tidy->parseString($result, $config, 'utf8');
#        $string = tidy_get_output($tidy);
		$string = $result;
        $dom = new DOMDocument();
        @$dom->loadHTML($string);
        $xpath = new DOMXPath($dom);
        $lines = $xpath->query('//li[' . $offset . ']');

        if (!$lines->length) {
            $this->doPrivmsg($this->event->getNick(), 'Definition ' . $offset . ' not found for term ' . $term);
            return;
        }

        $def = $lines->item(0)->nodeValue;
        $max = $this->getPluginIni('max_length');
        if (!$max) {
            $max = 200;
        }
        if (strlen($def) > $max) {
            $def = substr($def, 0, $max) . '...';
        }
        
        $tinyurl = Phergie_Plugin_TinyUrl::get($url);
        $msg = $this->event->getNick() . ': ' . $term . ' - ' . $def . ' [ ' . $tinyurl . ' ]';
        $this->doPrivmsg($this->event->getSource(), $msg);
    }
}
