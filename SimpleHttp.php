<?php

/**
 * Very simple http abstraction based on curl.
 * The curl extension has to be installed.
 */  
class SimpleHttp {

    /**
     * Check, that the curl extension is instaled
     */
    public function __construct() {
        if (! function_exists('curl_init')) {
            throw new Exception('SimpleGoogleLogin requires the CURL PHP extension');
        }
    }

    /**
     * Transforms the supplied key-values list into an form 
     * encoded string. The values will be urlencoded.
     */
    public function arrayFormEncode($params) {
        $paramString = '';
        foreach($params as $key=>$value) { $paramString .= $key.'='.urlencode($value).'&'; }
        $paramString = rtrim($paramString,'&');
        return $paramString;
    }    

    /**
     * does a curl POST request.
     * $url the url
     * $data assoziative array with parameters for the url
     * $headerList an array of header lines
     */
    public function postRequest($url, $params=[], $headerList=[]) {
        $httpResult = new HttpResult();
        
        $ch = curl_init();
        
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$this->arrayFormEncode($params));
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerList);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);    

        if($result === false) {
            $httpResult->errorMessage = curl_error($ch);
        } else {
            $httpResult->body = $result;
        }

        $httpResult->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return $httpResult;
    }
    
    /**
     * does a curl GET request.
     * $url the url
     * $data assoziative array with parameters for the url
     * $headerList an array of header lines
     */
    public function getRequest($url, $params=[], $headerList=[]) {
        $httpResult = new HttpResult();

        $ch = curl_init();
        
        if (count($params) > 0) {
            $url .= '?'.$this->arrayFormEncode($params);
        }
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerList);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if($result === false) {
            $httpResult->errorMessage = curl_error($ch);
        } else {
            $httpResult->body = $result;
        }
        
        $httpResult->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpResult;
    }
}

class HttpResult {
    public $body;
    public $code;        
    public $errorMessage;
}

?>