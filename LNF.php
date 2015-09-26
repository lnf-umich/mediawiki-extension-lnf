<?php
$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'LNF',
    'author' => 'Jim Getty', 
    'url' => 'http://lnf.umich.edu', 
    'description' => 'Some LNF customizations for MediaWiki',
    'version'  => 0.2
);

$wgAutoloadClasses['LNF'] = __DIR__ . '/LNF.body.php';
$wgAutoloadClasses['LNFAuth'] = __DIR__ . '/LNF.auth.php';
$wgAutoloadClasses['LNFHooks'] = __DIR__ . '/LNF.hooks.php';
$wgAutoloadClasses['LNFSpecial'] = __DIR__ . '/LNF.special.php';

$wgResourceModules['ext.LNF'] = array(
	"scripts"		=> array("js/lnf.js"),
	"styles"		=> array("css/lnf.css"),	
	"localBasePath"	=> __DIR__,
	"remoteExtPath"	=> "LNF"
);

$wgMessagesDirs['LNF'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['LNFMagic'] = __DIR__ . '/LNF.i18n.magic.php';
$wgExtensionMessagesFiles['LNFAlias'] = __DIR__ . '/LNF.alias.php';
$wgSpecialPages['LNF'] = 'LNFSpecial';

$lnf = new LNF();

if (!isset($wgLnfEnableOAuth))
    $wgLnfEnableOAuth = false;

if ($wgLnfEnableOAuth){
    $wgAuth = new LNFAuth();
    $wgHooks['UserLoadAfterLoadFromSession'][] = 'LNFHooks::onUserLoadAfterLoadFromSession';
    $wgHooks['UserLoginForm'][] = 'LNFHooks::onUserLoginForm';
}

$wgHooks['BeforePageDisplay'][] = 'LNFHooks::onBeforePageDisplay';
$wgHooks['ParserFirstCallInit'][] = 'LNFHooks::onParserFirstCallInit';