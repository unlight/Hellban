<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Hellban'] = array(
	'Name' => 'Hellban',
	'Description' => "A ban that lets the user continue viewing posts and making posts, but that makes it so no other message board users can see the banned user's posts.",
	'Version' => '1.0.beta',
	'Date' => '13 Dec 2010',
	'Author' => '',
	'AuthorEmail' => '',
	'AuthorUrl' => '',
	'RequiredApplications' => False,
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'RegisterPermissions' => array('Plugins.Hellbanned.Comments.View', 'Plugins.Hellbanned.Discussions.View'),
	'SettingsPermission' => False,
	'License' => 'X.Net License'
);

// TEST
//Gdn::Config()->Set('Plugins.Hellban.ServerError', '0.5');
/* ========================================================================
CONFIG:
$Configuration['Plugins']['Hellban']['ServerError'] = False;
Every time the user tries to load a page, he is greeted with a fake apache server error message.
False: disabled
0 < FLOAT < 1: Chance to see error

TODO:
Highlight discussion
Highlight comment

TODO (Config):
$Configuration['Plugins']['Hellban']['SlowDownUser'] = False;
$Configuration['Plugins']['Hellban']['ServerError'] = 'X minutes';
$Configuration['Plugins']['Hellban']['CommentsGuestsView'] = False;
$Configuration['Plugins']['Hellban']['DiscussionsGuestsView'] = False;


========================================================================= */


class HellbanPlugin implements Gdn_IPlugin {
	
	protected $Configuration;
	
	public function __construct() {
		$this->Configuration = C('Plugins.Hellban');
	}
	
	public static $StatusCodes = array(
		400 => 'Bad Request',
		408 => 'Request Timeout',
		500 => 'Internal Server Error',
		502 => 'Bad Gateway'
	);
	
	public static $StatusDescription = array(
		400 => 'Your browser (or proxy) sent a request that this server could not understand.',
		408 => "The server closed the network connection because the browser didn't finish the request within the specified time.",
		500 => 'The server encountered an internal error and was unable to complete your request.',
		502 => 'The proxy server received an invalid response from an upstream server.'
	);
	
	protected function RenderError($Code = False) {
		$Database = Gdn::Database();
		if ($Database != Null) $Database->CloseConnection();
		sleep(mt_rand(20, 30));
		if ($Code == False || !array_key_exists($Code, self::$StatusCodes))
			$Code = array_rand(self::$StatusCodes);			
		$this->Code = $Code;
		$this->Status = self::$StatusCodes[$Code];
		$this->Description = self::$StatusDescription[$Code];
		header('HTTP/1.1 ' . $Code . ' ' . self::$StatusCodes[$Code]);
		include dirname(__FILE__).'/views/error.php';
		exit();
	}
	
	public function Base_AfterGetSession_Handler($Sender) {
		$User =& $Sender->EventArguments['User'];
		if (GetValue('Hellbanned', $User)) {
			$HellbanConfiguration =& $this->Configuration;
			$ServerError = GetValue('ServerError', $HellbanConfiguration);
			if (is_numeric($ServerError) && (0 < $ServerError && $ServerError < 1)) {
				$RadndomNumber = mt_rand(1, PHP_INT_MAX);
				$ChanceInt = (int)(1/$ServerError);
				if ($RadndomNumber % $ChanceInt == 0) self::RenderError();
			}/* elseif ($TimeSeconds = strtotime($ServerError, 0)) {
				if ($User->DateLastActive) {
					$DateLastActive = strtotime($User->DateLastActive);
					$bRenderError = (time() - $DateLastActive) > $TimeSeconds;
					if ($bRenderError) self::RenderError();
				}
			}*/
		}
		
	}
	
	
	public function CommentModel_BeforeGetCount_Handler($Sender) {
		$Session = Gdn::Session();
		$Hellbanned = GetValueR('User.Hellbanned', $Session);
		if (!($Hellbanned == 1 || $Session->CheckPermission('Plugins.Hellbanned.Comments.View'))) {
			$Sender->SQL->Where('Hellbanned', 0, False, False);
		}
	}
	
	public function CommentModel_BeforeGet_Handler($Sender) {
		$Sender->SQL->Select('c.Hellbanned');
		
		$Session = Gdn::Session();
		$Hellbanned = GetValueR('User.Hellbanned', $Session);
		if (!($Hellbanned == 1 || $Session->CheckPermission('Plugins.Hellbanned.Comments.View'))) {
			$Sender->SQL->Where('c.Hellbanned', 0, False, False);
		}
	}
	
	public function DiscussionModel_BeforeGetCount_Handler($Sender) {
		// TODO: NO EVENT, WAIT
		//d($Sender->SQL);
	}
	
	public function DiscussionModel_BeforeGet_Handler($Sender) {
		$Session = Gdn::Session();
		$Hellbanned = GetValueR('User.Hellbanned', $Session);
		$Sender->SQL->Select('d.Hellbanned');
		if (!($Hellbanned == 1 || $Session->CheckPermission('Plugins.Hellbanned.Discussions.View'))) {
			$Sender->SQL->Where('d.Hellbanned', 0, False, False);
		}
	}
	
	protected function PutHellbannedToFormValues($Sender, $bInsert) {
		$Session = Gdn::Session();
		if ($Session->IsValid() && $bInsert) {
			if (GetValue('Hellbanned', $Session->User)) {
				$FormPostValues =& $Sender->EventArguments['FormPostValues'];
				$FormPostValues['Hellbanned'] = 1;
			}
		}
	}
	
	public function Base_BeforeSaveDiscussion_Handler($Sender) {
		$Session = Gdn::Session();
		$bInsert = $Sender->EventArguments['Insert'];
		$this->PutHellbannedToFormValues($Sender, $bInsert);
	}
	
	public function Base_BeforeSaveComment_Handler($Sender) {
		$bInsert = (GetValue('CommentID', $Sender->EventArguments) === False);
		$this->PutHellbannedToFormValues($Sender, $bInsert);
	}
	
	public function ProfileController_AfterAddSideMenu_Handler($Profile) {
		$Session = Gdn::Session();
		$HasPermission = $Session->CheckPermission('Garden.Users.Edit');
		if ($HasPermission) {
			$SideMenu =& $Profile->EventArguments['SideMenu'];
			$Url = '/profile/hellban/'.$Profile->User->UserID;
			$HellbanText = ($Profile->User->Hellbanned) ? T('UnHellban %1$s') : T('Hellban %1$s!');
			$SideMenu->AddLink('Options', sprintf($HellbanText, ($Profile->User->Gender == 'f') ? 'her' : 'him'), $Url, 'Garden.Users.Edit', array('class' => 'HellbanButton'));
		}
	}
	
	public function ProfileController_Hellban_Create($Sender, $Args = False) {
		$Sender->Permission('Garden.Users.Edit');
		$UserID = ArrayValue(0, $Args);
		$UserModel = Gdn::UserModel();
		$User = $UserModel->Get($UserID);
		if ($User == False) throw NotFoundException('User');
		$UserModel->SetProperty($User->UserID, 'Hellbanned');
		$Sender->StatusMessage = 'Hellbanned';
		$Sender->RedirectUrl = Url('/profile/'.$User->UserID.'/'.Gdn_Format::Url($User->Name));
		if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) Redirect($Sender->RedirectUrl);
	}
	
	public function Structure() {
		
		Gdn::Structure()
			->Table('User')
			->Column('Hellbanned', 'tinyint', '0')
			->Set();
		
		Gdn::Structure()
			->Table('Comment')
			->Column('Hellbanned', 'tinyint', '0')
			->Set();
		
		Gdn::Structure()
			->Table('Discussion')
			->Column('Hellbanned', 'tinyint', '0')
			->Set();
	}
	
	public function Setup() {
		$this->Structure();
	}
}
//$H = new HellbanPlugin(); $H->Setup(); d('HellbanPlugin setup OK'); // DEBUG