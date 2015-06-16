<?php

/*
=====================================================
Bug Tracker Module - by Nine Four Ltd.
-----------------------------------------------------
http://ninefour.co.uk/labs/
-----------------------------------------------------
Copyright (c) 2009 Nine Four Ltd.
=====================================================
File: mcp.earwig.php
-----------------------------------------------------
Compatibility: EE 1.6 + jQuery 1.3
-----------------------------------------------------
Purpose: Keep track of bugs within a project
=====================================================
*/

//define module constants
define("MODULE_SLUG", "earwig");
define("PATH_MOD_IMAGES", '/admin/modules/earwig/img');

if (!defined('EXT')) {
    exit('Invalid file request');
}

//autoload magic function automatically include class files if they are referenced
function __autoload($name) {
	if (file_exists(PATH_MOD.MODULE_SLUG.'/lib/'.$name.'.class.php')) {
  		require_once(PATH_MOD.MODULE_SLUG.'/lib/'.$name.'.class.php');
	}
}

class Earwig_CP {
	
	//module vars
	private $module_name = 'Earwig';
	private $module_version = '1.0';
	private $backend_bool = 'y';


	//constructor
	public function __construct($switch = true) {
		global $IN;

		//ajax requests
		$Ajax = new Ajax();

		//get switch val
		$switchval = $IN->GBL('P');

		//check versions
		Util::check_php_version();
		Util::check_jquery_version();

		//switch on action parameter
		if ($switch) {
			switch($switchval) {
				case 'inbox_list':
					$this->inbox_list();
					break;
				case 'bug_list':
					$this->bug_list();
					break;
				case 'inbox_delete_confirm':
				case 'bug_delete_confirm':
					$this->bug_delete_confirm();
					break;
				case 'bug_delete':
					$this->bug_delete();
					break;
				case 'bug_add':
				case 'inbox_add':
					$this->bug_add();
					break;
				case 'bug_edit':
					$this->bug_edit();
					break;
				case 'bug_view':
					$this->bug_view();
					break;
				case 'get_bugs_from_project':
					$this->get_bugs_from_project();
					break;
				case 'bug_filter':
					$this->bug_filter();
					break;
				case 'project_list':
					$this->project_list();
					break;
				case 'project_delete_confirm':
					$this->project_delete_confirm();
					break;
				case 'project_delete':
					$this->project_delete();
					break;
				case 'project_add':
					$this->project_add();
					break;
				case 'project_edit':
					$this->project_edit();
					break;
				case 'project_assign_members':
					$this->project_assign_members();
					break;
				case 'project_view':
					$this->project_view();
					break;
				case 'project_export':
					$this->project_export();
					break;
				case 'comment_view':
					$this->comment_view();
					break;
				case 'comment_add':
					$this->comment_add();
					break;
				case 'comment_delete_confirm':
					$this->comment_delete_confirm();
					break;
				case 'comment_delete':
					$this->comment_delete();
					break;
				case 'tag_list':
					$this->tag_list();
					break;
				case 'tag_view':
					$this->tag_view();
					break;
				case 'tag_edit':
					$this->tag_edit();
					break;
				case 'tag_delete_confirm':
					$this->tag_delete_confirm();
					break;
				case 'tag_delete':
					$this->tag_delete();
					break;
				case 'attachment_add':
					$this->attachment_add();
					break;
				case 'attachment_delete_confirm':
					$this->attachment_delete_confirm();
					break;
				case 'attachment_delete':
					$this->attachment_delete();
					break;
				case 'attachment_view':
					$this->attachment_view();
					break;
				case 'timeline_delete_confirm':
					$this->timeline_delete_confirm();
					break;
				case 'timeline_delete':
					$this->timeline_delete();
					break;
				case 'timeline_add':
					$this->timeline_add();
					break;
				case 'inbox_filter':
					$this->inbox_filter();
					break;
				default:
					$this->inbox_list();
					break;
			}
		}
	}

	private function inbox_list() {
		//get user's bugs
		$Bugs = new Bugs();
		$Paging = new Paging();
		$InboxBugs = $Bugs->get_inbox_bugs($Paging);
		$Bugs->noun = 'inbox';
		$Bugs->list_objects(false, false, $InboxBugs, $Paging);
	}
	
	private function inbox_filter() {
		//find bugs
		$Bugs = new Bugs();
		$Paging = new Paging();
		
		//pagination search link
		if (isset($_GET['encsearch'])) {
			$_POST = unserialize(base64_decode($_GET['encsearch']));
		}
		
		$Bugs->noun = 'inbox';
		$FilterBugs = $Bugs->get_filtered_bugs($_POST, true, $Paging);
		if (count($FilterBugs) == 1) {
			$FilterBugs[0]->view();
		}
		else {
			$Bugs->list_objects(false, false, $FilterBugs, $Paging);
		}
	}

	private function bug_list() {
		//get all bugs
		$Bugs = new Bugs();
		$Paging = new Paging();		
		$AllBugs = $Bugs->find_all($Paging);
		$Bugs->list_objects(false, false, $AllBugs, $Paging);
	}

	private function bug_delete_confirm() {
		$Bugs = new Bugs();
		$Bugs->delete_confirm();
	}

	private function bug_delete() {
		$Bugs = new Bugs();
		$Bugs->delete();
	}

	private function bug_add() {
		$Bugs = new Bugs();
		if (!empty($_POST)) {
			$Bugs->create($_POST, $once = true);
		}
		else {
			$Bugs->create_form();
		}
	}

	private function bug_edit() {
		global $IN;

		//find bug
		$Bugs = new Bugs();
		$data['bugID'] = $IN->GBL('id');
		$Bug = $Bugs->find($data, $limit=1);

		if (!empty($_POST)) {
			$Bug->update($_POST, true);
		}
		else {
			$Bug->edit_form();
		}
	}

	private function bug_view() {
		global $IN;

		//find bug
		$Bugs = new Bugs();
		$data['bugID'] = $IN->GBL('id');
		$Bug = $Bugs->find($data, $limit=1);

		$Bug->view();
	}
	
	private function bug_filter() {
		//find bugs
		$Bugs = new Bugs();
		$Paging = new Paging();
		
		//pagination search link
		if (isset($_GET['encsearch'])) {
			$_POST = unserialize(base64_decode($_GET['encsearch']));
		}

		$FilterBugs = $Bugs->get_filtered_bugs($_POST, false, $Paging);
		if (count($FilterBugs) == 1) {
			$FilterBugs[0]->view();
		}
		else {
			$Bugs->list_objects(false, false, $FilterBugs, $Paging);
		}
	}

	private function project_list() {
		$Projects = new Projects();
		$AllProjects = $Projects->get_assigned_projects();
		$Projects->list_objects(false, false, $AllProjects);
	}

	private function project_delete_confirm() {
		$Projects = new Projects();
		$Projects->delete_confirm();
	}

	private function project_delete() {
		$Projects = new Projects();
		$Projects->delete();
	}

	private function project_add() {
		$Projects = new Projects();
		if (!empty($_POST)) {
			$Projects->create($_POST, $once = true);
		}
		else {
			$Projects->create_form();
		}
	}

	private function project_edit() {
		global $IN;

		//find team
		$Projects = new Projects();
		$data['projectID'] = $IN->GBL('id');
		$Project = $Projects->find($data, $limit=1);

		if (!empty($_POST)) {
			$Project->update($_POST);
		}
		else {
			$Project->edit_form();
		}
	}

	private function project_assign_members() {
		global $IN;

		//find project
		$Projects = new Projects();
		$data['projectID'] = $IN->GBL('id');
		$Project = $Projects->find($data, $limit=1);

		if (!empty($_POST)) {
			if ($Project->assign_members($_POST)) {
				$AllProjects = $Projects->get_assigned_projects();
				$Projects->list_objects(false, false, $AllProjects);
			}
		}
		else {
			$Project->assign_members_form();
		}
	}

	private function project_view() {
		global $IN;

		//find bug
		$Projects = new Projects();
		$data['projectID'] = $IN->GBL('id');
		$Project = $Projects->find($data, $limit=1);

		$Project->view();
	}
	
	private function project_export() {
		global $IN, $DB;

		$Util = new Util();
		$data['projectID'] = $IN->GBL('id');
		
		//get results
		$sql = "SELECT b.bugID, b.bugTitle, b.bugDate, m1.screen_name as bugCreator, m2.screen_name as bugAssignee, b.bugFixedDate, b.bugSeverity, b.bugStatus
				FROM exp_earwig_bugs b
				LEFT JOIN exp_members m1 ON b.bugCreator = m1.member_id
				LEFT JOIN exp_members m2 ON b.bugAssignee = m2.member_id 
				WHERE b.projectID = '".$data['projectID']."'
				ORDER BY b.bugDate ASC";
		$query = $DB->query($sql);
		$fields = $query->result;
		
		$titles = array();
		$titles[] = "ID";
		$titles[] = "Title";
		$titles[] = "Date";
		$titles[] = "Creator";
		$titles[] = "Assignee";
		$titles[] = "Fixed Date";
		$titles[] = "Severity";
		$titles[] = "Status";
		
		//print_r($fields);
		//exit;
		
		$Util->download_csv($titles, $fields, "earwig_issues_project_".$data['projectID']."");

	}

	private function comment_view() {
		global $IN;

		//find comments
		$Comments = new Comments();
		$data['bugID'] = $IN->GBL('id');
		$AllComments = $Comments->find($data);

		//list
		$Comments->list_objects(false, false, $AllComments, $data['bugID']);
	}

	private function comment_add() {
		global $IN;

		$Comments = new Comments();
		if (!empty($_POST)) {
			$Comments->create($_POST);
		}
		else {
			$Comments->create_form(false, false, $IN->GBL('id'));
		}
	}

	private function comment_delete_confirm() {
		global $IN;

		$Comments = new Comments();
		$Comments->delete_confirm($IN->GBL('id'));
	}

	private function comment_delete() {
		global $IN;

		$Comments = new Comments();
		$Comments->delete($IN->GBL('id'));
	}

	private function tag_list($msg = false, $response = false) {
		$Tags = new Tags();
		$AllTags = $Tags->find_all();
		$Tags->list_objects($msg, $response, $AllTags);
		$AlphabeticalTags = $Tags->find_alphabetical();
		$Tags->tag_cloud($AlphabeticalTags);
	}

	private function tag_view() {
		global $IN;

		$Tags = new Tags();
		$data['tagID'] = $IN->GBL('id');
		$Tag = $Tags->find($data, $limit=1);

		//for search form
		$_POST['keywords'] = $Tag->tagValue();

		$Bugs = new Bugs();
		$Paging = new Paging();
		$BugsWithTag = $Bugs->get_bugs_with_tag($Tag, $Paging);
		$Bugs->list_objects(false, false, $BugsWithTag, $Paging);
	}
	
	private function tag_edit() {
		global $IN, $LANG;

		//find tag
		$Tags = new Tags();
		$data['tagID'] = $IN->GBL('id');
		$Tag = $Tags->find($data, $limit=1);

		if (!empty($_POST)) {
			if ($Tag->update($_POST)) {
				$this->tag_list($LANG->line('tag_edited'), true);
			}
		}
		else {
			$Tag->edit_form();
		}
	}
	
	private function tag_delete_confirm() {
		global $IN;

		$Tags = new Tags();
		$Tags->delete_confirm($IN->GBL('id'));
	}

	private function tag_delete() {
		global $IN, $LANG;

		$Tags = new Tags();
		$Tags->delete($IN->GBL('id'));
		$this->tag_list($LANG->line('tag_deleted'), true);
	}
	
	private function attachment_add() {
		global $IN;

		$Attachments = new Attachments();
		if (!empty($_POST)) {
			$Attachments->create($_POST);
		}
		else {
			$Attachments->create_form(false, false, $IN->GBL('id'));
		}
	}

	private function attachment_delete_confirm() {
		global $IN;

		$Attachments = new Attachments();
		$Attachments->delete_confirm($IN->GBL('id'));
	}

	private function attachment_delete() {
		global $IN;

		$Attachments = new Attachments();
		$Attachments->delete($IN->GBL('id'));
	}
	
	private function attachment_view() {
		global $IN;

		//find attachment
		$Attachments = new Attachments();
		$data['attachmentID'] = $IN->GBL('id');
		$Attachment = $Attachments->find($data, $limit=1);

		$Attachment->download();
	}
	
	private function timeline_delete_confirm() {
		global $IN;

		$Timelines = new Timelines();
		$Timelines->delete_confirm($IN->GBL('id'));
	}

	private function timeline_delete() {
		global $IN;

		$Timelines = new Timelines();
		$Timelines->delete($IN->GBL('id'), false);
	}
	
	private function timeline_add() {
		global $IN;

		$Timelines = new Timelines();
		if (!empty($_POST)) {
			$Timelines->create_from_form($_POST);
		}
		else {
			Factory::fourohfour();
		}
	}
	
	public function bug_view_remote() {
		global $IN, $FNS, $PREFS;
		
		//attempt to redirect to control panel location
		$url = $PREFS->ini('cp_url');
		$url .= '?S=0&C=modules&M=earwig&P=bug_view&id='.$IN->GBL('id');
		
		$FNS->redirect($url);
	}

	public function project_view_remote() {
		global $IN, $FNS, $PREFS;
		
		//attempt to redirect to control panel location
		$url = $PREFS->ini('cp_url');
		$url .= '?S=0&C=modules&M=earwig&P=project_view&id='.$IN->GBL('id');
		
		$FNS->redirect($url);
	}

	public function earwig_module_install() {
		global $DB;

		//create bugs table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_bugs` (
				 `bugID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `bugTitle` TEXT,
				 `bugDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				 `bugURL` TEXT,
				 `bugExpected` TEXT,
				 `bugActually` TEXT,
				 `bugDoing` TEXT,
				 `bugCreator` INT(10),
				 `bugAssignee` INT(10),
				 `bugStatus` ENUM('open', 'resolved', 'closed') DEFAULT 'open',
				 `bugSeverity` ENUM('critical', 'high', 'normal', 'low', 'trivial') DEFAULT 'normal',
				 `bugFixer` INT(10),
				 `bugFixedDate` DATETIME,
				 `projectID` INT(10));";

		//create tags table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_tags` (
				 `tagID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `tagValue` TEXT,
				 `bugID` INT(10));";

		//create projects table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_projects` (
				 `projectID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `projectTitle` TEXT,
				 `projectDescription` TEXT,
				 `projectStatus` ENUM('active', 'inactive'),
				 `projectLeader` INT(10),
				 `projectBugForm` ENUM('simple', 'advanced') DEFAULT 'advanced',
				 `projectColour` VARCHAR(7),
				 `projectEmailEnabled` BOOL DEFAULT TRUE,
				 `projectUploadID` INT(10),
				 `projectDefaultAssignee' INT(10));";

		//create comments table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_comments` (
				 `commentID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `commentValue` TEXT,
				 `commentOwner` INT(10),
				 `commentDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				 `bugID` INT(10));";
		
		//create uploads table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_attachments` (
				 `attachmentID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `attachmentName` TEXT,
				 `bugID` INT(10),
				 `attachmentDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				 `attachmentLocation` TEXT,
				 `attachmentMimeType` TEXT,
				 `attachmentOwner` INT(10));";
				 
		//create timelines table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_timelines` (
				 `timelineID` INT(10) AUTO_INCREMENT PRIMARY KEY,
				 `timelineIcon` TEXT,
				 `bugID` INT(10),
				 `timelineDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				 `timelineDetails` TEXT,
				 `timelineMember` INT(10),
				 `commentID` INT(10) DEFAULT NULL,
				 `attachmentID` INT(10) DEFAULT NULL);";

		//create projects_members table
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_earwig_projects_members` (
				`projectID` INT(10),
				`member_id` INT(10));";

		//insert into modules
        $sql[] = "INSERT INTO exp_modules(module_id, module_name, module_version, has_cp_backend)
				  VALUES ('', '".$this->module_name."', '".$this->module_version."', '".$this->backend_bool."')";
				  
		//add bug view action
		$sql[] = "INSERT INTO exp_actions (action_id, class, method) 
				  VALUES ('', 'Earwig_CP', 'bug_view_remote')";

		//add project view action
		$sql[] = "INSERT INTO exp_actions (action_id, class, method) 
				  VALUES ('', 'Earwig_CP', 'project_view_remote')";

		//perform queries
        foreach ($sql as $query) {
            $DB->query($query);
        }

        return true;
    }

    public function earwig_module_deinstall() {
        global $DB;

        //create sql query array
        $sql[] = "DELETE FROM exp_modules WHERE module_name = '".$this->module_name."'";
        $sql[] = "DELETE FROM exp_actions WHERE class = 'Earwig_CP' AND method = 'bug_view_remote' LIMIT 1";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_bugs";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_projects";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_comments";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_attachments";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_tags";
        $sql[] = "DROP TABLE IF EXISTS exp_earwig_projects_members";

		//perform queries
        foreach ($sql as $query) {
            $DB->query($query);
        }

        return true;
    }

}

?>