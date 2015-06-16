<?php

class Ajax {

	public function __construct() {
		if (isset($_GET['ajax_method'])) {
			switch ($_GET['ajax_method']) {
				case 'swapStatus':
					$this->swap_status();
					break;
				case 'getAssignees':
					$this->get_assignees();
					break;
				case 'getProjectBugForm':
					$this->get_project_bug_form();
					break;
				case 'getTags':
					$this->get_tags();
					break;
				case 'getURLs':
					$this->get_urls();
					break;
				case 'getQuickAssignees':
					$this->get_quick_assignees();
					break;
				case 'assignBug':
					$this->assign_bug();
					break;
				case 'getMoreTimelines':
					$this->get_more_timelines();
					break;
				case 'getChart':
					$this->get_chart();
					break;
			}
			exit();
		}
	}

	private function swap_status() {
		global $DB, $LANG;

		//find bug
		$Bugs = new Bugs();
		$data['bugID'] = $_GET['bugID'];
		$Bug = $Bugs->find($data, $limit=1);

		//swap status
		if ($Bug->bugStatus() == 'resolved') {
			$sql = "UPDATE exp_earwig_bugs
					SET bugStatus = 'open',
					bugFixedDate = NULL
					WHERE bugID = '".$Bug->id()."'";
		}
		else {
			$date = date('Y-m-d H:i:s');
			$sql = "UPDATE exp_earwig_bugs
					SET bugStatus = 'resolved',
					bugFixedDate = '".$date."'
					WHERE bugID = '".$Bug->id()."'";
		}
		$DB->query($sql);

		//refind bug
		$Bug = $Bugs->find($data, $limit=1);
		
		//add to timeline
		$Timelines = new Timelines();
		$Timelines->create('tag_blue_edit', $LANG->line('timeline_status_changed').' '.$Bug->bugStatus(), $Bug);

		//return new html
		echo $Bug->get_bug_status();
	}
	
	private function get_assignees() {
		global $DB, $DSP;
		
		//get project id
		$data['projectID'] = $_GET['projectID'];
		
		//if bug id is set, get bug
		if (isset($_GET['bugID'])) {
			$Bugs = new Bugs();
			$finddata['bugID'] = $_GET['bugID'];
			$Bug = $Bugs->find($finddata, 1);
			unset($finddata);
		}		
		
		//get project
		$Projects = new Projects();
		$finddata['projectID'] = $data['projectID'];
		$Project = $Projects->find($finddata, 1);
		
		//get assigned users
		$sql = "SELECT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_earwig_projects_members
				WHERE exp_members.member_id = exp_earwig_projects_members.member_id
				AND exp_earwig_projects_members.projectID = '".$data['projectID']."'";
		$query = $DB->query($sql);
		$select = '';
		if ($query->num_rows > 0) {
			foreach ($query->result as $member) {
				if ((isset($Bug) && is_object($Bug) && $Bug->bugAssignee() == $member['member_id']) || ($Project->projectDefaultAssignee() == $member['member_id'])) {
					$select .= $DSP->input_select_option($member['member_id'], $member['screen_name'], 'y');
				}
				else {
					$select .= $DSP->input_select_option($member['member_id'], $member['screen_name']);
				}
			}
			echo $select;
		}
		echo '';
	}
	
	private function get_project_bug_form() {
		global $DB, $DSP;
		
		//get project id
		$data['projectID'] = $_GET['projectID'];
		
		//find project
		$Projects = new Projects();
		$Project = $Projects->find($data, $limit=1);
		
		//return type
		echo $Project->projectBugForm();
	}
	
	private function get_tags() {
		global $DB;
		
		$sql = "SELECT DISTINCT tagValue FROM exp_earwig_tags";
		$query = $DB->query($sql);
		
		$tags = array();
		
		foreach ($query->result as $tag) {
			$tags[] = $tag['tagValue'];
		}
		
		echo json_encode($tags);
	}
	
	private function get_urls() {
		global $DB;
		
		$sql = "SELECT DISTINCT bugURL FROM exp_earwig_bugs";
		$query = $DB->query($sql);
		
		$urls = array();
		
		foreach ($query->result as $url) {
			$urls[] = $url['bugURL'];
		}
		
		echo json_encode($urls);
	}
	
	private function get_quick_assignees() {
		global $DB, $DSP;
		
		$Bugs = new Bugs();
		$finddata['bugID'] = $_GET['bugID'];
		$Bug = $Bugs->find($finddata, 1);
		unset($finddata);
		
		//get project
		$Projects = new Projects();
		$finddata['projectID'] = $Bug->projectID();
		$Project = $Projects->find($finddata, 1);
		
		//get assigned users
		$sql = "SELECT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_earwig_projects_members
				WHERE exp_members.member_id = exp_earwig_projects_members.member_id
				AND exp_earwig_projects_members.projectID = '".$Project->projectID()."'";
		$query = $DB->query($sql);
		if ($query->num_rows > 0) {
			$select = '<select name="bugAssignee" class="select bugAssigneeSelect" id="'.$Bug->id().'">';
			foreach ($query->result as $member) {
				if ($member['member_id'] == $Bug->bugAssignee()) {
					$select .= $DSP->input_select_option($member['member_id'], $member['screen_name'], 'y');
				}
				else {
					$select .= $DSP->input_select_option($member['member_id'], $member['screen_name']);
				}
			}
			$select .= '</select>&nbsp;<a href="#" class="editAssigneeLink"><img src="'.PATH_MOD_IMAGES.'/user_edit.png" class="icon"></a>';
			echo $select;
		}
		echo '';
	}
	
	private function assign_bug() {
		global $DB;
		
		$Bugs = new Bugs();
		$finddata['bugID'] = $_GET['bugID'];
		$Bug = $Bugs->find($finddata, 1);
		unset($finddata);
		
		$data['bugAssignee'] = $_GET['member_id'];
		
		if ($Bug->bugAssignee() != $data['bugAssignee']) {		
			$Bug->update($data, false);
		}
		
		echo $Bug->get_bug_assignee_link();
	}
	
	private function get_more_timelines() {
		global $DB, $SESS, $DSP;
		
		$Timelines = new Timelines();
		
		//get timeline count
		$sql = "SELECT * FROM exp_earwig_timelines
				WHERE bugID = '".$_GET['bugID']."'
				ORDER BY timelineDate ASC, timelineID ASC";
		$query = $DB->query($sql);
		$total_count = $query->num_rows;
		$total_count = $total_count - 5;
		
		//get rest of timelines
		$sql = "SELECT * FROM exp_earwig_timelines
				WHERE bugID = '".$_GET['bugID']."'
				ORDER BY timelineDate ASC, timelineID ASC
				LIMIT 0, ".$total_count;
		$query = $DB->query($sql);
		if ($query->num_rows > 0) {
			$Ts = $Timelines->return_instances($query->result);
		}
		else {
			echo '';
			return;
		}
		
		$i = 1;
		
		foreach ($Ts as $Timeline) {
			$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
			echo '<tr>';
				echo '<td width="2%" class="'.$class.'">';
					echo '<div class="default">';
						echo $Timeline->timelineIcon();
					echo '</div>';
				echo '</td>';
				echo '<td width="73%" class="'.$class.'">';
					echo '<div class="default">';
						echo $Timeline->get_timeline_details();
					echo '</div>';
				echo '</td>';
				echo '<td width="10%" class="'.$class.'">';
					echo '<div class="default">';
						echo $Timeline->get_timeline_date();
					echo '</div>';
				echo '</td>';
				echo '<td width="10%" class="'.$class.'">';
					echo '<div class="default">';
						echo $Timeline->get_timeline_member();
					echo '</div>';
				echo '</td>';
				if ($SESS->userdata('group_id') == '1') {
					echo '<td width="5%" class="'.$class.'">';
						echo '<div class="default">';
							echo $DSP->input_checkbox('toggle[]', $Timeline->id(), '', " id='delete_box_".$Timeline->id()."'");
						echo '</div>';
					echo '</td>';
				}
			echo '</tr>';
		}
	}
	
	public function get_chart() {
		//get project
		$Projects = new Projects();
		$data['projectID'] = $_GET['id'];
		$Project = $Projects->find($data, 1);
		
		$Chart = new Chart($Project);
		
		$member_id = $_GET['member_id'];
		$start = $_GET['start'];
		
		if ($member_id == '' && $start == '') {
			echo $Chart->view_init();
			return;
		}
		
		echo $Chart->view_specific($start, $member_id);
	}

}

?>