<?

class Bug extends Base {

	//Base/Factory specific

	protected $classname = "Bug";
	protected $table = "exp_earwig_bugs";
	protected $pk = "bugID";

	public function get_bug_assignee($member_id = false) {
		global $LANG, $PREFS, $SESS;

		$current_member_id = $SESS->userdata('member_id');
	
		if ($this->bugAssignee() == 0) {
			return $LANG->line('none');
		}
		
		if ($member_id == false) {
			$member_id = $this->bugAssignee();
		}

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($member_id)."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			if ($PREFS->ini('enable_avatars') == 'y' && $query->result[0]['avatar_filename'] != '' && $query->result[0]['display_avatars'] == 'y') {
				$s = '<span class="avatarLink"><a href="mailto:'.$query->result[0]['email'].'"><img src="'.$PREFS->ini('avatar_url').'/'.$query->result[0]['avatar_filename'].'">'.$query->result[0]['screen_name'].'</a></span>';
			}
			else {	
				$s = '<a href="mailto:'.$query->result[0]['email'].'">'.$query->result[0]['screen_name'].'</a>';
			}
		}
		else {
			$s = $LANG->line('unknown');
		}
		
		return $s;
	}
	
	public function get_bug_assignee_link($member_id = false) {
		global $LANG, $PREFS, $SESS;

		$current_member_id = $SESS->userdata('member_id');
	
		if ($this->bugAssignee() == 0) {
			return $LANG->line('none');
		}
		
		if ($member_id == false) {
			$member_id = $this->bugAssignee();
		}

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($member_id)."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			if ($PREFS->ini('enable_avatars') == 'y' && $query->result[0]['avatar_filename'] != '' && $query->result[0]['display_avatars'] == 'y') {
				$s = '<div class="assigneeHolder"><span class="avatarLink"><a href="mailto:'.$query->result[0]['email'].'"><img src="'.$PREFS->ini('avatar_url').'/'.$query->result[0]['avatar_filename'].'">'.$query->result[0]['screen_name'].'</a></span>';
			}
			else {	
				$s = '<div class="assigneeHolder"><a href="mailto:'.$query->result[0]['email'].'">'.$query->result[0]['screen_name'].'</a>';
			}
		}
		else {
			$s = '<div class="assigneeHolder">'.$LANG->line('unknown');
		}
		
		//get project
		$Projects = new Projects();
		$data['projectID'] = $this->projectID();
		$Project = $Projects->find($data, 1);
	
		if ($this->bugCreator() == $current_member_id || $Project->projectLeader() == $current_member_id || $SESS->userdata('group_id') == '1') {
			$s .= ' - <a href="#" class="assignBug" id="'.$this->id().'">'.$LANG->line('edit').'</a>';
		}
		
		$s .= '</div>';
		
		return $s;
	}

	public function get_bug_creator() {
		global $LANG, $PREFS;

		if ($this->bugCreator() == 0) {
			return $LANG->line('unknown');
		}

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->bugCreator())."'";
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

	public function get_project_title() {
		global $LANG, $DSP;

		$sql = "SELECT projectColour, projectTitle FROM exp_earwig_projects
				WHERE projectID = '".$this->db->escape_str($this->projectID())."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=project_view&id='.$this->projectID(), '<span class="projectTitle" style="background-color: '.$query->result[0]['projectColour'].'">'.$query->result[0]['projectTitle'].'</span>');
		}
		return $LANG->line('none');
	}

	public function get_bug_status($status = false) {
		if ($status == false) {
			$status = $this->bugStatus();
		}

		if ($status == 'open') {
			$link = '<a href="#" class="swapStatus" id="'.$this->id().'">[+]</a>';
			return '<span style="color: rgb(153, 0, 0);">'.ucfirst($status).'</span>';
		}
		elseif ($status == 'resolved') {
			$link = '<a href="#" class="swapStatus" id="'.$this->id().'">[-]</a>';
			return '<span style="color: rgb(0, 153, 51);">'.ucfirst($status).'</span>';
		}
		elseif ($status == 'closed') {
			return ucfirst($status);
		}
	}

	public function get_bug_status_no_link($status = false) {
		if ($status == false) {
			$status = $this->bugStatus();
		}
		
		if ($status == 'open') {
			return '<span style="color: rgb(153, 0, 0);">'.ucfirst($status).'</span> ';
		}
		elseif ($status == 'resolved') {
			return '<span style="color: rgb(0, 153, 51);">'.ucfirst($status).'</span> ';
		}
		elseif ($status == 'closed') {
			return ucfirst($status);
		}
	}

	public function get_comment_link() {
		global $DSP, $LANG;

		$sql = "SELECT commentID FROM exp_earwig_comments
				WHERE bugID = '".$this->db->escape_str($this->id())."'";
		$query = $this->db->query($sql);

		return $DSP->qdiv('defaultBold', '('.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view&id='.$this->id().'#timeline', (string) $query->num_rows).') - '.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view&id='.$this->id().'#addcomment',$LANG->line('add')));
	}
	
	public function assign_link() {
		global $LANG, $SESS;	
	
		$member_id = $SESS->userdata('member_id');
		
		//get project
		$Projects = new Projects();
		$data['projectID'] = $this->projectID();
		$Project = $Projects->find($data, 1);
	
		if ($this->bugCreator() == $member_id || $Project->projectLeader() == $member_id || $SESS->userdata('group_id') == '1') {
			return '<div class="defaultBold assignHolder"><a href="#" class="assignBug" id="'.$this->id().'">'.$LANG->line('assign').'</a></div>';
		}
	}
	
	public function get_bug_date() {
		global $PREFS;

		$time = strtotime($this->bugDate());
		if ($PREFS->ini('time_format') == 'us') {
			return date('m/d/Y h:i a', $time);
		}
		elseif ($PREFS->ini('time_format') == 'eu') {
			return date('H:i d/m/Y', $time);
		}
	}

	public function get_bug_fixed_date() {
		global $PREFS, $LANG;
		
		if (is_array($this->bugFixedDate()) || $this->bugFixedDate() == null || $this->bugFixedDate() == '0000-00-00 00:00:00') {
			return $LANG->line('na');
		}
		
		$time = strtotime($this->bugFixedDate());
		if ($PREFS->ini('time_format') == 'us') {
			return date('m/d/Y h:i a', $time);
		}
		elseif ($PREFS->ini('time_format') == 'eu') {
			return date('H:i d/m/Y', $time);
		}
	}
	
	public function get_bug_expected() {
		//instantiate typography class
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography;

		return Util::parse_for_bugs($TYPE->parse_type(nl2br($this->bugExpected()), array('text_format'=>'xhtml', 'html_format'=>'safe', 'auto_links'=>'y', 'allow_img_url'=>'n')));
	}

	public function get_bug_actually() {
		//instantiate typography class
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography;

		return Util::parse_for_bugs($TYPE->parse_type(nl2br($this->bugActually()), array('text_format'=>'xhtml', 'html_format'=>'safe', 'auto_links'=>'y', 'allow_img_url'=>'n')));
	}

	public function get_bug_doing() {
		//instantiate typography class
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography;

		return Util::parse_for_bugs($TYPE->parse_type(nl2br($this->bugDoing()), array('text_format'=>'xhtml', 'html_format'=>'safe', 'auto_links'=>'y', 'allow_img_url'=>'n')));
	}

	public function view($msg = false, $response = false) {
		global $DSP, $LANG;

		parent::view($msg, $response);

		//get attachments
		$Attachments = new Attachments();
		$data['bugID'] = $this->id();
		$BugAttachments = $Attachments->find($data);

		//get timelines
		$Timelines = new Timelines();
		
		//list timelines
		$DSP->body .= '<div class="section">';
		$DSP->body .= '<a id="timeline"></a>';
		
		//get count
		$sql = "SELECT * FROM exp_earwig_timelines
				WHERE bugID = '".$this->id()."'";
		$query = $this->db->query($sql);				
		if ($query->num_rows > 5) {				
			$DSP->body .= '<h1 style="float: left;">'.$LANG->line($Timelines->noun.'_plural').'</h1> <a style="float: right;" href="#" class="getMoreTimelines" id="'.$this->id().'">'.$LANG->line('more')."</a><br/><br/>";
		}
		else {
			$DSP->body .= $DSP->heading($LANG->line($Timelines->noun.'_plural'));
		}
				
		$Timelines->list_objects(false, false, false, $this->id(), false);
		$DSP->body .= '</div>';
		
		//form
		$DSP->body .= '<div class="section">';
		$DSP->body .= '<a id="addupload"></a>';
		$DSP->body .= $DSP->heading($LANG->line($Timelines->noun.'_add'));
		$DSP->body .= $Timelines->create_form(false, false, $this->id(), false, true);
		$DSP->body .= '</div>';
	}

	public function get_bug_tag_links() {
		global $LANG;

		$sql = "SELECT tagID, tagValue FROM exp_earwig_tags
				WHERE bugID = ".$this->id();
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$tags = array();
			foreach ($query->result as $tag) {
				$tags[] = '<a href="'.BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=tag_view'.AMP.'id='.$tag['tagID'].'">'.$tag['tagValue'].'</a>';
			}
			return implode(', ', $tags);
		}
		return $LANG->line('none');
	}

	public function bugTags() {
		$sql = "SELECT tagValue FROM exp_earwig_tags
				WHERE bugID = ".$this->id();
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$tags = array();
			foreach ($query->result as $tag) {
				$tags[] = $tag['tagValue'];
			}
			return implode(', ', $tags);
		}
		return '';
	}

	public function update($data, $view = true) {
		global $LANG;

		//bug assignee changed
		if (isset($data['bugAssignee']) && $this->bugAssignee() != $data['bugAssignee']) {
			$change_assignee = true;
		}
		
		//bug status changed
		if (isset($data['bugStatus']) && $this->bugStatus() != $data['bugStatus']) {
			$change_status = true;
		}
		
		//get tags
		if (isset($data['bugTags'])) {
			$tags = $data['bugTags'];
			unset($data['bugTags']);
			$tags = Util::tagparse($tags);
	
			//delete previous tags
			$sql = "DELETE FROM exp_earwig_tags
					WHERE bugID = '".$this->id()."'";
			$this->db->query($sql);
	
			//create tags
			if (is_array($tags)) {
				$Tags = new Tags();
				foreach ($tags as $tag) {
					$tagdata['tagValue'] = $tag;
					$tagdata['bugID'] = $this->id();
	
					//insert
					$columns = array();
					$values = array();
					foreach ($tagdata as $key=>$value) {
						$columns[] = $key;
						$values[] = "'".$value."'";
					}
					$sql = "INSERT INTO exp_earwig_tags(".implode(",", $columns).")
							VALUES(".implode(",", $values).")";
					$this->db->query($sql);
				}
			}
		}

		if (isset($data['bugStatus'])) {
			//bugFixedDate
			if ($data['bugStatus'] == 'resolved') {
				$data['bugFixedDate'] = date('Y-m-d H:i:s');
			}
			elseif ($data['bugStatus'] == 'open') {
				$data['bugFixedDate'] = NULL;
			}
		}
		
		//get project
		$Projects = new Projects();
		$projectfinddata['projectID'] = $this->projectID();
		$Project = $Projects->find($projectfinddata, $limit=1);
		
		//attempt to e-mail assignee and creator
		if ($this->bugAssignee() != 0 && $this->bugCreator() != '') {
			$Email = new Email($Project, $this, false, false);
			$Email->bug_edited();			
		}
		
		$Timelines = new Timelines();
		
		if (!isset($change_status)) {
			$Timelines->create('bug_edit', $LANG->line('timeline_bug_edited'), $this);
		}
		
		//add changed assignee to timeline if required
		if (isset($change_assignee) && $change_assignee == true) {
			$Timelines->create('user_add',  $LANG->line('timeline_bug_assigned').' '.$this->get_bug_assignee($data['bugAssignee']), $this);
		}
		
		//bug status has changed
		if (isset($change_status) && $change_status == true) {
			$Timelines->create('tag_blue_edit', $LANG->line('timeline_status_changed').' '.$this->get_bug_status_no_link($data['bugStatus']), $this);
		}
		
		//attempt to update bug
		parent::update($data, false, $view);
	}

	public function delete() {
		//delete tags
		$sql = "DELETE FROM exp_earwig_tags
				WHERE bugID = '".$this->id()."'";
		$this->db->query($sql);

		//delete bug
		parent::delete();
	}
	
	public function edit_form() {
		global $DSP;

		$Bugs = new Bugs();
		//bug status field
		$this->form_fields['bugStatus'] = $Bugs->get_bugstatus_select();
		//project assignment field
		$this->form_fields['projectID'] = $Bugs->get_project_select();
		//assignee field
		$this->form_fields['bugAssignee'] = $Bugs->get_assignee_select();
		//bug severity
		$this->form_fields['bugSeverity'] = $Bugs->get_bug_severity_select();
		
		//remove bug status
		unset($this->form_fields['bugStatus']);
		
		$DSP->extra_header .= '<style type="text/css">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/css/autocomplete.css');
       	$DSP->extra_header .= '</style>';
		$DSP->extra_header .= '<script type="text/javascript">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/plugin.autocomplete.js');
		$DSP->extra_header .= '</script>';	
		
		parent::edit_form();
	}
	
	public function get_bug_title() {
		global $DSP;
		
		//check for attachment
		$sql = "SELECT * FROM exp_earwig_attachments
				WHERE bugID = '".$this->id()."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) { 
			return $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view'.AMP.'id='.$this->id().'#attachments', '<img src="'.PATH_MOD_IMAGES.'/attach.png" class="attachmentIcon">').$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view'.AMP.'id='.$this->id(), $this->bugTitle());
		}		
		return $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view'.AMP.'id='.$this->id(), $this->bugTitle());
	}
	
	public function get_edit_link() {
		global $LANG;

		return $LANG->line('bug_edit_verb');
	}
	
	public function get_bug_severity() {
		global $LANG;
		
		$out = '';
		switch ($this->bugSeverity()) {
			case 'critical':
				$out .= '<span style="color: #aa1100;">';
				break;
			case 'high':
				$out .= '<span style="color: #ff3300;">';
				break;
			case 'normal':
				$out .= '<span style="color: #ff6600;">';
				break;
			case 'low':
				$out .= '<span style="color: #3399ff;">';
				break;
			case 'trivial':
				$out .= '<span style="color: #44dd00;">';
				break;
		}
		
		$out .= $LANG->line($this->bugSeverity());
		$out .= '</span>';

		return $out;
	}
	
	public function get_bug_url() {
		$url = $this->bugURL();
		if ($url != '') {
			return '<a href="'.$url.'" class="external">'.$url.'</a>';
		}
		return '';
	}

}

?>