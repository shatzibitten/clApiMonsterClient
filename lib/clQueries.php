<?php
/**
 * Query generators for citylife.kz request's
 *
 * @author shatzibitten
 * @version 0.1
 */
include "lib/sfYaml.php";

/**
 * @todo Добавить различные возможности хранения настроек.
 */
class CLQuery { 
    const   XML_FORMAT      = ".xml";   //response type
    const   JSON_FORMAT     = ".json";  //response type
    //used in __call method to indicate special method call
    const   GET_BY_PATTERN  = "getBy";   
    const   GET_ALL_PATTERN = "getAll"; 
    const   QUERY_PARAM_DELIMITER = "&";
    
    protected $sourceName,
              $registeredParams,
              $format,
              $config,
              $context,
              $query;
    
    private $_cnfName = "config.yml";
    
    public function __construct($context = null) {
        try 
         {  
          $this->context            = $context;
          $this->registeredParams   = array();
          $this->format             = self::XML_FORMAT; //default format
          $this->query              = $this->generateRoot();

          if (is_null($context))
           {
            throw new Exception("Context must be init!"); 
           }
           
         }
        catch (Exception $e)
         {
          echo $e->getLine(); 
          die($e->getMessage());  
         }
         
        $this->loadConfig();
    }
    
    public function __call($name, $args) {       
//        if (preg_match("#^".self::GET_ALL_PATTERN."#i", $name, $match))
//         {
//          $this->setQuery(""); 
//         }
         
        if (preg_match("#^".self::GET_BY_PATTERN."(\w+)#i", $name, $match)) 
         {
           if ($this->isValidAttribute($match[1])) 
            {
             //check param duplicates in query and ignore it in query  
             if (in_array($match[1],$this->getRegistredParams()))
              {
               return;   
              }
              
             $key   = strtolower($match[1]);
             $value = is_null($args[0]) ? 0 : $args[0];
             $param = $key."=".$value;
             $this->appendQueryParams($param);
             $this->registerParam($match[1]); 
            }
           else 
            {
             throw new Exception("Call to undefined method CLQuery");   
            }
         }
    }
    
    public function setQuery($query) {
        $this->query = $query;
    }
    
    /**
     * Append params to the query
     * @param type $params Array or string
     */
    public function appendQueryParams($params) {
        $new_query = $this->getQuery();
        
        if (is_array($params))
         {
          $new_query .= http_build_query($params);  
         }
        else
         {
          $new_query .= self::QUERY_PARAM_DELIMITER.$params;
         }
        
       $this->setQuery($new_query);
    }
    
    public function getQuery() {
        return $this->query;
    }
    
    /**
     *
     * @param type $format
     * @todo Сделать переопределение query с сохранением текущих параметров запроса
     */
    public function setFormat($format) {
        $this->format = $format;
        
        $this->setQuery($this->generateRoot()); //we must regen query root because format is changed
    }
    
    public function getFormat() {
        return $this->format;
    }
    
    /**
     * Each data source or source type has own config file 
     * @return type Name of the config file.
     */
    protected function getConfigFileName() {
        return $this->sourceName."-".$this->_cnfName;
    }
    
    private function loadConfig() {
        $this->config = sfYaml::load($this->getConfigFileName());
    
    }
    
    /**
     * Validate attribute
     * @param  string $attr Attribute name. Must be equal with option (in context) from config file.
     * @return boolean true if attribute name exist in config file, otherwise return false
     */
    private function isValidAttribute($attr) {
        if ($this->getConfigOption($attr))
         {
          return true;
         }
         
        return false;
    }
    
    /**
     * Retrive option from config file by key.
     * 
     * @param  string $option Option key
     * @return string Return option value from config file.  
     */
    private function getConfigOption($option) {
        if (!isset($this->config[$this->context]))
         {
          die("{$this->context} option doesn't exist! Check your file - ".$this->getConfigFileName());  
         }

        $option = strtolower($option);
         
          
        if (in_array($option, $this->config[$this->context]))
         { 
          return $option;  
         }
        
        return false;
    }
    
    private function getRegistredParams() {
        return $this->registeredParams;
    }
    
    private function registerParam($param) {
        array_unshift($this->registeredParams, $param);        
    }
    
    /**
     * Generate root query.
     */
    private function generateRoot() {
       return $this->sourceName."/".$this->context.$this->format."?";
    }

}

class CLReferencesQuery extends CLQuery {
    const   TOWN_CONTEXT           = "towns";
    const   COUNTRY_CONTEXT        = "countries";
    const   CMPNY_TYPE_CONTEXT     = "company_types";
    const   BANKS_CONTEXT          = "banks";
    const   AUTOCHARGES_CONTEXT    = "autocharges";
    const   WORD_CONTEXT           = "word_suggestions";
    
    protected $sourceName   = "reference";   
}

class CLSearchQuery extends CLQuery {
    protected $sourceName   = "search"; 
}
?>
