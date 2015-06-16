<?php

class Email {

	private $Project;
	private $Bug;
	private $Comment;
	private $member;
	private $site_name;

	public function __construct($Project = false, $Bug = false, $Comment = false, $Attachment = false) {
		global $DB;
		
		$this->Project = $Project;
		$this->Bug = $Bug;
		$this->Comment = $Comment;
		$this->Attachment = $Attachment;
		
		//get site name
		$sql = "SELECT site_label FROM exp_sites";
		$query = $DB->query($sql);
		if ($query->num_rows > 1) {
			$this->site_name = 'Earwig Bug Tracker';
		}
		else {
			$this->site_name = $query->result[0]['site_label'];
		}
	}
	
	private function check_settings() {
		//check to see if email alerts are enabled
		if ($this->Project->projectEmailEnabled() == false) {
			return false;
		}
		return true;
	}
	
	public function bug_assigned() {
		global $DB, $FNS, $SESS;
		
		if ($this->check_settings() == false) {
			return false;
		}
		
		//get member
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Bug->bugAssignee()."'";
		$query = $DB->query($sql);
		if ($query->num_rows == 0) {
			return false;
		}
		$member = $query->result[0];
		
		//check for user messaging
		if ($member['accept_messages'] == 'n' || $member['accept_admin_email'] == 'n') {
			return false;
		}
		
		//get current member id
		$member_id = $SESS->userdata('member_id');
		
		if ($member_id != $member['member_id']) {
			//send email
			$to = $member['email'];
			$subject = '['.$this->Project->projectTitle().'] You have been assigned a bug named "'.$this->Bug->bugTitle().'"';
			$body = "Hi ".$member['screen_name'].", \r\n\r\n";
			$body .= "You have been assigned a new bug entitled \"".$this->Bug->bugTitle()."\" from the project \"".$this->Project->projectTitle()."\".\r\n\r\n";
			$link = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'bug_view_remote').'&id='.$this->Bug->id();
			$body .= "Click here to go to this bug - ".$link."\r\n\r\n";
			$body .= "Regards,\r\n";
			$body .= $this->site_name;
			$this->send_email($to, $subject, $body);
		}
		
		//return
		return true;
	}
	
	public function comment_added() {
		global $DB, $FNS, $SESS;
		
		if ($this->check_settings() == false) {
			return false;
		}
		
		//get members
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Bug->bugAssignee()."'
				OR member_id = '".$this->Bug->bugCreator()."'";
		$query = $DB->query($sql);
		if ($query->num_rows == 0) {
			return false;
		}
		$members = $query->result;
		
		//get current member id
		$member_id = $SESS->userdata('member_id');
		
		//find comment owner
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Comment->commentOwner()."'";
		$query = $DB->query($sql);
		$commentowner = $query->result[0];
		
		//send email
		foreach ($members as $member) {
			if ($member['member_id'] != $member_id && $member['accept_messages'] == 'y' && $member['accept_admin_email'] == 'y') {
				$to = $member['email'];
				$subject = '['.$this->Project->projectTitle().'] A new comment has been added to "'.$this->Bug->bugTitle().'"';
				$body = "Hi ".$member['screen_name'].", \r\n\r\n";
				$body .= "A new comment has been added to \"".$this->Bug->bugTitle()."\" from the project \"".$this->Project->projectTitle()."\": \r\n\r\n";
				$body .= '"'.$this->Comment->commentValue()."\r\n\r\n";
				$body .= '" - '.$commentowner['screen_name'].' '.$this->Comment->get_comment_date()."\r\n\r\n";
				$link = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'bug_view_remote').'&id='.$this->Bug->id();
				$body .= "Click here to review to this issue - ".$link."\r\n\r\n";
				$body .= "Regards,\r\n";
				$body .= $this->site_name;
				$this->send_email($to, $subject, $body);
			}
		}
		
		//return
		return true;
	}
	
	public function bug_edited() {
		global $DB, $FNS, $SESS;
		
		if ($this->check_settings() == false) {
			return false;
		}
		
		//get members
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Bug->bugAssignee()."'
				OR member_id = '".$this->Bug->bugCreator()."'";
		$query = $DB->query($sql);
		if ($query->num_rows == 0) {
			return false;
		}
		$members = $query->result;
		
		//get current member id
		$member_id = $SESS->userdata('member_id');
		
		//send email
		foreach ($members as $member) {
			if ($member['member_id'] != $member_id && $member['accept_messages'] == 'y' && $member['accept_admin_email'] == 'y') {
				$to = $member['email'];
				$subject = '['.$this->Project->projectTitle().'] The issue "'.$this->Bug->bugTitle().'" has been edited';
				$body = "Hi ".$member['screen_name'].", \r\n\r\n";
				$body .= "The issue \"".$this->Bug->bugTitle()."\" from the project \"".$this->Project->projectTitle()."\" has been edited.\r\n\r\n";
				$link = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'bug_view_remote').'&id='.$this->Bug->id();
				$body .= "Click here to go to this issue - ".$link."\r\n\r\n";
				$body .= "Regards,\r\n";
				$body .= $this->site_name;
				$this->send_email($to, $subject, $body);
			}
		}
		
		//return
		return true;
	}
	
	public function attachment_added() {
		global $DB, $FNS, $SESS;
		
		if ($this->check_settings() == false) {
			return false;
		}
		
		//get members
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Bug->bugAssignee()."'
				OR member_id = '".$this->Bug->bugCreator()."'";
		$query = $DB->query($sql);
		if ($query->num_rows == 0) {
			return false;
		}
		$members = $query->result;
		
		//get current member id
		$member_id = $SESS->userdata('member_id');
		
		//find attachment owner
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$this->Attachment->attachmentOwner()."'";
		$query = $DB->query($sql);
		$attachmentowner = $query->result[0];
		
		//send email
		foreach ($members as $member) {
			if ($member['member_id'] != $member_id && $member['accept_messages'] == 'y' && $member['accept_admin_email'] == 'y') {
				$to = $member['email'];
				$subject = '['.$this->Project->projectTitle().'] A new attachment has been added to "'.$this->Bug->bugTitle().'"';
				$body = "Hi ".$member['screen_name'].", \r\n\r\n";
				$body .= "A new attachment has been added to the issue \"".$this->Bug->bugTitle()."\" from the project \"".$this->Project->projectTitle()."\": \r\n\r\n";
				$body .= $this->Attachment->attachmentName().' - '.$attachmentowner['screen_name'].' '.$this->Attachment->get_attachment_date()."\r\n\r\n";
				$link = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'bug_view_remote').'&id='.$this->Bug->id();
				$body .= "Click here to go to this issue - ".$link."\r\n\r\n";
				$body .= "Regards,\r\n";
				$body .= $this->site_name;
				$this->send_email($to, $subject, $body);
			}
		}
		
		//return
		return true;
	}
	
	public function assigned_to_project($member_id) {
		global $DB, $FNS, $SESS;
		
		if ($this->check_settings() == false) {
			return false;
		}
		
		//get member
		$sql = "SELECT * FROM exp_members
				WHERE member_id = '".$member_id."'";
		$query = $DB->query($sql);
		if ($query->num_rows == 0) {
			return false;
		}
		$member = $query->result[0];
		
		//check for user messaging
		if ($member['accept_messages'] == 'n' || $member['accept_admin_email'] == 'n') {
			return false;
		}
		
		//get current member id
		$current_member_id = $SESS->userdata('member_id');
		
		if ($current_member_id != $member['member_id']) {
			//send email
			$to = $member['email'];
			$subject = '['.$this->Project->projectTitle().'] You have been assigned to the project entitled "'.$this->Project->projectTitle().'"';
			$body = "Hi ".$member['screen_name'].", \r\n\r\n";
			$body .= "You have been assigned to the project entitled \"".$this->Project->projectTitle()."\".\r\n\r\n";
			$link = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'project_view_remote').'&id='.$this->Project->id();
			$body .= "Click here to go to this project - ".$link."\r\n\r\n";
			$body .= "Regards,\r\n";
			$body .= $this->site_name;
			$this->send_email($to, $subject, $body);
		}
		
		//return
		return true;
	}
	
	private function send_email($to, $subject, $body) {
		global $PREFS, $REGX;

		if (!class_exists('EEmail')) {
			require PATH_CORE.'core.email'.EXT;
		}		
		$email = new EEmail();
			
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography(0);

		$messagebody = $TYPE->parse_type(stripslashes($REGX->xss_clean($body)),
											   			array('text_format'   => 'none',
											   		 		  'html_format'   => 'none',
															  'auto_links'    => 'n',
											   		 		  'allow_img_url' => 'n'
											   		 	)
											   		 );

		$email->initialize();
		$email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));
		$email->to($to);
		$email->subject($subject);
		$email->message($REGX->entities_to_ascii($body));
		$email->Send();
	}

	
}

?>