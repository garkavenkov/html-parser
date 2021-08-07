<?php

namespace WebUtils;

class Parser 
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
     * Constructor
     *
     * @param string $url
     * @param array $options
     */
    public function __construct($url, $options = [])
    {
        $this->url = $url;
        
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
     * Undocumented function
     *
     * @param String $attr
     * @param mixed $key
     * @return Array
     */
    public function getURL($attr, $key = null): Array
    {
        $links = [];

        if ($this->content) {

            foreach($this->content as $item) {

                if ($item->hasAttribute($attr)) {
 
                    $value = $this->host . $item->getAttribute($attr);

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
     * Select nodes from DOMXPath 
     *
     * @param string $xpath
     * @return Parser
     */
    public function select($xpath): Parser
    {
        $this->content = $this->domxpath->query($xpath);
        return $this;
    }

}
