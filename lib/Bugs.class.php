<?

class Bugs extends Factory {

	//Base/Factory specific

	protected $classname = "Bug";
	protected $table = "exp_earwig_bugs";
	protected $pk = "bugID";

	//ExpressionEngine specific

  	//the noun (for LANG)
  	public $noun = 'bug';
  
  	//the plural of the noun (for LANG)
  	public $noun_plural = 'bugs';
  
  	//the listing columns to display and widths
  	public $list_columns = array(
  								'bugID'=>array(
  									'width'	=>	'5%',
  									'link'	=>	null,
  									'method'	=>	null
  								),
  								'projectID'=>array(
  									'width'	=>	'10%',
  									'link'	=>	null,
  									'method'	=>	'get_project_title'
  								),
  								'bugTitle'=>array(
  									'width'	=>	'25%',
  									'link'	=>	null,
  									'method'	=>	'get_bug_title'
  								),
  								'bugSeverity'=>array(
  									'width'	=>	'10%',
  									'link'	=>	null,
  									'method'	=>	'get_bug_severity'
  								),
  								'bugStatus'=>array(
  									'width'	=>	'10%',
  									'link'	=>	null,
  									'method'	=>	'get_bug_status'
  								),
  								'bugAssignee'=>array(
  									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_bug_assignee_link'
								),
								'bugCreator'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_bug_creator'
								),
								'bugDate'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_bug_date'
								),
								'bugComments'=>array(
									'width'	=>	'5%',
									'link'	=>	null,
									'method'	=>	'get_comment_link'
								),
								'edit'=>array(
									'width'	=>	'5%',
									'link'	=>	'bug_edit',
									'method'	=>	'get_edit_link'
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'projectID'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'bugStatus'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'bugSeverity'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true									
								),
								'bugTitle'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	true
								),
								'bugURL'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	false
								),
								'bugExpected'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	true
								),
								'bugActually'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	false
								),
								'bugDoing'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	false
								),
								'bugTags'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	false
								),
								'bugAssignee'=>array(
									'type'	=>	'select',
									'options'	=>	null,
									'required'	=>	true
								),
								'attachmentFile'=>array(
									'type'	=>	'file',
									'options'	=>	null,
									'required'	=>	false
								)
							);

	//view fields
	public $view_fields = array('bugID'	=>	null,
								'projectID'	=>	'get_project_title',
								'bugTitle'	=>	null,
								'bugDate'	=>	'get_bug_date',
								'bugStatus'	=>	'get_bug_status',
								'bugSeverity'	=>	'get_bug_severity',
								'bugFixedDate'	=>	'get_bug_fixed_date',
								'bugURL'	=>	'get_bug_url',
								'bugExpected'	=>	'get_bug_expected',
								'bugActually'	=>	'get_bug_actually',
								'bugDoing'	=>	'get_bug_doing',
								'bugTags'	=>	'get_bug_tag_links',
								'bugCreator'	=>	'get_bug_creator',
								'bugAssignee'	=>	'get_bug_assignee');

	//filter fields
	public $filter_fields = array('projectID'=>null, 'bugStatus'=>null, 'bugSeverity'=>null, 'bugAssignee'=>null, 'bugCreator'=>null);

	//the text identifier and edit link identifier
	public $name_field = 'bugTitle';

	public function create($data) {
		global $LANG, $SESS;

		//find project
		$Projects = new Projects();
		$projectfinddata['projectID'] = $data['projectID'];
		$Project = $Projects->find($projectfinddata, $limit = 1);
		unset($projectfinddata);

		//get button value
		if (isset($_POST['cancel'])) {
			$AllBugs = $this->find_all();
			$this->list_objects(false, false, $AllBugs);
			return;
		}
		elseif (isset($_POST['addanother'])) {
			$redirect = 'addanother';
			unset($data['addanother']);
		}
		elseif (isset($_POST['add'])) {
			$redirect = 'add';
			unset($data['add']);
		}
		
		//get creator id
		$data['bugCreator'] = $SESS->userdata('member_id');

		//get tags
		$tags = $data['bugTags'];
		unset($data['bugTags']);
		$tags = Util::tagparse($tags);

		//unset attachment
		unset($data['attachmentFile']);

		//attempt to get default assignee if required
		if ($data['bugAssignee'] == 0) {
			//get project
			$Projects = new Projects();
			$finddata['projectID'] = $data['projectID'];
			$Project = $Projects->find($finddata, 1);
			
			//get default assignee
			$data['bugAssignee'] = $Project->projectDefaultAssignee();
		}
		
		//check for required fields
		//unset bugStatus for create() method (only needed for update() method)
		unset($this->form_fields['bugStatus']);
		$invalid_fields = array();
		foreach ($this->form_fields as $field=>$options) {
			if ($options['required'] == true && (!isset($_POST[$field]) || $_POST[$field] == '')) {
				$invalid_fields[] = $LANG->line($field);
			}
		}
		
		//if there are errors to output, output them
		if (count($invalid_fields) > 0) {
			$msg = 'The following fields are required: '.implode($invalid_fields, ', ');
			
			//if project uses simple form, 'What you expected' should be replaced
			if ($Project->projectBugForm() == 'simple') {
				$msg = str_replace('What you expected to happen', 'Description', $msg);
			}
			
			$this->create_form($msg, false);
			return;
		}
	
		//attempt to create bug
		//insert
		$columns = array();
		$values = array();
		foreach ($data as $key=>$value) {
			$columns[] = $key;
			$values[] = "'".$value."'";
		}
		$sql = "INSERT INTO ".$this->table."(".implode(",", $columns).")
				VALUES(".implode(",", $values).")";
		$this->db->query($sql);

		//find bug
		$finddata['bugID'] = $this->db->insert_id;
		$Bug = $this->find($finddata, $limit=1);

		//add to timeline
		$Timelines = new Timelines();
		$Timelines->create('bug_add', $LANG->line('timeline_bug_added'), $Bug);
		
		//add assignee to timeline
		$Timelines->create('user_add', $LANG->line('timeline_bug_assigned').' '.$Bug->get_bug_assignee(), $Bug);

		//attempt to upload file if one is set
		if (isset($_FILES['attachmentFile'])) {			
			//get upload information
			$uploaddetails = $Project->get_upload_details();
			
			//filter size/type
			if (($uploaddetails['max_size'] == '' || $_FILES['attachmentFile']['size'] < $uploaddetails['max_size'])) {
				//get microtime
				$microtime = Util::microtime_float();
				$attachmentdata['attachmentLocation'] = $uploaddetails['server_path'].$microtime.'_'.basename($_FILES['attachmentFile']['name']); 
				$attachmentdata['attachmentMimeType'] = $_FILES['attachmentFile']['type'];
				if (move_uploaded_file($_FILES['attachmentFile']['tmp_name'], $attachmentdata['attachmentLocation'])) {
					//add member id
					$attachmentdata['attachmentOwner'] = $SESS->userdata('member_id');
					//add bug id
					$attachmentdata['bugID'] = $Bug->id();
					//add name
					$attachmentdata['attachmentName'] = $_FILES['attachmentFile']['name'];
			
					//create db attachment
					foreach ($attachmentdata as $key=>$value) {
						$attachmentcolumns[] = $key;
						$attachmentvalues[] = "'".$value."'";
					}
					$sql = "INSERT INTO exp_earwig_attachments (".implode(",", $attachmentcolumns).")
							VALUES(".implode(",", $attachmentvalues).")";
					$this->db->query($sql);
					
					$attachmentfinddata['attachmentID'] = $this->db->insert_id;
					$Attachments = new Attachments();
					$Attachment = $Attachments->find($attachmentfinddata, 1);					
					
					//add attachment to timeline
					$Timelines->create('attach', $LANG->line('timeline_attachment_added'), $Bug, false, $Attachment);
				}		
			}
		}

		//create tags
		if (is_array($tags) && is_object($Bug)) {
			foreach ($tags as $value) {
				$sql = "INSERT INTO exp_earwig_tags(tagValue, bugID)
						VALUES('".$value."', '".$Bug->id()."')";
				$this->db->query($sql);
			}
		}
		
		//attempt to e-mail assignee
		if ($Bug->bugAssignee() != 0) {
			$Email = new Email($Project, $Bug, false, false);
			$Email->bug_assigned();			
		}

		//list bug
		if ($redirect == 'add') {
			$Bug->view();
		}
		//add another
		elseif ($redirect == 'addanother') {
			unset($_POST);
			$this->create_form($LANG->line($this->noun.'_added'), true, false, true, false);
		}
	}

	public function get_project_select() {
		//add project select to fields
		$selects = array();

		//get assigned projects
		$Projects = new Projects();
		$AssignedProjects = $Projects->get_assigned_projects();

		//get projects
		foreach ($AssignedProjects as $Project) {
			$selects[$Project->projectTitle()] = $Project->id();
		}

		//add to form_fields
		$select['type'] = 'select';
		$select['required'] = true;
		$select['options'] = $selects;

		//return
		return $select;
	}

	public function get_assignee_select() {
		//add member_id select to fields
		$selects = array();
		$selects['--Select--'] = '0';

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

	public function get_bugstatus_select() {
		global $LANG;
		
		$selects = array();
		$selects[$LANG->line('open')] = 'open';
		$selects[$LANG->line('resolved')] = 'resolved';
		$selects[$LANG->line('closed')] = 'closed';
		$select['required'] = true;
		$select['type'] = 'select';
		$select['options'] = $selects;

		return $select;
	}
	
	public function get_bug_severity_select() {
		global $LANG;
		
		$selects = array();
		$selects[$LANG->line('critical')] = 'critical';
		$selects[$LANG->line('high')] = 'high';
		$selects[$LANG->line('normal')] = 'normal';
		$selects[$LANG->line('low')] = 'low';
		$selects[$LANG->line('trivial')] = 'trivial';
		$select['required'] = true;
		$select['type'] = 'select';
		$select['options'] = $selects;

		return $select;
	}

	public function get_inbox_bugs($Paging = false) {
		global $SESS;

		$member_id = $SESS->userdata('member_id');
		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$this->db->escape_str($member_id)."'
				AND exp_earwig_bugs.bugAssignee = '".$this->db->escape_str($member_id)."'
				AND exp_earwig_bugs.bugStatus = 'open'
				AND exp_earwig_projects.projectStatus = 'active'
				ORDER BY exp_earwig_bugs.bugDate DESC";
		if ($Paging != false && $Paging->enabled()) {
			$sql .= ' LIMIT '.$Paging->lower_bound().', '.$Paging->per_page();
		}
		$query = $this->db->query($sql);
		
		//do paging
		if ($Paging != false && $Paging->enabled()) {
			$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
					WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
					AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
					AND exp_earwig_projects_members.member_id = '".$this->db->escape_str($member_id)."'
					AND exp_earwig_bugs.bugAssignee = '".$this->db->escape_str($member_id)."'
					AND exp_earwig_bugs.bugStatus = 'open'
					AND exp_earwig_projects.projectStatus = 'active'
					ORDER BY exp_earwig_bugs.bugDate DESC";
			$pquery = $this->db->query($sql);
			$Paging->set_total($pquery->num_rows);
		}
		
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}

	public function find_all($Paging = false) {
		global $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				ORDER BY exp_earwig_bugs.bugStatus ASC, exp_earwig_bugs.bugDate DESC";
		if ($Paging != false && $Paging->enabled()) {
			$sql .= ' LIMIT '.$Paging->lower_bound().', '.$Paging->per_page();
		}
		$query = $this->db->query($sql);
		
		//do paging
		if ($Paging != false && $Paging->enabled()) {
			$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
					WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
					AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
					AND exp_earwig_projects_members.member_id = '".$member_id."'
					AND exp_earwig_projects.projectStatus = 'active'
					ORDER BY exp_earwig_bugs.bugStatus ASC, exp_earwig_bugs.bugDate DESC";
			$pquery = $this->db->query($sql);
			$Paging->set_total($pquery->num_rows);
		}
		
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}

	public function list_objects($msg = false, $status = false, $Bugs = false, $Paging = false) {
		//filters
		$this->filter_selects();
		
		$AllBugs = $this->find_all();
		if ($Bugs == false && !is_array($Bugs)) {
			parent::list_objects($msg, $status, $AllBugs, false, true, $Paging);
		}
		else {
			parent::list_objects($msg, $status, $Bugs, false, true, $Paging);
		}
	}

	public function get_inbox_bugs_count() {
		$InboxBugs = $this->get_inbox_bugs();

		return count($InboxBugs);
	}

	public function get_bugs_with_tag($Tag, $Paging = false) {
		global $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		//get tag value
		$tagvalue = $Tag->tagValue();

		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * FROM exp_earwig_bugs, exp_earwig_tags, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_bugs.bugID = exp_earwig_tags.bugID
				AND exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				AND exp_earwig_tags.tagValue = '".$tagvalue."'
				ORDER BY exp_earwig_bugs.bugStatus ASC, exp_earwig_bugs.bugDate DESC";
		if ($Paging != false && $Paging->enabled()) {
			$sql .= ' LIMIT '.$Paging->lower_bound().', '.$Paging->per_page();
		}
		$query = $this->db->query($sql);
		
		//do paging
		if ($Paging != false && $Paging->enabled()) {
			$sql = "SELECT * FROM exp_earwig_bugs, exp_earwig_tags, exp_earwig_projects, exp_earwig_projects_members
					WHERE exp_earwig_bugs.bugID = exp_earwig_tags.bugID
					AND exp_earwig_bugs.projectID = exp_earwig_projects.projectID
					AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
					AND exp_earwig_projects_members.member_id = '".$member_id."'
					AND exp_earwig_projects.projectStatus = 'active'
					AND exp_earwig_tags.tagValue = '".$tagvalue."'
					ORDER BY exp_earwig_bugs.bugStatus ASC, exp_earwig_bugs.bugDate DESC";
			$pquery = $this->db->query($sql);
			$Paging->set_total($pquery->num_rows);
		}

		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	public function create_form($msg = false, $response = false, $extraid = false, $showheader = true) {
		global $DSP;
		
		//bug status field
		$this->form_fields['bugStatus'] = $this->get_bugstatus_select();
		//project assignment field
		$this->form_fields['projectID'] = $this->get_project_select();
		//assignee field
		$this->form_fields['bugAssignee'] = $this->get_assignee_select();
		//severity field
		$this->form_fields['bugSeverity'] = $this->get_bug_severity_select();
		
		//remove status
		unset($this->form_fields['bugStatus']);		
		
		$DSP->extra_header .= '<script type="text/javascript">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/plugin.autocomplete.js');
		$DSP->extra_header .= '</script>';
		
		parent::create_form($msg, $response, $extraid, $showheader, true);
	}
	
	public function get_filtered_bugs($data, $inbox = false, $Paging = false) {
		global $SESS;
		
		//get member_id
		$member_id = $SESS->userdata('member_id');

		if (is_numeric($data['keywords'])) {
			$finddata['bugID'] = $data['keywords'];
			$out[] = $this->find($finddata, 1);
			return $out;
		}

		//start sql
		$sql = "SELECT * FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'";
		
		//inbox switch
		if ($inbox == true) {
			$sql .= " AND exp_earwig_bugs.bugAssignee = '".$member_id."'
					  AND exp_earwig_bugs.bugStatus = 'open'";
		}
		
		//project filter
		if (isset($data['projectID']) && $data['projectID'] != '') {
			$sql .= " AND exp_earwig_bugs.projectID = '".$data['projectID']."'";
		} 
		//status filter
		if (isset($data['bugStatus']) && $data['bugStatus'] != '') {
			$sql .= " AND exp_earwig_bugs.bugStatus = '".$data['bugStatus']."'";
		}
		//assignee filter
		if (isset($data['bugAssignee']) && $data['bugAssignee'] != '') {
			$sql .= " AND exp_earwig_bugs.bugAssignee ='".$data['bugAssignee']."'";
		}
		//creator filter
		if (isset($data['bugCreator']) && $data['bugCreator'] != '') {
			$sql .= " AND exp_earwig_bugs.bugCreator = '".$data['bugCreator']."'";
		}
		//severity filter
		if (isset($data['bugSeverity']) && $data['bugSeverity'] != '') {
			$sql .= " AND exp_earwig_bugs.bugSeverity = '".$data['bugSeverity']."'";
		}
		
		//search
		if (isset($data['keywords']) && $data['keywords'] != '') {
			$keywords = "'%".$data['keywords']."%'";
			$sql .= " AND (exp_earwig_bugs.bugTitle LIKE ".$keywords."
					  OR exp_earwig_bugs.bugURL LIKE ".$keywords."
					  OR exp_earwig_bugs.bugExpected LIKE ".$keywords."
					  OR exp_earwig_bugs.bugActually LIKE ".$keywords."
					  OR exp_earwig_bugs.bugDoing LIKE ".$keywords."
					  OR exp_earwig_bugs.bugSeverity LIKE ".$keywords."
					  OR exp_earwig_projects.projectTitle LIKE ".$keywords."
					  OR exp_earwig_projects.projectDescription LIKE ".$keywords.")";
		}
		
		//finish sql
		$sql .= " ORDER BY exp_earwig_bugs.bugStatus ASC, exp_earwig_bugs.bugDate DESC";
		
		if ($Paging != false && $Paging->enabled()) {
			$psql = $sql.' LIMIT '.$Paging->lower_bound().', '.$Paging->per_page();
		}
		else {
			$psql = $sql;
		}
		$query = $this->db->query($psql);
		
		//do paging
		if ($Paging != false && $Paging->enabled()) {
			$pquery = $this->db->query($sql);
			$Paging->set_total($pquery->num_rows);
		}
		
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	private function filter_selects() {
		global $SESS, $LANG;
		
		//get member id
		$member_id = $SESS->userdata('member_id');
		
		//project filter
		$Projects = new Projects();
		$AssignedProjects = $Projects->get_assigned_projects();
		if (count($AssignedProjects) > 1 && is_array($AssignedProjects)) {
			$selects = array();
			foreach ($AssignedProjects as $Project) {
				$selects[$Project->projectTitle()] = $Project->id();
			}
			$this->filter_fields['projectID'] = $selects;
		}
		else {
			unset($this->filter_fields['projectID']);
		}
		
		//url filter
		/*$sql = "SELECT bugURL FROM exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$selects = array();
			foreach ($query->result as $result) {
				$selects[$result['bugURL']] = $result['bugURL'];
			}
		}
		$this->filter_fields['bugURL'] = $selects;*/
		
		//status filter
		$selects = array();
		$selects[$LANG->line('resolved')] = 'resolved';
		$selects[$LANG->line('open')] = 'open';
		$selects[$LANG->line('closed')] = 'closed';
		$this->filter_fields['bugStatus'] = $selects;
		
		//severity filter
		$selects = array();
		$selects[$LANG->line('critical')] = 'critical';
		$selects[$LANG->line('high')] = 'high';
		$selects[$LANG->line('normal')] = 'normal';
		$selects[$LANG->line('low')] = 'low';
		$selects[$LANG->line('trivial')] = 'trivial';
		$this->filter_fields['bugSeverity'] = $selects;
		
		//get all users from relevant projects
		//get relevant projects
		$Projects = new Projects();
		$ThisProjects = $Projects->get_assigned_projects();
		
		//add members
		$members = array();
		foreach ($ThisProjects as $Project) {
			$m = $Project->get_assigned_members();
			foreach ($m as $member) {
				if (!in_array($m, $members)) {
					$members[] = $member;	
				}
			}
		}
		
		$selects = array();
		foreach ($members as $user) {
			$selects[$user['screen_name']] = $user['member_id'];
		}
		
		//assigned filter
		if ($this->noun != 'inbox') {
			$this->filter_fields['bugAssignee'] = $selects;
		}
		else {
			unset($this->filter_fields['bugAssignee']);
		}
		
		//creator filter
		$this->filter_fields['bugCreator'] = $selects;
	}
	
	public function get_fixed_bugs_over_period($Project, $weeks = null, $member_id = null) {
		$days = $weeks * 7;

		$nowmicro = Util::microtime_float();
		$pastmicro = $nowmicro - (60*60*24*$days);
		$nowdate = date('Y-m-d', $nowmicro);
		$pastdate = date('Y-m-d', $pastmicro);
		
		$sql = "SELECT * FROM exp_earwig_bugs
				WHERE exp_earwig_bugs.projectID = '".$this->db->escape_str($Project->id())."'
				AND exp_earwig_bugs.bugStatus = 'resolved'";
	
		//member id set
		if ($member_id != null) {
			$sql .= " AND exp_earwig_bugs.bugAssignee = '".$member_id."'";
		}			
		
		//weeks set
		if ($weeks != null) {
			$sql .= " AND bugFixedDate BETWEEN '".$pastdate." 00:00:00' AND '".$nowdate." 23:59:59'";
		}
		
		$sql .= " ORDER BY bugFixedDate ASC";
		
		$query = $this->db->query($sql);

		$occ = array();

		if ($weeks != null) {
			$initmicro = $pastmicro;
			for ($i = 0; $i <= $days; $i++) {
				$occ[date('d/m', $initmicro)] = 0;
				$initmicro = $initmicro + (60*60*24);			
			}
		}

		if ($query->num_rows > 0) {
			foreach ($query->result as $r) {
				$B = new Bug($r);

				$date = date('d/m', strtotime($B->bugFixedDate()));
				if (isset($occ[$date])) {
					$occ[$date]++;
				}
				else {
					$occ[$date] = 1;
				}
			}
		}

		return $occ;
	}
	
	public function get_cumulative_fixed_count($Project) {
		$sql = "SELECT * FROM exp_earwig_bugs
				WHERE exp_earwig_bugs.projectID = '".$this->db->escape_str($Project->id())."'
				ORDER BY exp_earwig_bugs.bugFixedDate ASC";
		$query = $this->db->query($sql);		
		
		$occ = array();
		$count = 1;
		
		if ($query->num_rows > 0) {
			foreach ($query->result as $r) {
			
				$date = date('d/m', strtotime($r['bugFixedDate']));
			
				if ($date != '01 Jan') {			
					$occ[date('d/m', strtotime($r['bugFixedDate']))] = $count;
					$count++;	
				}			
			
			}
		}
		
		return $occ;
	}
}

?>