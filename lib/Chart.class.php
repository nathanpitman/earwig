<?

class Chart {
	
	private $GoogleCharts;
	private $Project;
	private $db;
	
	public function __construct($Project) {
		global $DB;
		$this->db = $DB;
		$this->Project = $Project;
	}
	
	public function view() {
		global $DSP;
		
		$DSP->body .= '<div id="chart_img">';
		$DSP->body .= $this->view_init();
		$DSP->body .= '</div>';
	}
	
	public function switcher() {
		global $DSP;

		//get members
		$sql = "SELECT * FROM exp_members, exp_earwig_projects_members
				WHERE exp_members.member_id = exp_earwig_projects_members.member_id
				AND exp_earwig_projects_members.projectID = '".$this->Project->id()."'";
		$query = $this->db->query($sql);

		$html = '';
		
		//date select
		$html .= '<label for="chart_start">From: </label>&nbsp;';
		$html .= '<select id="chart_start" class="chart_select">';
		$html .= '<option value="">Start of project</option>';
		$html .= '<option value="4">4 weeks ago</option>';
		$html .= '<option value="3">3 weeks ago</option>';
		$html .= '<option value="2">2 weeks ago</option>';
		$html .= '<option value="1">1 week ago</option>';
		$html .= '</select>';
		
		$html .= '&nbsp;&nbsp;';
		
		//member select
		$html .= '<label for="chart_member">User: </label>&nbsp;';
		$html .= '<select id="chart_member" class="chart_select">';
		$html .= '<option value="">All assigned users</option>';
		
		if ($query->num_rows > 0) {
			foreach ($query->result as $member) {
				$html .= '<option value="'.$member['member_id'].'">'.$member['screen_name'].'</option>';
			}
		}
		
		$html .= '</select>';
		
		$DSP->body .= $html;
	}
	
	public function view_specific($weeks_ago = null, $member_id = null) {
		$Bugs = new Bugs();

		//get bugs array
		$occarray = $Bugs->get_fixed_bugs_over_period($this->Project, $weeks_ago, $member_id);		
		
		//create graph with correct title
		if ($member_id == null && $weeks_ago != null) {

			//do text
			if ($weeks_ago == 1) {
				$text = $weeks_ago.' week ago';
			}
			else {
				$text = $weeks_ago.' weeks ago';
			}

			$thischart = new GoogleCharts($occarray, 'bary', "Bugs fixed since ".$text." by all assigned users", '1000x300');
		}
		elseif ($member_id != null && $weeks_ago == null) {

			//get member
			$sql = "SELECT * FROM exp_members
					WHERE member_id = '".$member_id."'";
			$query = $this->db->query($sql);

			$thischart = new GoogleCharts($occarray, 'bary', "Bugs fixed since the start of the project by ".$query->result[0]['screen_name'], '1000x300');

		}
		elseif ($member_id != null && $weeks_ago != null) {

			//get member
			$sql = "SELECT * FROM exp_members
					WHERE member_id = '".$member_id."'";
			$query = $this->db->query($sql);
			
			//do text
			if ($weeks_ago == 1) {
				$text = $weeks_ago.' week ago';
			}
			else {
				$text = $weeks_ago.' weeks ago';
			}
			
			$thischart = new GoogleCharts($occarray, 'bary', "Bugs fixed since ".$text." by ".$query->result[0]['screen_name'], '1000x300');

		}
		
		//do y labels
		$labels = array();
		foreach ($occarray as $key=>$value) {
			$labels[] = $key;
		}
		$bl = implode('|', $labels);					
		$thischart->setLabels($bl,'bottom');
		if (max($occarray) == 0) {
			$string = '0';
		}
		else {
			$part = max($occarray)/4;
			$string = '0|'.round($part).'|'.round($part*2).'|'.round($part*3).'|'.round($part*4);
		}					
		$thischart->setlabels($string, 'left');	

		//draw
		return $thischart->draw();
	}
	
	public function view_init() {
		$Bugs = new Bugs();
		
		//get bugs array
		$occarray = $Bugs->get_cumulative_fixed_count($this->Project);
		
		$thischart = new GoogleCharts($occarray, 'line', "Bugs fixed since the start of the project by all assigned users", '1000x300');
	
		//do y labels
		$labels = array();
		foreach ($occarray as $key=>$value) {
			$labels[] = $key;
		}
		$bl = implode('|', $labels);					
		$thischart->setLabels($bl,'bottom');
		
		//do x labels and values
		if (max($occarray) == 0) {
			$string = '0';
		}
		else {
			$part = max($occarray) / 4;
			$string = '0|'.(round($part)).'|'.(round($part*2)).'|'.(round($part*3)).'|'.round(max($occarray));
		}					
		$thischart->setlabels($string, 'left');					

		//draw
		return $thischart->draw();
	}
	
}

?>