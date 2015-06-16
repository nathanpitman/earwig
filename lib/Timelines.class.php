<?

class Timelines extends Factory {

	//Base/Factory specific

	protected $classname = "Timeline";
	protected $table = "exp_earwig_timelines";
	protected $pk = "timelineID";

	//ExpressionEngine specific

	//the noun (for LANG)
	public $noun = 'timeline';

	//the plural of the noun (for LANG)
	public $noun_plural = 'timelines';

	//the listing columns to display and widths
	public $list_columns = array(
								'timelineIcon'=>array(
									'width'	=>	'2%',
									'link'	=>	null,
									'method'	=>	'timelineIcon'
								),								
								'timelineDetails'=>array(
									'width'	=>	'73%',
									'link'	=>	null,
									'method'	=>	'get_timeline_details'
								),								
								'timelineDate'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_timeline_date'
								),
								'timelineMember'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_timeline_member'	
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'commentValue'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	true
								),
								'bugStatus'=>array(
									'type'	=>	'radio',
									'options'	=>	null,
									'required'	=>	false
								),
								'attachmentFile'=>array(
									'type'	=>	'file',
									'options'	=>	'multi',
									'required'	=>	false									
								)
							);

	//view fields
	public $view_fields = array();

	//the text identifier and edit link identifier
	public $name_field = 'timelineDate';
	
	public function list_objects($msg = false, $status = false, $Attachments = false, $extraid = false, $showheader = true) {
		//get total count
		$sql = "SELECT * FROM exp_earwig_timelines
				WHERE bugID = '".$this->db->escape_str($extraid)."'
				ORDER BY timelineDate ASC, timelineID ASC";
		$query = $this->db->query($sql);

		$total_count = $query->num_rows;
		$low_count = $total_count - 5;
		
		if ($low_count < 0) {
			$low_count = 0;
		}

		$sql = "SELECT * FROM exp_earwig_timelines
				WHERE bugID = '".$this->db->escape_str($extraid)."'
				ORDER BY timelineDate ASC, timelineID ASC
				LIMIT ".$low_count.", ".$total_count;
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$AllTimelines = $this->return_instances($query->result);
		}
		else {
			$AllTimelines = array();
		}
		return parent::list_objects($msg, $status, $AllTimelines, $extraid, $showheader);
	}
	
	public function create($icon, $details, $Bug, $Comment = false, $Attachment = false) {
		global $SESS;
		
		if ($Comment != false && is_object($Comment)) {
			$data['commentID'] = $Comment->id();
		}
		
		if ($Attachment != false && is_object($Attachment)) {
			$data['attachmentID'] = $Attachment->id();
		}
		
		$data['timelineDetails'] = $details;
		$data['timelineIcon'] = '<img src="'.PATH_MOD_IMAGES.'/'.$icon.'.png" class="timelineIcon" />';
		$data['timelineMember'] = $SESS->userdata('member_id');
		$data['bugID'] = $Bug->id();
		
		return parent::create($data, false, false);
	}
	
	public function create_from_form($data) {
		global $IN, $LANG;
		
		$Bugs = new Bugs();
		$finddata['bugID'] = $IN->GBL('id');
		$Bug = $Bugs->find($finddata, 1);		
		
		//check for comment value
		if ($data['commentValue'] == '' || !isset($data['commentValue'])) {
			$Bug->view($LANG->line('no_comment_value'), false);
			return;
		}
		
		$msg = array();
		
		//add attachment if necessary
		$Attachments = new Attachments();
		
		if (isset($_FILES['attachmentFile'])) {
			//get count
			$count = count($_FILES['attachmentFile']['name']);
			$i = 0;
		
			while ($i < $count) {
				if ($_FILES['attachmentFile']['name'][$i] != '') {
					$file['name'] = $_FILES['attachmentFile']['name'][$i];
					$file['type'] = $_FILES['attachmentFile']['type'][$i];
					$file['tmp_name'] = $_FILES['attachmentFile']['tmp_name'][$i];
					$file['error'] = $_FILES['attachmentFile']['error'][$i];
					$file['size'] = $_FILES['attachmentFile']['size'][$i];
					
					if ($Attachments->create($data, $file)) {
						$msg[] = $LANG->line('attachment_added');
					}
					else {
						$Bug->view($LANG->line('attachment_error'), false);
						return;
					}
				}
				$i++;
			}
		}
		
		//add comment if necessary
		$Comments = new Comments();
		if (isset($data['commentValue']) && $data['commentValue'] != '') {
			if (!isset($data['bugStatus']) || $Bug->bugStatus() == $data['bugStatus']) {
				$Comments->create($data);
				$msg[] = $LANG->line('comment_added');
			}
			elseif (isset($data['bugStatus']) && $Bug->bugStatus() != $data['bugStatus']) {
				//update bug
				$updatedata['bugStatus'] = $data['bugStatus'];
				$Bug->update($updatedata, false);
				$Comments->create($data);
				$msg[] = $LANG->line('status_edited');
				$msg[] = $LANG->line('comment_added');
			}
		}
		
		//do return
		$m = implode($msg, '<br />');
		$Bug->view($m, true);
	}
	
	public function delete($extraid = false, $return = true) {
		global $IN, $DSP, $LANG, $SESS, $DB;
		
		$Attachments = new Attachments();
		$Comments = new Comments();

		//find objects and delete
        foreach ($_POST as $key=>$val) {
            if (strstr($key, 'delete') && !is_array($val) && is_numeric($val)) {
                $data[$this->pk] = $val;
                $Obj = $this->find($data, $limit=1);
                
                //potentially delete comments/attachments
                if ($Obj->attachmentID() != null && !is_array($Obj->attachmentID())) {
	                $afinddata['attachmentID'] = $Obj->attachmentID();
	                $Attachment = $Attachments->find($afinddata, 1);
	                if (is_object($Attachment)) {
		                $Attachment->delete();
		            }
	            }
	            
	            if ($Obj->commentID() != null && !is_array($Obj->commentID())) {
	                $cfinddata['commentID'] = $Obj->commentID();
	                $Comment = $Comments->find($cfinddata, 1);
	                if (is_object($Comment)) {
		            	$Comment->delete();
		            }
	            }
                
                if (is_object($Obj)) {
	                $Obj->delete();
	            }
            }
        }

		$Bugs = new Bugs();
		$finddata['bugID'] = $extraid;
		$Bug = $Bugs->find($finddata, $limit=1);
		$Bug->view($LANG->line($this->noun.'_ambiguous').' '.$LANG->line('successfully_deleted'), true);
	}
	
	public function get_bugstatus_select() {
		global $LANG;
		
		$selects = array();
		$selects[$LANG->line('open')] = 'open';
		$selects[$LANG->line('resolved')] = 'resolved';
		$selects[$LANG->line('closed')] = 'closed';
		$select['required'] = false;
		$select['type'] = 'radio';
		$select['options'] = $selects;

		return $select;
	}
	
	public function create_form($msg = false, $response = false, $extraid = false, $showheader = true) {
		global $DSP, $SESS;
		
		unset($_POST);
		
		//get bug
		$Bugs = new Bugs();
		$data['bugID'] = $extraid;
		$Bug = $Bugs->find($data, 1);
		unset($data);
		
		//get project
		$Projects = new Projects();
		$data['projectID'] = $Bug->projectID();
		$Project = $Projects->find($data, 1);
		
		//bug status field
		$this->form_fields['bugStatus'] = $this->get_bugstatus_select();
		
		//get current member id
		$member_id = $SESS->userdata('member_id');
		
		//set current bug status so the correct one shows up in radio selects
		$_POST['bugStatus'] = $Bug->bugStatus();
		
		//unset form field if no permissions
		if ($Bug->bugCreator() != $member_id && $Project->projectLeader() != $member_id && $Bug->bugAssignee() != $member_id && $SESS->userdata('group_id') != '1') {
			unset($this->form_fields['bugStatus']);
		}
				
		parent::create_form($msg, $response, $extraid, $showheader, true);
	}
	
	public function find_latest($data) {
		$sql = "SELECT * FROM exp_earwig_timelines 
				WHERE bugID = '".$data['bugID']."'
				ORDER BY timelineDate ASC
				LIMIT 5";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	public function list_objects_direct($msg = false, $status = false, $Objs = false, $extraid = false, $showheader = false, $Paging = false) {
		return parent::list_objects($msg, $status, $Objs, $extraid, $showheader, $Paging);
	}
}

?>