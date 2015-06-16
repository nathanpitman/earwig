<?

class Timeline extends Base {

	//Base/Factory specific

	protected $classname = "Timeline";
	protected $table = "exp_earwig_timelines";
	protected $pk = "timelineID";
	
	public function get_timeline_date() {
		global $PREFS;

		$time = strtotime($this->timelineDate());
		if ($PREFS->ini('time_format') == 'us') {
			return date('m/d/Y h:i a', $time);
		}
		elseif ($PREFS->ini('time_format') == 'eu') {
			return date('H:i d/m/Y', $time);
		}
	}
	
	public function get_timeline_member() {
		global $LANG, $PREFS;

		if ($this->timelineMember() == 0) {
			return $LANG->line('unknown');
		}

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->timelineMember())."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			if ($PREFS->ini('enable_avatars') == 'y' && $query->result[0]['avatar_filename'] != '' && $query->result[0]['display_avatars'] == 'y') {
				return '<span class="avatarLink"><a href="mailto:'.$query->result[0]['email'].'"><img src="'.$PREFS->ini('avatar_url').'/'.$query->result[0]['avatar_filename'].'">'.$query->result[0]['screen_name'].'</a></span>';
			}
			else {	
				return '<a href="mailto:'.$query->result[0]['email'].'">'.$query->result[0]['screen_name'].'</a>';
			}
		}
		return $LANG->line('unknown');
	}
	
	public function get_timeline_details() {
		$out = $this->timelineDetails();
		
		//comment
		if ($this->commentID() != NULL && !is_array($this->commentID())) {
			$Comments = new Comments();
			$finddata['commentID'] = $this->commentID();
			$Comment = $Comments->find($finddata, 1);
			
			if (is_object($Comment)) {
				$out .= '<br/>';
				$out .= '<span class="timeline_comment">'.$Comment->view().'</span>';
			}
		}
		
		//attachment
		if ($this->attachmentID() != NULL && !is_array($this->attachmentID())) {
			$Attachments = new Attachments();
			$finddata['attachmentID'] = $this->attachmentID();
			$Attachment = $Attachments->find($finddata, 1);
			
			if (is_object($Attachment)) {
				$out .= '<br/>';
				$out .= '<span class="timeline_attachment">'.$Attachment->view().'</span>';
			}
		}

		return $out;
	}
		
}

?>