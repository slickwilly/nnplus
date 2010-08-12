<?php

require_once("config.php");
require_once(WWW_DIR."/lib/page.php");
require_once(WWW_DIR."/lib/users.php");
require_once(WWW_DIR."/lib/util.php");

$page = new Page();
$users = new Users();

$page->title = "Forgotten Password";
$page->meta_title = "Forgotten Password";
$page->meta_keywords = "forgotten,password,signup,registration";
$page->meta_description = "Forgotten Password";

if ($users->isLoggedIn())
	$page->show404();
	
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action) 
{
	case "reset":
		if (!isset($_REQUEST['guid']))
		{
			$page->smarty->assign('error', "No reset code provided.");
			break;
		}
		
		$ret = $users->getByPassResetGuid($_REQUEST['guid']);
		if (!$ret)
		{
			$page->smarty->assign('error', "Bad reset code provided.");
			break;		
		}
		else
		{
			//
			// reset the password, inform the user, send out the email
			//
			$users->updatePassResetGuid($ret["ID"], "");
			$newpass = substr(md5(uniqid()), 0, 8);
			$users->updatePassword($ret["ID"], $newpass);

			$to = $ret["email"];
			$subject = $page->site->title." Password Reset";
			$contents = "Your password has been reset to ".$newpass;
			sendEmail($to, $subject, $contents, $page->site->email);
			
			$page->smarty->assign('confirmed', "true");
			
			break;			
		}
		
		break;
	case 'submit':
		
		$page->smarty->assign('email', $_POST['email']);
		
		if ($_POST['email'] =="")
		{
			$page->smarty->assign('error', "Missing Email");
		}
		else
		{
			//
			// Check users exists and send an email
			//
			$ret = $users->getByEmail($_POST['email']);
			if (!$ret)
			{
				$page->smarty->assign('error', "The email address is not recognised.");
				break;
			}
			else
			{
				//
				// Generate a forgottenpassword guid, store it in the user table
				//
				$guid = md5(uniqid());
				$users->updatePassResetGuid($ret["ID"], $guid);
				
				//
				// Send the email
				//
				$to = $ret["email"];
				$subject = $page->site->title." Forgotten Password Request";
				$contents = "Someone has requested a password reset for this email address. To reset the password use the following link.\n\n ".$page->serverurl."forgottenpassword.php?action=reset&guid=".$guid;
				sendEmail($to, $subject, $contents, $page->site->email);
				break;
			}
		}
		break;
	
}

$page->content = $page->smarty->fetch('forgottenpassword.tpl');
$page->render();

?>