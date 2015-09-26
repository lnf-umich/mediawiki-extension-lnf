<?php
class LNFHooks{
    private static $resources = array();
    
    static function onBeforePageDisplay(&$out, &$skin){
        $out->addModules(array("ext.LNF"));
    }

    static function onParserFirstCallInit(&$parser){
        $parser->setHook('lnf-tool-table', 'LNFHooks::renderToolTable');
        $parser->setHook('lnf-tool-property', 'LNFHooks::renderToolProperty');
        $parser->setFunctionHook('lnf-auth-check', 'LNFHooks::renderAuthCheck');
        return true;
    }
    
    static function renderToolTable($input, array $args, Parser $parser, PPFrame $frame){
        global $lnf;
        
        $parser->disableCache();
        
        $tools = $lnf->getToolList();
        
        ob_start();
        require('includes/lnf-tool-table.php');
        $result = ob_get_clean();
        
        return $result;
    }
    
    private static function getCostString($cost){
		return "$".sprintf('%01.2f', $cost->MulVal);
	}
    
    static function renderToolProperty($input, array $args, Parser $parser, PPFrame $frame){
        global $lnf;
        
        $parser->disableCache();
        
        $id = $args['id'];
        $property = $args['property'];
        $format = array_key_exists('format', $args) ? $args['format'] : null;
        $function = array_key_exists('function', $args) ? $args['function'] : null;
        $parsedId = $parser->recursiveTagParse($id, $frame);
        
        if (is_numeric($parsedId) && !array_key_exists($parsedId, self::$resources)){
            self::$resources[$parsedId] = $lnf->getResource($parsedId);
        }
        
        if (array_key_exists($parsedId, self::$resources))
	        $res = self::$resources[$parsedId];
	        
	    if ($res == null)
	    	return $property;
        
        if ($property == "ToolEngineers"){
            if (is_array($res->ToolEngineers)){
	            $html = "<ul>";
	            foreach($res->ToolEngineers as $item)
	                $html .= '<li><a href="mailto:'.$item->Email[0].'">'.$item->DisplayName.'</li>';
	            $html .= "</ul>";
	            return $html;
            }else{
            	$result = self::getPropertyValue($res, "ToolEngineers");
			}
        }else{
        	$result = self::getPropertyValue($res, $property);
        }
        
        if ($function != null){
			$result = self::$function($result);
		}
        
        if ($format == null)
    		return $result;
    	else
    		return sprintf($format, $result);
    }
    
    static function renderAuthCheck(&$parser, $param1, $param2){
        global $lnf;
        
        $parser->disableCache();
        
        $authCheck = $lnf->authCheck();
        $isAuthorized = $authCheck->Authenticated;
        return $isAuthorized ? $param1 : $param2;
    }
    
    static function onUserLoadAfterLoadFromSession($user){
		global $lnf, $wgAuth;

		if ($user->isLoggedIn()){
			return;
		}
		else{
			//$wgAuth->log("no one is logged in, performing authCheck");
		
			//do the authCheck
			$auth = $lnf->authCheck();
			//echo '<textarea style="height: 500px; width: 100%;">';
            //var_dump($auth);
            //echo '</textarea>';
			if ($auth->Authenticated){
				//make sure the username is a proper mediawiki name
				$munged = User::getCanonicalName($auth->UserName);
				
				//get the id of a local account, this will create the account if it doesn't exist
				$id = $wgAuth->getFormsAuthId($munged);
				
				if ($id != 0){
					$user->setId($id);
					$user->loadFromId();
					$user->setEmail($auth->Email);
					$user->mEmailAuthenticated = wfTimestampNow();
					$user->setRealName($auth->FName . ' ' . $auth->LName);
					$user->setPassword(null);
					
					// add user to any available groups
					$allGroups = User::getAllGroups();
					$userGroups = $user->getGroups();
					foreach ($allGroups as $g){
						//only add the group if it is already defined (in LocalSettings.php) and the user is not already a member
						if (in_array($g, $auth->Roles) && !in_array($g, $userGroups)){
							$user->addGroup($g);
						}
					}
					
					$user->saveSettings();
					$wgAuth->updateUser($user);
					$user->saveToCache();
					$user->setCookies();
					wfSetupSession();
				}
			}
		}
	}
	
	static function onUserLoginForm(&$template){
		global $wgLnfRedirectLoginForm, $wgLnfLoginUrl, $wgLnfApiClientId, $wgOut;
        
		$returnTo = isset($_GET["returnto"]) ? $_GET["returnto"] : "";
		$returnQuery = isset($_GET["returntoquery"]) ? $_GET["returntoquery"] : "";
        
        $redirectUri = "http://lnf-linux-dev.eecs.umich.edu/wiki/Special:LNF";
        $separator = "?";
        
        if ($returnTo){
            $redirectUri .= $separator."returnto=".$returnTo;
            $separator = "&";
        }
        
        if ($returnQuery){
            $redirectUri .= $separator."returntoquery=".$returnQuery;
            $separator = "&";
        }
        
        $loginUrl = $wgLnfLoginUrl."?client_id=".$wgLnfApiClientId."&redirect_uri=".urlencode($redirectUri)."&state=lnf-wiki-login";
        
		$template->data["message"] = '<div style="font-size: 18pt; font-weight: bold;"><a href="'.$loginUrl.'">Click here to log in using LNF Online Services</div>';
		$template->data["messagetype"] = "warning";
        
        if ($wgLnfRedirectLoginForm){
            $wgOut->redirect($loginUrl);
       }
	}
    
    //returns the value of an object property, including arrays and nexted objects
    //examples:
    //		1)	when $object->test = "hello"
    //			then getPropertyValue($object, "test") returns "hello"
    //		2)	when $object->test = array("foo"=>"bar")
    //			then getPropertyValue($object, "test[foo]") return "bar"
    //		3)	when $object->test->foo->bar = "hello"
	//			then getPropertyValue($object, "test.foo.bar") return "hello"
	//		4)	when $object->test = array($foo) (and $foo->bar = "hello")
	//			then getPropertyValue($object, "test[0].bar") return "hello"
    private static function getPropertyValue($object, $property){
    	
    	//if something goes wrong just return $property
    	if ($object == null) return $property;
    	
		$result = $object;
		
		foreach (explode(".", $property) as $bit){
			if (preg_match('/(.+)\[(.+)\]/', $bit, $matches)){
				$name = $matches[1];
				$index = $matches[2];
				if (property_exists($result, $name))
					$result = $result->{$name}[$index];
				else
					return $property; //something went wrong
			}else{
				if (property_exists($result, $bit))
					$result = $result->{$bit};
				else
					return $property; //something went wrong
			}
		}
		
		return $result;
	}
}