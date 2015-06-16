<?

class Attachments extends Factory {

	//Base/Factory specific

	protected $classname = "Attachment";
	protected $table = "exp_earwig_attachments";
	protected $pk = "attachmentID";

	//ExpressionEngine specific

	//the noun (for LANG)
	public $noun = 'attachment';

	//the plural of the noun (for LANG)
	public $noun_plural = 'attachments';

	//the listing columns to display and widths
	public $list_columns = array(
								'attachmentName'=>array(
									'width'	=>	'75%',
									'link'	=>	null,
									'method'	=>	'get_attachment_link'
								),								
								'attachmentOwner'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_attachment_owner'
								),								
								'attachmentDate'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_attachment_date'
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'attachmentName'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	true
								),
								'attachmentFile'=>array(
									'type'	=>	'file',
									'options'	=>	null,
									'required'	=>	true
								)
							);

	//view fields
	public $view_fields = array();

	//the text identifier and edit link identifier
	public $name_field = 'attachmentName';
	
	public function list_objects($msg = false, $status = false, $Attachments = false, $extraid = false, $showheader = true) {
		$sql = "SELECT * FROM exp_earwig_attachments
				WHERE bugID = '".$this->db->escape_str($extraid)."'
				ORDER BY attachmentDate DESC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$AllAttachments = $this->return_instances($query->result);
		}
		else {
			$AllAttachments = array();
		}
		return parent::list_objects($msg, $status, $AllAttachments, $extraid, $showheader);
	}
	
	public function create($data, $file = false) {
		global $LANG, $SESS, $IN, $FNS;
		
		if ($file == false) {
			$file = $_FILES['attachmentFile'];
		}
		
		//find bug
		$Bugs = new Bugs();
		$finddata['bugID'] = $IN->GBL('id');
		$Bug = $Bugs->find($finddata, $limit = 1);
		unset($finddata);
		
		//find project
		$Projects = new Projects();
		$finddata['projectID'] = $Bug->projectID();
		$Project = $Projects->find($finddata, $limit = 1);
		unset($finddata);
		
		//get upload information
		$uploaddetails = $Project->get_upload_details();
		
		//filter size/type
		if (($uploaddetails['max_size'] == '' || $file['size'] < $uploaddetails['max_size'])) {
			//get microtime
			$microtime = Util::microtime_float();
			$data['attachmentLocation'] = $uploaddetails['server_path'].$microtime.'_'.basename($file['name']); 

			//name/mime type
			$data['attachmentMimeType'] = $file['type'];
			$data['attachmentName'] = $file['name'];
			
			//encode for url
			$data['attachmentLocation'] = str_replace('\'', '', $data['attachmentLocation']);
			$data['attachmentLocation'] = str_replace(' ', '_', $data['attachmentLocation']);
			
			if (@!move_uploaded_file($file['tmp_name'], $data['attachmentLocation'])) {
				return false;
			}		
		}
		else {
			return false;
		}
		
		//add member id
		$data['attachmentOwner'] = $SESS->userdata('member_id');
		//add bug id
		$data['bugID'] = $IN->GBL('id');
		
		//unset possible comment value
		unset($data['commentValue']);

		//unset possible bug status value
		unset($data['bugStatus']);
		
		//create db attachment
		foreach ($data as $key=>$value) {
			$columns[] = $key;
			$values[] = "'".$value."'";
		}
		$sql = "INSERT INTO ".$this->table."(".implode(",", $columns).")
				VALUES(".implode(",", $values).")";
		$this->db->query($sql);
		
		//find attachment
		$attachmentfinddata['attachmentID'] = $this->db->insert_id;
		$Attachment = $this->find($attachmentfinddata, $limit=1);
		
		//attempt to e-mail assignee and creator
		if ($Bug->bugAssignee() != 0 && $Bug->bugCreator() != 0) {
			$Email = new Email($Project, $Bug, false, $Attachment);
			$Email->attachment_added();			
		}
		
		//add to timeline
		$Timelines = new Timelines();
		$Timelines->create('attach', $LANG->line('timeline_attachment_added'), $Bug, false, $Attachment);		

		//output bug
		unset($_POST);
		return true;
	}
	
	public function delete($extraid = false) {
		global $IN, $DSP, $LANG, $SESS, $DB;

		//find objects and delete
        foreach ($_POST as $key=>$val) {
            if (strstr($key, 'delete') && !is_array($val) && is_numeric($val)) {
                $data[$this->pk] = $val;
                $Obj = $this->find($data, $limit=1);
                $Obj->delete();
            }
        }

		$Bugs = new Bugs();
		$finddata['bugID'] = $extraid;
		$Bug = $Bugs->find($finddata, $limit=1);
		$Bug->view($LANG->line($this->noun.'_ambiguous').' '.$LANG->line('successfully_deleted'), true);
	}

}

?>