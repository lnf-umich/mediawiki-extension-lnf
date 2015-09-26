<?php
class LNFAuth extends AuthPlugin{
    function getFormsAuthId($username){
		//at this point $username should already be munged
		$temp = User::newFromName($username);
        
		if ($temp != null){
			if ($temp->getID() == 0){
				// user does not exist so create it
				$temp->loadDefaults($username);
				$temp->addToDatabase();
				$this->initUser($temp, true);
			}
				
			return $temp->getID();
		}
        
		return 0;
	}
}