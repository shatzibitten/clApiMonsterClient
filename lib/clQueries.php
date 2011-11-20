<?php
/**
 * Query generators for citylife.kz request's
 *
 * @author   shatzibitten
 * @version  0.2
 * @internal 21.11.2011 - add freeze status to query objects. 
 * 
 * @todo: Query classes generator.
 */
include "lib/sfYaml.php";

/**
 * @todo Добавить различные возможности хранения настроек.
 *       Приводить к CamelCase названия параметров запроса  
 */
class clQuery { 
    const   XML_FORMAT              = ".xml";   //response type
    const   JSON_FORMAT             = ".json";  //response type
    //used in __call method to indicate special method call
    const   GET_BY_PATTERN          = "getBy";   
    const   GET_ALL_PATTERN         = "getAll"; 
    const   QUERY_PARAM_DELIMITER   = "&";
    const   REQUIRED_PARAM          = "*";
    
    protected $sourceName,
              $params,
              $registeredParams,
              $format,
              $config,
              $context,
              $query;
    
    private $_freeze        = false;
    private $requiredParams = array();
    private $_cnfName       = "config.yml";
    
    public function __construct($context = null) {
        try 
         {  
                   
          $this->loadConfig();
          
          if ($this->isValidContext($context))
           {
            $this->context = $context;
           }
           
          $this->registeredParams   = array();
          $this->requiredParams     = $this->collectRequiredParams();
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
    }
    
    /*
     * Return object. You can create object chain.
     * @exceptions: Throw exception if object was frozen before.
     */
    public function __call($name, $args) {       
//        if (preg_match("#^".self::GET_ALL_PATTERN."#i", $name, $match))
//         {
//          $this->setQuery(""); 
//         }
        
        if ($this->wasFrozen())
         {
          throw new Exception("Object was frozen!");   
         }
         
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
             $this->regParam($match[1]); 
            }
           else 
            {
             throw new Exception("Call to undefined method clQuery");   
            }
            
            return $this;
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
    
    public function getRequiredParams() {
        return $this->requiredParams;
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
    
    public function validateQuery() {
        $req = $this->getRequiredParams();
        if (empty($req))
         {
          return true;  
         }
        
        return false;
    }
    
    /**
     * Freeze query. After calling this method you cannot to add or delete parameters to query.
     */
    public function freeze() {
        $this->_freeze = true;
        
        return true;
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
     * Validate context
     * @param  string $context
     * @return boolean Die if context isn't correct, otherwise return true
     */
    private function isValidContext($context) {
        
        if (!isset($this->config[$context]))
         {
          die("{$context} option doesn't exist! Check your file - ".$this->getConfigFileName());  
         }
        
        return true; 
    }
    
    /**
     * Retrive option from config file by key.
     * 
     * @param  string $option Option key
     * @return string Return option value from config file.  
     */
    private function getConfigOption($option) {
        
        $option = strtolower($option);
                   
        if (in_array($option, $this->clearParam($this->config[$this->context])))
         { 
          return $option;  
         }
        
        return false;
    }
    
    /**
     * Retrive all params of the current context
     * @return array  Array of parameters
     */
    private function getAllOptions() {
        return $this->config[$this->context];
    }
    
    private function getRegistredParams() {
        return $this->registeredParams;
    }
    
    /**
     * Register new query parameter.
     * @param type $param 
     * 
     */
    private function regParam($param) {
        array_unshift($this->getRegistredParams(), $param); //reg param
          
        //exclude param from array if in it
        $req_array = $this->getRequiredParams();

        if (in_array(strtolower($param), $req_array))
         {
            $srchd_key = array_search(strtolower($param), $req_array);
            unset($this->requiredParams[$srchd_key]);
         }
    }
    
    /**
     * Collect all required parameters from config file. 
     */
    private function collectRequiredParams() {
        $dst = array();
        foreach ($this->getAllOptions() as $param) 
         {
            if ($this->paramIsRequired($param) === true) 
             {
                $dst[] = $this->clearParam($param);   
             }
 
         }

         return $dst;
    }
    

    private function paramIsRequired($param) {
       
        if (stripos($param, "*") !== false)
               return true;
       
       return false;
    }
    
    /**
     * Delete required identificator from param
     * @param  string $param parameter name
     * @return string Common parameter without required param identificator 
     */
    private function clearParam($param) {
        return str_replace(self::REQUIRED_PARAM,"",$param);
    }
            
    /**
     * Generate root query.
     */
    private function generateRoot() {
       return $this->sourceName."/".$this->context.$this->format."?";
    }
    
    /**
     * Check current freeze status.
     * @return boolean return freeze status 
     */
    private function wasFrozen() {
        return $this->_freeze;
    }

}

class clReferencesQuery extends clQuery {
    const   TOWN_CONTEXT           = "towns";
    const   COUNTRY_CONTEXT        = "countries";
    const   CMPNY_TYPE_CONTEXT     = "company_types";
    const   BANKS_CONTEXT          = "banks";
    const   AUTOCHARGES_CONTEXT    = "autocharges";
    const   WORD_CONTEXT           = "word_suggestions";
    
    protected $sourceName   = "reference";   
}

class clSearchQuery extends CLQuery {
    const CMPNY_CONTEXT         = "company";
    const AUTOBUS_CONTEXT       = "autobus_stopping";
    const CASHDISP_CONTEXT      = "cashdisp";
    const AUTOCHARGES_CONTEXT   = "autocharge";
    const AUTOBUS_ROUTE_CONTEXT = "autobus_route";
    
    protected $sourceName   = "search"; 
}
?>
