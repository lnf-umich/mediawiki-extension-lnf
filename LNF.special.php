<?php
class LNFSpecial extends SpecialPage {
	function __construct() {
		parent::__construct('LNF');
        $this->selfTitle = SpecialPage::getTitleFor('LNF');
	}

	function execute($par) {
        global $lnf;
        
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
        
        $code = $request->getVal("code");
        $state = $request->getVal("state");
        
        if ($code && $state == "lnf-wiki-login"){
            try{
                $auth = $lnf->getAuthorization($code);
                if ($auth){
                    setcookie($lnf->getCookieName(), $auth->access_token);
                    $returnTo = $request->getVal('returnto');
                    $returnQuery = $request->getVal('returnquery');
                    $output->redirect($returnTo.$returnQuery);
                }
            }
            catch(Exception $ex){
                $output->addWikiText('<div style="color: #ff0000;">error: '.$ex->getMessage().'</div>');
            }
        } else {        
            $output->setPageTitle(wfMessage('lnf-title')->text());
            
            # Get request data from, e.g.
            $param = $request->getText( 'param' );

            # Do stuff
            # ...
            $wikitext = 'Hello world!';
            $wikitext .= '<pre>';
            $wikitext .= print_r($request, true);
            $wikitext .= '</pre>';
            $output->addWikiText($wikitext);
        }
	}
}