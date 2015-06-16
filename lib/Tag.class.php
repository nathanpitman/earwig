<?

class Tag extends Base {

	//Base/Factory specific

	protected $classname = "Tag";
	protected $table = "exp_earwig_tags";
	protected $pk = "tagID";

	public function get_bug_count() {
		global $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		$details = $this->to_array();
		$sql = "SELECT * FROM exp_earwig_tags, exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_tags.bugID = exp_earwig_bugs.bugID
				AND exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				AND exp_earwig_tags.tagValue = '".$details['tagValue']."'";
		$query = $this->db->query($sql);
		return (string) $query->num_rows;
	}
	
	public function update($data) {
		//get all tags with current value
		$sql = "SELECT * FROM exp_earwig_tags
				WHERE tagValue = '".$this->tagValue()."'";
		$query = $this->db->query($sql);
		
		foreach ($query->result as $info) {
			$Tag = new Tag($info);
			$Tag->parent_update($data);
		}
		
		return true;
	}
	
	public function parent_update($data) {
		parent::update($data, false, false);
	}

}

?>