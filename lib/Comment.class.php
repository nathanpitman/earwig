<?

class Comment extends Base {

	//Base/Factory specific

	protected $classname = "Comment";
	protected $table = "exp_earwig_comments";
	protected $pk = "commentID";

	public function __construct($data) {
		parent::__construct($data);
	}

	public function update($data) {
		global $LANG;

		parent::update($data);
		$FNS->redirect(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=bug_view&id='.$data['bugID']);
	}

	public function get_comment_owner() {
		global $LANG;

		$sql = "SELECT screen_name FROM exp_members
				WHERE member_id = '".$this->db->escape_str($this->commentOwner())."'";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $query->result[0]['screen_name'];
		}
		return $LANG->line('unknown');
	}

	public function get_comment_value() {
		//instantiate typography class
		if (!class_exists('Typography')) {
			require PATH_CORE.'core.typography'.EXT;
		}
		$TYPE = new Typography;

		return Util::parse_for_bugs($TYPE->parse_type(nl2br($this->commentValue()), array('text_format'=>'xhtml', 'html_format'=>'safe', 'auto_links'=>'y', 'allow_img_url'=>'n')));
	}

	public function get_comment_date() {
		global $PREFS;

		$time = strtotime($this->commentDate());
		if ($PREFS->ini('time_format') == 'us') {
			return date('m/d/Y h:i a', $time);
		}
		elseif ($PREFS->ini('time_format') == 'eu') {
			return date('H:i d/m/Y', $time);
		}
	}
	
	public function view() {
		return $this->get_comment_value();
	}


}

?>