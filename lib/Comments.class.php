<?

class Comments extends Factory {

	//Base/Factory specific

	protected $classname = "Comment";
	protected $table = "exp_earwig_comments";
	protected $pk = "commentID";

	//ExpressionEngine specific

	//the noun (for LANG)
	public $noun = 'comment';

	//the plural of the noun (for LANG)
	public $noun_plural = 'comments';

	//the listing columns to display and widths
	public $list_columns = array(
								'commentValue'=>array(
									'width'	=>	'65%',
									'link'	=>	null,
									'method'	=>	'get_comment_value'
								),
								'commentDate'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_comment_date'
								),
								'commentOwner'=>array(
									'width' => 	'10%',
									'link' 	=> 	null,
									'method' 	=>	'get_comment_owner'
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'commentValue'=>array(
									'type'	=>	'textarea',
									'options'	=>	null,
									'required'	=>	true
								)
							);

	//view fields
	public $view_fields = array('commentDate'	=>	null,
								'commentOwner'	=>	'get_comment_owner',
								'commentValue'	=>	'get_comment_value');

	//the text identifier and edit link identifier
	public $name_field = 'commentDate';

	//constructer
	public function __construct() {
		//parent constructer
		parent::__construct();
	}

	public function create($data) {
		global $LANG, $SESS, $IN, $FNS;

		//add member id
		$data['commentOwner'] = $SESS->userdata('member_id');
		//add bug id
		$data['bugID'] = $IN->GBL('id');

		//find bug
		$Bugs = new Bugs();
		$finddata['bugID'] = $data['bugID'];
		$Bug = $Bugs->find($finddata, $limit = 1);

		//unset potential bugStatus
		unset($data['bugStatus']);
		
		//unset potential attachmentName
		unset($data['attachmentName']);

		//create comment
		foreach ($data as $key=>$value) {
			$columns[] = $key;
			$values[] = "'".$value."'";
		}
		$sql = "INSERT INTO ".$this->table."(".implode(",", $columns).")
				VALUES(".implode(",", $values).")";
		$this->db->query($sql);
		
		//find comment
		$commentfinddata['commentID'] = $this->db->insert_id;
		$Comment = $this->find($commentfinddata, $limit = 1);
		
		//find project
		$Projects = new Projects();
		$projectfinddata['projectID'] = $Bug->projectID();
		$Project = $Projects->find($projectfinddata, $limit = 1);
		
		//attempt to e-mail assignee
		if ($Bug->bugAssignee() != 0 && $Bug->bugCreator() != '') {
			$Email = new Email($Project, $Bug, $Comment, false);
			$Email->comment_added();			
		}
		
		//add to timeline
		$Timelines = new Timelines();
		$Timelines->create('comments_add', $LANG->line('timeline_comment_added'), $Bug, $Comment, false);
		
		//return
		return true;	
	}

	public function list_objects_bug($msg = false, $status = false, $Comments = false, $extraid = false, $showheader = true) {
		$sql = "SELECT * FROM exp_earwig_comments
				WHERE bugID = '".$this->db->escape_str($extraid)."'
				ORDER BY commentDate ASC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			$AllComments = $this->return_instances($query->result);
		}
		else {
			$AllComments = array();
		}
		return parent::list_objects($msg, $status, $AllComments, $extraid, $showheader);
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