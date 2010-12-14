<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Hellban'] = array(
	'Name' => 'Hellban',
	'Description' => "A ban that lets the user continue viewing posts and making posts, but that makes it so no other message board users can see the banned user's posts.",
	'Version' => '1.0',
	'Date' => '13 Dec 2010',
	'Author' => '',
	'AuthorEmail' => '',
	'AuthorUrl' => '',
	'RequiredApplications' => False,
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'RegisterPermissions' => False,
	'SettingsPermission' => False,
	'License' => 'X.Net License'
);

//register_shutdown_function(function(){$H = new HellbanPlugin(); $H->Setup(); d('HellbanPlugin setup OK');});

class HellbanPlugin implements Gdn_IPlugin {
	
	
	public function __construct() {
		$this->Setup();
	}
	
	public function Structure() {
		Gdn::Structure()
			->Table('Comment')
			->Column('Hellbanned', 'tinyint', '0')
			->Set();
	}
	
	public function Setup() {
	}
}