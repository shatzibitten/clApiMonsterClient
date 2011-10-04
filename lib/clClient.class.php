<?php 

/**
 * Description of clClient
 * API client for citylife.kz
 * 
 * @link API documentation http://dev.citylife.kz/mapsapi
 * @version 0.1
 * @author shatzibitten
 * фывы
 * @todo Поддержка "поисков".
 *       Переписать извлечение информации из блоков
 */
class clClient {
    
    const   ROOT_BLOCK   = "citylife";
    const   GEO_BLOCK    = "geo";
    const   FOUND_BLOCK  = "found";
    const   ITEMS_BLOCK  = "items";
    const   ITEM         = "item";
    const   ERROR_BLOCK  = "error";
    const   ERROR_TEXT   = "text";
    const   TOTAL_BLOCK  = "total_count";
    const   STATUS_BLOCK = "status";
    
    
    private $_URL = "http://api.citylife.kz/";
    private $_API = "X19nZXIxeURubFJUdldtYnBweks=";
    
    private static $XML_MODE  = "XML";
    private static $JSON_MODE = "JSON";
    
    private $mode,
            $response;
       
    public function __construct($url = null, $api = null) {
        $this->_URL = is_null($url) ? $this->_URL : $url;
        $this->_API = is_null($api) ? $this->_API : $api;
    }
    public function setURL($url) {
        $this->_URL = $url;
    }
    public function getURL() {
        return $this->_URL;
    }
    
    public function setAPIkey($key) {
        $this->_API = $key;
    }
    
    public function getAPIkey() {
        return $this->_API;
    }
    
    public function executeQuery(clQuery $query) {
        $format = $query->getFormat();
        
        switch($format) 
         {
            case clQuery::XML_FORMAT:
                $data_source    =  $this->buildRequestUrl($query->getQuery());
                $this->response = new SimpleXMLElement($data_source, NULL, TRUE);
                
                $this->setMode(self::$XML_MODE);
            break;
            
            case clQuery::JSON_FORMAT:
                $jsonURL        = $this->buildRequestUrl($query->getQuery());
                $json           = file_get_contents($jsonURL);
                $this->response = json_decode($json);
                
                $this->setMode(self::$JSON_MODE);
            break;
        
            default:
            break;
        }
    }
    
    /**
     * Get all xml document as SimpleXMLElement, JSON object or raw data.
     * 
     * @param  any Turn on raw data mode
     * @return SimpleXMLElement/JSON object All response in SimpleXMLElement or JSON object
     */
    public function getAllResponse($raw = null) {
        if (is_null($this->response))
                throw new Exception("Please call executeQuery method at first!");
        
        //raw data mode
        if ($raw) 
         {
            
          if ($this->isXmlMode()) 
              return $this->response->asXml();
          elseif ($this->isJsonMode())
              return json_encode($this->response);
         
          
         }
         
        return $this->response;
    }
    
    /**
     * Get total count of found elements
     */
    public function getTotalCount() {
        $total_block = self::TOTAL_BLOCK;
        $found_block = self::FOUND_BLOCK;
        
        if ($this->isXmlMode())
            $response    = $this->response->xpath("//".$total_block);
        else
            $response[0] = $this->response->$found_block->$total_block;
        
        return $response[0];
    }

    /**
     * Get response status
     * @example http://dev.citylife.kz/mapsapi/errors_code
     */
    public function getStatus() {
        $status_block = self::STATUS_BLOCK;
        
        if ($this->isXmlMode())
            $response    = $this->response->xpath("//".$status_block);
        else 
            $response[0] = $this->response->$status_block;
        
        return $response[0];
    }
    
    /**
     * Get response error code
     * @example http://dev.citylife.kz/mapsapi/errors_code
     * 
     * @return string If everything OK - null, otherwise error code
     */    
    public function getErrorCode() {
        $error_block = self::ERROR_BLOCK;
        
        if ($this->isXmlMode()) 
            $response    = $this->response->xpath("//".$error_block."/code");
        else
            $response[0] = $this->response->$error_block->code;
        
        return $response[0];
    }
    
    /**
     * @return string If everything OK - null, otherwise error text 
     */
    public function getErrorText() {
        $error_block = self::ERROR_BLOCK;
        $error_text  = self::ERROR_TEXT;
        
        if ($this->isXmlMode())
            $response    = $this->response->xpath("//".$error_block."/".$error_text);
        else
            $response[0] = $this->response->$error_block->$error_text;
        
        return $response[0];
    }
    
    /**
     * Get information from <items> block.
     */
    public function getFoundItems() {
        $items_block = self::ITEMS_BLOCK;
        $found_block = self::FOUND_BLOCK;
        
        if($this->isXmlMode())
            $response    = $this->response->xpath("//".$found_block."/".$items_block);
        else
            $response[0] = $this->response->$found_block->$items_block;
        
        return $response[0];
    }
    
    /**
     * Get information from <geo> section
     */
    public function getGeo() {
        $geo_block = self::GEO_BLOCK;
        
        if ($this->isXmlMode()) 
            $response    = $this->response->xpath("//".$geo_block);
        else
            $response[0] = $this->response->$geo_block;
        
        return $response[0];
    }
    
    public function addToResultSet($param) {
        
    }

    /** 
     * Build final request url.
     * 
     * @example http://dev.citylife.kz/mapsapi/intro_require_params - about URL structure
     * 
     * @param  string $query Query that was generated by CLQuery object
     * 
     * @return string URL with api key and request parameters 
     */
    private function buildRequestUrl($query) {
        return $this->_URL.$query."&key=".$this->getAPIkey();
    } 
    
    private function isValidAPI() {
        
    } 
    
    private function setMode($mode) {
        $this->mode = $mode;
    }
    
    private function getMode() {
        return $this->mode;
    }
    
    private function isXmlMode() {
        return $this->getMode() === self::$XML_MODE;
    }
    
    private function isJsonMode() {
        return $this->getMode() === self::$JSON_MODE;
    }
    
}
?>
