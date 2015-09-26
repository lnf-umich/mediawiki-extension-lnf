<?php
class LNF{
    function getCookieName(){
        return 'lnf_token';  
    } 
    
    function getBaseUrl(){
        global $wgLnfApiUrl;
        
        $result = $wgLnfApiUrl;
        
        if (!$this->endsWith($result, "/"))
            $result .= "/";
        
        return $result;
    }
    
    function getToolList(){
        return $this->apiGet("scheduler/resource-info?IsActive=true");
    }
    
    function getResource($id){
        return $this->apiGet("scheduler/resource?id=$id", true);
    }
    
    function authCheck(){
        return $this->apiGet("authcheck", true);
    }
    
    function getAuthorization($code){
        global $wgLnfApiClientId, $wgLnfApiClientSecret;
        
        return $this->apiPost("oauth2/token", array(
            "client_id" => $wgLnfApiClientId,
            "client_secret" => $wgLnfApiClientSecret,
            "grant_type" => "authorization_code",
            "code" => $code
        ));
    }
    
    private function apiPost($path, $data = null, $authorize = false){

        $opts = array(
            'http' => array(
                'method' => "POST",
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        
        if ($data){
            $postData = http_build_query($data);
            $opts['http']['content'] = $postData;
            $opts['http']['header'] .= "Content-Length: ".strlen($postData)."\r\n";
        }
        
        if ($authorize && isset($_COOKIE[$this->getCookieName()]))
            $opts['http']['header'] .= "Authorization: Bearer ".$_COOKIE[$this->getCookieName()]."\r\n";
        
        $context = stream_context_create($opts);
        
        $json = @file_get_contents($this->getBaseUrl().$path, false, $context);
        
        if (!$json){
            $error = error_get_last();
            throw new Exception($error['message']);
        }
        
        return json_decode($json);
    }
    
    private function apiGet($path, $authorize = false){
        $headers = array();
        
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => ""
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        );
        
        if ($authorize && isset($_COOKIE[$this->getCookieName()]))
            $opts['http']['header'] .= "Authorization: Bearer ".$_COOKIE[$this->getCookieName()]."\r\n";
        
        $context = stream_context_create($opts);
        
        $json = file_get_contents($this->getBaseUrl().$path, false, $context);
        
        return json_decode($json);
    }
    
    //http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }
    
    function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
}