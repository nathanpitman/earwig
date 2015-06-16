<?

class Project extends Base {

	//Base/Factory specific

	protected $classname = "Project";
	protected $table = "exp_earwig_projects";
	protected $pk = "projectID";

	public function get_total_users() {
		$sql = "SELECT * FROM exp_earwig_projects_members
				WHERE projectID = '".$this->db->escape_str($this->id())."'";
		$query = $this->db->query($sql);
		return (string)$query->num_rows;
	}

	public function get_project_leader() {
		global $LANG, $PREFS;

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->projectLeader())."'";
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
	
	public function get_default_assignee() {
		global $LANG, $PREFS;

		$sql = "SELECT screen_name, email, avatar_filename, display_avatars FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->projectDefaultAssignee())."'";
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

	public function get_project_status() {
		if ($this->projectStatus() == 'inactive') {
			return '<span style="color: rgb(153, 0, 0);">Archived</span> ';
		}
		else {
			return '<span style="color: rgb(0, 153, 51);">'.ucfirst($this->projectStatus()).'</span> ';
		}
	}

	public function get_number_of_bugs() {
		$sql = "SELECT bugID FROM exp_earwig_bugs
				WHERE projectID = '".$this->db->escape_str($this->id())."'";
		$query = $this->db->query($sql);
		return (string) $query->num_rows;
	}

	public function get_percentage_of_fixed_bugs() {
		//get current number of bugs
		$current_bugs = (int) $this->get_number_of_bugs();

		//get fixed bugs
		$sql = "SELECT DISTINCT bugID FROM exp_earwig_bugs
				WHERE (bugStatus = 'resolved'
				OR bugStatus = 'closed')
				AND projectID = '".$this->db->escape_str($this->id())."'";
		$query = $this->db->query($sql);
		$fixed_bugs = $query->num_rows;

		//return percentage
		if ($current_bugs > 0) {
			$percentage = round(($fixed_bugs / $current_bugs) * 100);
		}
		else {
			$percentage = 0;
		}
		return $percentage.'%';
	}

	public function get_project_colour() {
		return '<span style="color: '.$this->projectColour().'">'.$this->projectColour().'</span>';
	}

	public function get_project_title() {
		return '<span class="projectTitle" style="background-color: '.$this->projectColour().'">'.$this->projectTitle().'</span>';
	}

	public function edit_form() {
		global $DSP;

		//get owner select
		$Projects = new Projects();
		$this->form_fields['projectStatus'] = $Projects->get_status_select();
		$this->form_fields['projectLeader'] = $Projects->get_leader_select();
		$this->form_fields['projectUploadID'] = $Projects->get_upload_id_select();
		$this->form_fields['projectBugForm'] = $Projects->get_bug_form_select();
		$this->form_fields['projectDefaultAssignee'] = $Projects->get_leader_select();

		//colour selector js
		$DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/colorpicker.js');
       	$DSP->extra_header .= '</script>';
       	$DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/colourselector.js');
       	$DSP->extra_header .= '</script>';
       	$DSP->extra_header .= '<style type="text/css">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/css/colorpicker.css');
       	$DSP->extra_header .= '</style>';

       	parent::edit_form();
	}

	public function get_project_description() {
		//instantiate typography class
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography;

		return $TYPE->parse_type(nl2br($this->projectDescription()), array('text_format'=>'xhtml', 'html_format'=>'safe', 'auto_links'=>'y', 'allow_img_url'=>'n'));
	}

	public function get_upload_location_name() {
		$sql = "SELECT name FROM exp_upload_prefs
				WHERE id = '".$this->projectUploadID()."'";
		$query = $this->db->query($sql);
		return $query->result[0]['name'];
	}

	public function get_upload_details() {
		$sql = "SELECT server_path, allowed_types, max_size FROM exp_upload_prefs
				WHERE id = '".$this->projectUploadID()."'";
		$query = $this->db->query($sql);
		return $query->result[0];
	}
	
	public function get_project_email_enabled() {
		if ($this->projectEmailEnabled() == true) {
			return 'Yes';
		}
		else {
			return 'No';
		}
	}
	
	public function update($data) {
		if (!isset($data['projectEmailEnabled'])) {
			$data['projectEmailEnabled'] = false;
		}
		
		return parent::update($data);
	}
	
	public function get_project_bug_form() { 
		return ucfirst($this->projectBugForm());
	}
	
	public function assign_members($data) {
		global $DB;

		//get new members
		$new_members = array();
		foreach ($data['members'] as $member) {
			$sql = "SELECT * FROM exp_earwig_projects_members
					WHERE projectID = '".$this->id()."'
					AND member_id = '".$member."'";
			$query = $DB->query($sql);
			if ($query->num_rows == 0) {
				$new_members[] = $member;
			}
		}
		
		if (parent::assign_members($data)) {
			$Email = new Email($this, false, false, false);
			foreach ($new_members as $member) {
				if ($this->projectLeader() != $member) {
					$Email->assigned_to_project($member);
				}
			}
		}
		
		return true;
	}
	
	public function view($msg = false, $response = false) {
		global $DSP, $LANG;

		parent::view($msg, $response);
		
		$Chart = new Chart($this);

		//show project chart		
		$DSP->body .= '<div class="section" id="chart">';
		$DSP->body .= $DSP->heading($LANG->line('chart_view'));
		$DSP->body .= '<br/>';
		$Chart->switcher();
		$DSP->body .= '<br/>';
		$DSP->body .= '<br/>';
		$Chart->view();
		$DSP->body .= '</div>';
	}
	
}

?>