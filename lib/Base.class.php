<?php

class Base {

	protected $db;
	protected $details;
	protected $plural;

	function __construct($details) {
		global $DB;

		$this->db = $DB;
		$this->details  = $details;

		//find plural
		$class = get_class($this);
		$this->plural = $class.'s';

		//get public attributes
		$vars = get_class_vars($this->plural);
		foreach ($vars as $key=>$val) {
			$this->$key = $val;
		}
	}


	function __call($method, $arguments) {
		if (isset($this->details[$method])) {
			return $this->details[$method];
		}
		else {
			if (isset($this->details[$this->pk])) {
				$sql = "SELECT ".$method." FROM ".$this->table.
					   " WHERE ".$this->pk."=".$this->db->escape_str($this->details[$this->pk]);
				$query = $this->db->query($sql);
				$result = $query->result;
				$this->details[$method] = $result[0];
				return $this->details[$method];
			}
		}
		return false;
	}

	public function id() {
		return $this->details[$this->pk];
	}

	public function to_array() {
		return $this->details;
	}

	public function update($data, $existing_check = false, $view = true) {
		global $LANG;

		//check for existing name
		if ($existing_check == true) {
			$namefield = $this->name_field;
			if ($data[$namefield] != $this->$namefield()) {
				$sql = "SELECT * FROM ".$this->table."
						WHERE ".$this->name_field." = '".$this->db->escape_str($data[$this->name_field])."'";
				$query = $this->db->query($sql);
				$result = $query->result;
				if ($query->num_rows > 0) {
					return $this->view($LANG->line($this->noun.'_exists'), false);
				}
			}
		}

		//create sql
		$sql = "UPDATE ".$this->table." SET ";
		$items = array();
		foreach ($data as $key=>$value) {
			$items[] = $key." = '".$this->db->escape_str($value)."'";
		}
		$sql .= implode(", ", $items);
		$sql .= " WHERE ".$this->pk." = '".$this->db->escape_str($this->id())."'";

		//perform update
		$this->db->query($sql);
		$this->details = array_merge($this->details, $data);

		if ($view == true) {
			$this->view($LANG->line($this->noun.'_edited'), true);
		}

		return $this;
	}

	public function delete() {
		//create sql
		$sql = "DELETE FROM ".$this->table." WHERE ".
				$this->pk."='".$this->id()."'";

		//perform deletion
		return $this->db->query($sql);
	}

    //ExpressionEngine-specific methods

    //edit form
    public function edit_form() {
		global $DSP, $LANG, $IN, $DB, $PREFS;

		//JS includes
        $DSP->extra_header .= "<link rel=\"stylesheet\" href=\"".$PREFS->ini('theme_folder_url', TRUE)."jquery_ui/cupertino/jquery-ui-1.7.2.custom.css\" type=\"text/css\" media=\"screen\" />";
        $DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/ui.datepicker.js');
       	$DSP->extra_header .= '</script>';
       	$DSP->extra_header .= '<script type="text/javascript">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/form.js');
       	$DSP->extra_header .= '</script>';

		//header
		if (get_class($this) == 'Bug' || get_class($this) == 'Project') {
			Factory::page_header(false, false, true, $this->noun.'_edit', $this->noun, $this->id(), $this);
		}
		else {
			Factory::page_header(false, false, false, $this->noun.'_edit', $this->noun);
		}

		//start form
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line($this->noun.'_edit'));
		$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_edit'.AMP.'id='.$this->id()));
		$DSP->body .= $DSP->table('tableBorder', '0', '0', '100%');

		//add fields to form
		foreach ($this->form_fields as $fieldname=>$field) {
			$DSP->body .= $DSP->tr();
			switch ($field['type']) {
				case 'text':
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text($fieldname, $this->$fieldname(), '35', '', 'input', '100%', 'id="'.$fieldname.'"'))));
					break;
				case 'textarea':
					$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_textarea($fieldname, $this->$fieldname(), '10', '', '100%', 'id="'.$fieldname.'"'))));
					break;
				case 'select':
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					$select = '<select name="'.$fieldname.'" class="select" id="'.$fieldname.'">';
					foreach ($field['options'] as $name=>$value) {
						if ($this->$fieldname() == $value) {
							$select .= $DSP->input_select_option($value, $name, 'y');
						}
						else {
							$select .= $DSP->input_select_option($value, $name);
						}
					}
					$select .= '</select>';
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $select));
					break;
				case 'datetime':
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text($fieldname, $this->$fieldname(), '35', '', 'input datePicker', '100%', 'id="'.$fieldname.'"'))));
					break;
				case 'colourselect':
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text($fieldname, $this->$fieldname(), '35', false, 'input colourSelector', '100%', 'id="'.$fieldname.'"'))));
					break;
				case 'file':
					if ($this->noun != 'bug') {
						$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
						$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '<input name="'.$fieldname.'" id="'.$fieldname.'" type="file" id="'.$fieldname.'/>'));
					}
					break;
				case 'checkbox':
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
					if ($this->$fieldname() == true) {
						$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox($fieldname, '1', 1, 'id="'.$fieldname.'"')));
					}
					else {
						$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox($fieldname, '1', 0, 'id="'.$fieldname.'"')));
					}
			}
			$DSP->body .= $DSP->tr_c();
		}

		//finish form
		$DSP->body .= $DSP->table_c();
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('edit')));
	    $DSP->body .= $DSP->form_close();
    }

     public function assign_members_form() {
    	global $DSP, $LANG, $IN, $DB;

		//header
		Factory::page_header(false, false, false, $this->noun.'_assign_members', $this->noun);

		//get all site members
		$query = $DB->query("SELECT * FROM exp_members");
		$members = array();
		foreach ($query->result as $member) {
			$members[$member['member_id']] = $member['screen_name'];
		}

		//get currently assigned members
		$query = $DB->query("SELECT member_id FROM ".$this->table."_members
							  WHERE ".$this->pk." = '".$DB->escape_str($this->id())."'");
		$assignedmembers = array();
		foreach ($query->result as $member) {
			$assignedmembers[] = $member['member_id'];
		}

		$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_assign_members'), array('id' => $this->id()));
		$DSP->body .= $DSP->input_select_header('members[]', 'y', 8);
		foreach ($members as $member_id=>$screen_name) {
			if (in_array($member_id, $assignedmembers)) {
				$DSP->body .= $DSP->input_select_option($member_id, $screen_name, 'y');
			}
			else {
				$DSP->body .= $DSP->input_select_option($member_id, $screen_name);
			}
		}
		$DSP->body .= $DSP->input_select_footer();
		$DSP->body .= BR;
		$DSP->body .= $LANG->line('assignment_note');
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('assign')));
		$DSP->body .= $DSP->form_close();
    }

    public function assign_members($data) {
		global $DSP, $LANG, $DB;

		//delete old members
		$sql = "DELETE FROM ".$this->table."_members
				WHERE ".$this->pk." = '".$DB->escape_str($this->id())."'";
		$DB->query($sql);

		//insert new members
		if (isset($data['members']) && is_array($data['members'])) {
			foreach ($data['members'] as $member) {
				$DB->query("INSERT INTO ".$this->table."_members (".$this->pk.", member_id)
							VALUES ('".$DB->escape_str($this->id())."', '".$DB->escape_str($member)."')");
			}
		}

		//return
		return true;
    }
    
    public function get_assigned_members() {
    	global $DB;
    	
    	$sql = "SELECT DISTINCT * FROM ".$this->table."_members, exp_members
    			WHERE ".$this->pk." = '".$DB->escape_str($this->id())."'
    			AND ".$this->table."_members.member_id = exp_members.member_id";
    	$query = $DB->query($sql);
    	
    	if ($query->num_rows > 0) {
    		return $query->result;
    	}
    	return array();
    }

    public function view($msg = false, $response = false, $showheader = true) {
		global $DSP, $LANG, $IN, $DB, $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		//get project leader id
		$project_leader = 0;
		if ($this->noun == 'bug') {
			$sql = "SELECT exp_earwig_projects.projectLeader FROM exp_earwig_bugs, exp_earwig_projects
					WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID";
			$query = $this->db->query($sql);
			if ($query->num_rows > 0) {
				$project_leader = $query->result[0]['projectLeader'];
			}
		}
		
		//get project
		if ($this->noun == 'bug') {
			$Projects = new Projects();
			$projectfinddata['projectID'] = $this->projectID();
			$Project = $Projects->find($projectfinddata, $limit=1);
		}

		//header
		if ($showheader == true) {
			if ($this->noun == 'bug' || $this->noun == 'project') {
				Factory::page_header($msg, $response, true, $this->noun.'_view', $this->noun, $this->id(), $this);
			}
			else {
				Factory::page_header($msg, $response, false, $this->noun.'_view', $this->noun);
			}
		}

		//start table
		$name = $this->name_field;
		$DSP->body .= $DSP->qdiv('tableHeading', $this->$name());
		$DSP->body .= $DSP->table('tableBorder', '0', '0', '100%');

		//add fields to table
		foreach ($this->view_fields as $field=>$method) {
			$DSP->body .= $DSP->tr();
			if ($method == null) {
				$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $DSP->qdiv('defaultBold', $LANG->line($field)), '15%');
				$DSP->body .= $DSP->table_qcell('tableCellTwo', $DSP->qdiv('default', $this->$field()), '85%');
			}
			else {
				//only allow specific users to edit bug status inline
				if ($this->noun == 'bug' && $field == 'bugStatus') {
					$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $DSP->qdiv('defaultBold', $LANG->line($field)), '15%');
					if ($this->bugAssignee() == $member_id || $this->bugCreator() == $member_id || $project_leader == $member_id || $SESS->userdata('group_id') == '1') {
						$DSP->body .= $DSP->table_qcell('tableCellTwo', $DSP->qdiv('default', $this->get_bug_status()), '85%');
					}
					else {
						$DSP->body .= $DSP->table_qcell('tableCellTwo', $DSP->qdiv('default', $this->get_bug_status_no_link()), '85%');
					}
				}
				//for simple form (descriptor field rathen than 3 fields)
				elseif ($this->noun == 'bug' && $Project->projectBugForm() == 'simple' && ($field == 'bugExpected' || $field == 'bugActually' || $field == 'bugDoing')) {
					if ($field == 'bugExpected') {
						$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $DSP->qdiv('defaultBold', $LANG->line('description')), '15%');
						$DSP->body .= $DSP->table_qcell('tableCellTwo', $DSP->qdiv('default', $this->$method()), '85%');
					}
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $DSP->qdiv('defaultBold', $LANG->line($field)), '15%');
					$DSP->body .= $DSP->table_qcell('tableCellTwo', $DSP->qdiv('default', $this->$method()), '85%');
				}
			}
			$DSP->body .= $DSP->tr_c();
		}

		//finish table
		$DSP->body .= $DSP->table_c();
    }

}

?>
