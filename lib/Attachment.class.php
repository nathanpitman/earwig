<?

class Attachment extends Base {

	//Base/Factory specific

	protected $classname = "Attachment";
	protected $table = "exp_earwig_attachments";
	protected $pk = "attachmentID";
	
	public function get_attachment_date() {
		global $PREFS;

		$time = strtotime($this->attachmentDate());
		if ($PREFS->ini('time_format') == 'us') {
			return date('m/d/Y h:i a', $time);
		}
		elseif ($PREFS->ini('time_format') == 'eu') {
			return date('H:i d/m/Y', $time);
		}
	}
	
	public function get_attachment_owner() {
		global $LANG, $PREFS;

		if ($this->attachmentOwner() == 0) {
			return $LANG->line('unknown');
		}

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->attachmentOwner())."'";
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
	
	public function get_attachment_link() {
		global $DSP;
		
		return $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=attachment_view&id='.$this->id(), $this->attachmentName()));
	}
	
	public function download() {
		Util::get_file($this->attachmentLocation(), $this->attachmentMimeType());
	}
	
	public function view() {
		return $this->get_attachment_link();
	}


}

?>