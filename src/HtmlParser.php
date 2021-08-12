<?php

namespace WebUtils;

class HtmlParser 
{

    /**
     * URL address
     *
     * @var String
     */
    private $url;

    /**
     * Host name
     *
     * @var string
     */
    private $host;

    /**
     * DOMDocument object
     *
     * @var DOMDocument
     */
    private $dom;

    /**
     * DOMXPath object
     *
     * @var DOMXPath
     */
    private $domxpath;

    /**
     * Contains result after XPath query
     *
     * @var DOMNodeList
     */
    private $content;

    /**
     * Initializes a new session and parse html.
     *
     * @param string $url       Url
     * @param array $options    Settings for curl
     * @return HtmlParser
     */
    public function source($url, $options = []): HtmlParser
    {
        $this->url = $url;
        $this->host = '';
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0); 

        $content = curl_exec($ch);
        curl_close($ch);

        $this->dom = new \DOMDocument();
        // Get rid from spaces
        $this->dom->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $this->dom->loadHTML($content);
        libxml_use_internal_errors(false);

        $this->domxpath = new \DOMXPath($this->dom);

        $url_info = parse_url($this->url);

        if ($url_info) {
            if (isset($url_info['scheme'])) {
                $this->host .= $url_info['scheme'] . '://';
            }
            $this->host .= $url_info['host'] . '/';
        }
        
        return $this;
    }

    /**
     * Get host name
     *
     * @return String
     */
    public function getHost(): String
    {
        return $this->host;
    }

    /**
     * Return array of URLs
     *
     * @param String $attr  Attribute name which will be used for determine URL 
     * @param Boolean $relative Whether original URL contains host 
     * @param mixed $key    Name for key in array
     * @return Array
     */
    public function getURL($attr, $relative = false, $key = null): Array
    {
        $links = [];

        if ($this->content) {

            foreach($this->content as $item) {

                if ($item->hasAttribute($attr)) {
 
                    $value = $item->getAttribute($attr);
                    
                    if ($relative) {
                        $value = $this->host . $item->getAttribute($attr);
                    }
                    
                    $value = \str_replace(' ', '%20', $value);
                    
                    if ($key) {

                        if (\is_array($key)) {
                            $links[$item->{$key['name']}] = $value;
                        } else if (is_callable($key)) {
                            
                            $name = $key($item);
                            
                            if ($name) {
                                $links[$name] = $value;
                            } else {
                                exit("Unable to determine name!\n");
                            }
                            
                        } else {
                            $links[$key] = $value;
                        }
                            

                    } else {
                        
                        $links[] = $value;
                    }
 
                } 

            }

        }
        
        return $links;
    }

    /**
     * Returns node's text value.
     * 
     * If there are several nodes, than function return array of values,
     * otherwise function return string
     *
     * @param string $attribute Attibute name for text
     * @return array|string
     */
    public function getNodeText($attribute = 'textContent')
    {        
        if ($this->content) {
            if ($this->content->count() > 1) {
                $result = [];
                foreach($this->content as $node) {                
                    $result[] = $node->$attribute;
                }
                return $result;
            } else {                
                return $this->content[0]->$attribute;
            }            
        } 
        return null;
    }
   

    /**
     * Returns assocciative array
     *
     * @param integer $in_row           Collumns count in a row
     * @param integer $key              Position in the row which will be used as a key 
     * @param integer $value            Position in the row which will be used as a value 
     * @param string $key_attribute     Attibute name for key text. By default 'textContent'
     * @param string $value_attribute   Attibute name for value text. By default 'textContent'
     * @return Array
     */
    public function getAssocArray(int $in_row, int $key, int $value, string $key_attribute = 'textContent', string $value_attribute = 'textContent'): Array
    {
        $array = [];
        
        if ($this->content) {
            $rows = $this->content->count() / $in_row;
            
            $current_key = $key;
            $current_value = $value;

            for ($i=0; $i <$rows ; $i++) { 
                $array[$this->content[$current_key]->$key_attribute] = $this->content[$current_value]->$value_attribute;
                $current_key += $in_row;
                $current_value += $in_row;
            }
        }
        
        return $array;

    }

    /**
     * Returns result of query
     *
     * @return DOMNodeList
     */
    public function getNodes(): DOMNodeList
    {
        return $this->content;
    }


    /**
     * Select nodes from DOMXPath 
     *
     * @param string $xpath
     * @return HtmlParser
     */
    public function select($xpath): HtmlParser
    {
        $this->content = $this->domxpath->query($xpath);
        return $this;
    }

}
