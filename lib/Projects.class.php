<?

class Projects extends Factory {

	//Base/Factory specific

	protected $classname = "Project";
	protected $table = "exp_earwig_projects";
	protected $pk = "projectID";

	//ExpressionEngine specific

	//the noun (for LANG)
	public $noun = 'project';

	//the plural of the noun (for LANG)
	public $noun_plural = 'projects';

	//the listing columns to display and widths
	public $list_columns = array(
								'projectTitle'=>array(
									'width'	=>	'35%',
									'link'	=>	'project_view',
									'method'	=>	'get_project_title'
								),
								'projectLeader'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_project_leader'
								),
								'projectStatus'=>array(
									'width' => 	'10%',
									'link' 	=> 	null,
									'method' 	=>	'get_project_status'
								),
								'numberIssues'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_number_of_bugs'
								),
								'percentageFixedIssues'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_percentage_of_fixed_bugs'
								),
								'assignUsers'=>array(
									'width'	=>	'10%',
									'link'	=>'project_assign_members',
									'method'	=> 'get_total_users'
								),
								'export'=>array(
									'width'	=>	'5%',
									'link'	=>'project_export',
									'method'	=> null
								),
								'edit'=>array(
									'width'	=>	'5%',
									'link'	=>	'project_edit',
									'method'	=> null
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'projectTitle'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectDescription'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	false
								),
								'projectLeader'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectStatus'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectBugForm'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectColour'=>array(
									'type'	=>	'colourselect',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectUploadID'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'projectEmailEnabled'=>array(
									'type'	=>	'checkbox',
									'options'	=>	null,
									'required'	=>	false
								),
								'projectDefaultAssignee'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								)
							);

	//view fields
	public $view_fields = array('projectTitle'	=>	'get_project_title',
								'projectDescription'	=>	'get_project_description',
								'projectLeader'	=>	'get_project_leader',
								'projectStatus'	=>	'get_project_status',
								'projectBugForm'	=>	'get_project_bug_form',
								'projectColour'	=>	'get_project_colour',
								'projectUploadID'	=>	'get_upload_location_name',
								'projectEmailEnabled' => 'get_project_email_enabled',
								'projectDefaultAssignee'	=>	'get_default_assignee');

	//the text identifier and edit link identifier
	public $name_field = 'projectTitle';

	public function get_status_select() {
		global $LANG;
		
		//add member_id select to fields
		$selects = array();

		//get array
		$selects[$LANG->line('active')] = 'active';
		$selects[$LANG->line('inactive')] = 'inactive';

		//add to form_fields
		$select['type'] = 'select';
		$select['required'] = true;
		$select['options'] = $selects;

		//return
		return $select;
	}
	
	public function get_bug_form_select() {
		global $LANG;
		
		//add member_id select to fields
		$selects = array();

		//get array
		$selects[$LANG->line('simple')] = 'simple';
		$selects[$LANG->line('advanced')] = 'advanced';

		//add to form_fields
		$select['type'] = 'select';
		$select['required'] = true;
		$select['options'] = $selects;

		//return
		return $select;
	}

	public function get_leader_select() {
		//add member_id select to fields
		$selects = array();

		//get member ids
		$sql = "SELECT member_id, screen_name FROM exp_members";
		$query = $this->db->query($sql);
		foreach ($query->result as $result) {
			$selects[$result['screen_name']] = $result['member_id'];
		}

		//add to form_fields
		$select['type'] = 'select';
		$select['required'] = true;
		$select['options'] = $selects;

		//return
		return $select;
	}

	public function get_upload_id_select() {
		$selects = array();

		//get upload location ids
		$sql = "SELECT id, name FROM exp_upload_prefs";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			foreach ($query->result as $result) {
				$selects[$result['name']] = $result['id'];
			}
		}
		else {
			$selects['None'] = 0;
		}

		$select['type'] = 'select';
		$select['required'] = true;
		$select['options'] = $selects;

		return $select;
	}

	public function create_form($msg = false, $response = false) {
		global $DSP;

		//get status select
		$this->form_fields['projectStatus'] = $this->get_status_select();
		//get leader select
		$this->form_fields['projectLeader'] = $this->get_leader_select();
		//get upload loc select
		$this->form_fields['projectUploadID'] = $this->get_upload_id_select();
		//get bug form type select
		$this->form_fields['projectBugForm'] = $this->get_bug_form_select();
		//get default assignee select using existing method
		$this->form_fields['projectDefaultAssignee'] = $this->get_leader_select();

		//colour selector js
		$DSP->extra_header .= '<style type="text/css">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/css/colorpicker.css');
       	$DSP->extra_header .= '</style>';
		$DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/colorpicker.js');
       	$DSP->extra_header .= '</script>';
       	$DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/colourselector.js');
       	$DSP->extra_header .= '</script>';
       	

       	parent::create_form($msg, $response);
	}

	public function get_assigned_projects() {
		global $SESS;

		if ($SESS->userdata('group_id') == '1') {
			return $this->get_all_projects();
		}

		//get member_id
		$member_id = $SESS->userdata('member_id');

		$sql = "SELECT * FROM exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				ORDER BY projectStatus ASC, projectTitle ASC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	public function create($data, $once = true) {
		global $SESS, $LANG;
		
		$invalid_fields = array();
		foreach ($this->form_fields as $field=>$options) {
			if ($options['required'] == true && (!isset($_POST[$field]) || $_POST[$field] == '')) {
				$invalid_fields[] = $LANG->line($field);
			}
		}
		
		//if there are errors to output, output them
		if (count($invalid_fields) > 0) {
			$msg = 'The following fields are required: '.implode($invalid_fields, ', ');
			
			//go to bug view
			$this->create_form($msg, false);
			return;
		}
		
		$Project = parent::create($data, $once = true);
		
		if (is_object($Project)) {
			$memberdata['projectID'] = $Project->id();
			$memberdata['member_id'] = $SESS->userdata('member_id');
			$sql = $this->db->insert_string('exp_earwig_projects_members', $memberdata);
			$this->db->query($sql);
			
			//attempt to add default assignee if required
			if ($data['projectDefaultAssignee'] != $memberdata['member_id']) {
				$assigneedata['projectID'] = $Project->id();
				$assigneedata['member_id'] = $data['projectDefaultAssignee'];
				$sql = $this->db->insert_string('exp_earwig_projects_members', $assigneedata);
				$this->db->query($sql);
			}
		}
	}
	
	public function get_all_projects() {
		$sql = "SELECT * FROM exp_earwig_projects
				ORDER BY projectStatus ASC, projectTitle ASC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();			
	}

}

?>