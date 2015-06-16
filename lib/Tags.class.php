<?

class Tags extends Factory {

	//Base/Factory specific

	protected $classname = "Tag";
	protected $table = "exp_earwig_tags";
	protected $pk = "tagID";

	//ExpressionEngine specific

	//the noun (for LANG)
	public $noun = 'tag';

	//the plural of the noun (for LANG)
	public $noun_plural = 'tags';

	//the listing columns to display and widths
	public $list_columns = array(
								'tagValue'=>array(
									'width'	=>	'80%',
									'link'	=>	'tag_view',
									'method'	=>	'tagValue'
								),
								'bugCount'=>array(
									'width'	=>	'10%',
									'link'	=>	null,
									'method'	=>	'get_bug_count'
								),
								'edit'=>array(
									'width'	=>	'5%',
									'link'	=>	'tag_edit',
									'method'	=> null
								)
							);

	//the creation/edit fields to display and types
	public $form_fields = array(
								'tagValue'=>array(
									'type'	=>	'text',
									'options'	=>	null,
									'required'	=>	true
								)
							);

	//view fields
	public $view_fields = array();

	//the text identifier and edit link identifier
	public $name_field = 'tagValue';

	public function find_all() {
		global $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		$sql = "SELECT *, COUNT(exp_earwig_tags.bugID) as count FROM exp_earwig_tags, exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_tags.bugID = exp_earwig_bugs.bugID
				AND exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				GROUP BY exp_earwig_tags.tagValue
				ORDER BY count DESC, exp_earwig_tags.tagValue ASC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	public function find_alphabetical() {
		global $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		$sql = "SELECT *, COUNT(exp_earwig_tags.bugID) as count FROM exp_earwig_tags, exp_earwig_bugs, exp_earwig_projects, exp_earwig_projects_members
				WHERE exp_earwig_tags.bugID = exp_earwig_bugs.bugID
				AND exp_earwig_bugs.projectID = exp_earwig_projects.projectID
				AND exp_earwig_projects.projectID = exp_earwig_projects_members.projectID
				AND exp_earwig_projects_members.member_id = '".$member_id."'
				AND exp_earwig_projects.projectStatus = 'active'
				GROUP BY exp_earwig_tags.tagValue
				ORDER BY exp_earwig_tags.tagValue ASC";
		$query = $this->db->query($sql);
		if ($query->num_rows > 0) {
			return $this->return_instances($query->result);
		}
		return array();
	}
	
	public function tag_cloud($AllTags) {
		global $DSP, $LANG;
		
		$tags = array();
		$tagids = array();
		foreach ($AllTags as $Tag) {
			$tags[$Tag->tagValue()] = $Tag->count();
			$tagids[$Tag->tagValue()] = $Tag->id();
		}
		if (is_array($tags) && is_array($tagids)) {
			//$tags = array('weddings' => 32, 'birthdays' => 41, 'landscapes' => 62, 'ham' => 51, 'chicken' => 23, 'food' => 91, 'turkey' => 47, 'windows' => 82, 'apple' => 27);
			$maxsize = "30px";
			$minsize = "14px";
			$maxquantity = max(array_values($tags));
			$minquantity = min(array_values($tags));
			$spread = $maxquantity - $minquantity;
			//avoid division by 0
			if ($spread == 0) $spread=1;
			$fontstep = ($maxsize - $minsize)/($spread);
			$output = '';
			foreach ($tags as $key=>$value) {
				$size = round($minsize + (($value - $minquantity) * $fontstep));
				$size = ceil($size);
				//get id
				$id = $tagids[$key];
			 	$output .= '<span class="tags"><a href="'.BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P=tag_view'.AMP.'id='.$id.'" style="font-size: '.$size.'px" title="'.$value.($value == 1 ? ' bug' : ' bugs').' tagged with '.$key.'">'.$key.'</a></span> ';
			}
		}		
		$DSP->body .= '<br/>';
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line($this->noun.'_cloud'));
		$DSP->body .= $DSP->div('box');
		if (is_array($tags) && is_array($tagids)) {
			$DSP->body .= $output;
		}
		else {
			$DSP->body .= $LANG->line('no_tags');
		}
		$DSP->body .= $DSP->div_c();
	}
	
	public function delete($extraid = false) {
		parent::delete($extraid, false);
	}

}

?>