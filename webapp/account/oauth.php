<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: /u/login.php"); }

// set up
chdir("..");
require_once('config.webapp.inc.php');
ini_set("include_path", ini_get("include_path").":".$INCLUDE_PATH);
require_once("init.php");

$cfg = new Config();

$request_token = $_GET['oauth_token'];
$request_token_secret = $_SESSION['oauth_request_token_secret'];
/*
echo "DEBUG:"
echo "URL Request Token: ".$request_token."<br />";
echo "Session Request Token: ".$request_token_secret."<br />";
*/
$to = new TwitterOAuth($cfg->oauth_consumer_key, $cfg->oauth_consumer_secret, $request_token, $request_token_secret);
$tok = $to->getAccessToken();

if ( isset( $tok['oauth_token'] ) && isset($tok['oauth_token_secret']) ) {
	$api = new TwitterAPIAccessorOAuth($tok['oauth_token'], $tok['oauth_token_secret'], $cfg);
	$u = $api->verifyCredentials();

//	echo "User ID: ". $u['user_id'];
//	echo "User name: ". $u['user_name'];
	$twitter_id = $u['user_id'];
	$tu = $u['user_name']; 
	
	$db = new Database();
	$conn = $db->getConnection();
	$od = new OwnerDAO();

	$owner = $od->getByEmail($_SESSION['user']);

	if ( $twitter_id > 0 ) {
		echo "Twitter authentication successful.<br />";

		$id = new InstanceDAO();
		$i = $id->getByUsername($tu);
		$oid = new OwnerInstanceDAO();

		if ( isset($i) ) {
			echo "Instance already exists.<br />";

			$oi = $oid -> get($owner->id, $i->id);
			if ( $oi != null ) {
				echo "Owner already has this instance, no insert or update.<br />";
			} else {
				$oid->insert($owner->id, $i->id, $tok['oauth_token'], $tok['oauth_token_secret']);
				echo "Added owner instance.<br />";
			}

		} else {
			echo "Instance does not exist.<br />";

			$id->insert($twitter_id, $tu);
			echo "Created instance.<br />";

			$i = $id->getByUsername($tu);
			$oid->insert($owner->id, $i->id, $tok['oauth_token'], $tok['oauth_token_secret']);
			echo "Created an owner_instance.<br />";
		}
		# clean up
		$db->closeConnection($conn);	
	}
}



echo '<br /> <a href="'.$TWITALYTIC_CFG['site_root_path'].'account/">Back to your account</a>.';


?>