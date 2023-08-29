<?php

// Tweaked 03/23/2011 by Grantovich to use CN instead of givenName + SN

// PHP 7 Compatability ~ <3 mbillow 03/14/2017

// MediaWiki 1.36 compat ~ <3 mstrodl 2023-01-25

global $wgHooks, $wgOut;
$wgHooks['ParserBeforeInternalParse'][]      = 'parseUserSignatures';
$wgExtensionCredits['parserhook'][] = array(
	'version'     => '0.0.1',
	'name'        => 'CSH Usersig',
	'author'      => 'Computer Science House',
	'email'       => 'webmaster@csh.rit.edu',
	'url'         => 'https://csh.rit.edu/',
	'description' => 'provides the ability to add user names using ^^^user^^^ syntax',
	'descriptionmsg' => 'cshuserlink-desc',
);

function parseUserSignatures( &$parser, &$text, &$strip_state ) {
	while (preg_match('/\^\^\^(.+?)\^\^\^/', $text, $matches)) {
		$user = getUserInfoFromText($matches[1]);
		if($user != 0){
			$text = str_replace("^^^$matches[1]^^^", "[[:user:" . $user['uid'] . "|" . $user['name'] . "]]", $text);
		}else{
			$text = str_replace("^^^$matches[1]^^^", $matches[1], $text);
		}
	}
	while (preg_match('/\^\^(.+?)\^\^/', $text, $matches)) {
		$user = getUserInfoFromUID($matches[1]);
		if($user != 0){
			$text = str_replace("^^$matches[1]^^", "[[:user:" . $user['uid'] . "|" . $user['name'] . "]]", $text);
		}else{
			$text = str_replace("^^$matches[1]^^", "[[:user:".$matches[1]."]]", $text);
		}
	}
	return true;
}

function getLdapClient() {
	if(!isset($GLOBALS['ldap_ds'])){
		$GLOBALS['ldap_ds'] = ldap_connect('ldaps://ipa11-nrh.csh.rit.edu');
    ldap_bind($GLOBALS['ldap_ds'],'krbprincipalname=wiki/yasuko.csh.rit.edu@CSH.RIT.EDU,cn=services,cn=accounts,dc=csh,dc=rit,dc=edu',$GLOBALS['csh_wiki_ldap_password']);
	}
}

function getUserInfoFromText($name) {
	getLdapClient();
	$sr = ldap_list($GLOBALS['ldap_ds'],'cn=users,cn=accounts,dc=csh,dc=rit,dc=edu',"(displayName=*$name*)",array('uid','cn'));
	$results = ldap_get_entries($GLOBALS['ldap_ds'],$sr);
	if(!isset($results[0])) return 0;
	return array('uid'=>$results[0]['uid'][0],'name'=>$results[0]['cn'][0]);
}
function getUserInfoFromUID($name) {
	getLdapClient();
	$sr = ldap_list($GLOBALS['ldap_ds'],'cn=users,cn=accounts,dc=csh,dc=rit,dc=edu',"(uid=$name)",array('uid','cn'));
	$results = ldap_get_entries($GLOBALS['ldap_ds'],$sr);
	if(!isset($results[0])) return 0;
	return array('uid'=>$results[0]['uid'][0],'name'=>$results[0]['cn'][0]);
}
?>
