<?php

class Factory {

	protected $db;
	//singular values
	public $navigation_tabs = array('inbox', 'bug', 'project', 'tag');

	/**
	 *
	 */


	function __construct() {
		global $DB;
		$this->db = $DB;
	}


	/**
	 *
	 *
	 * @param unknown $data  (optional)
	 * @param unknown $limit (optional)
	 * @return unknown
	 */
	public function find($data=false, $limit=false) {
		//create query
		$sql = "SELECT * FROM " . $this->table;
		if ($data == true) {
			$sql .= " WHERE ";
			$i = 0;
			foreach ($data as $key=>$value) {
				if ($i > 0) {
					$sql .= " AND ";
				}
				$sql .= $key . "=". $this->db->escape_str($value);
				$i++;
			}
			if ($limit!=false) {
				$sql .= " LIMIT ".$limit;
			}
		}

		//perform query
		$query = $this->db->query($sql);
		$result = $query->result;

		//return objects depending on result
		if (!empty($result)) {
			if (count($result) == 1 && $limit == 1) {
				return new $this->classname($result[0]);
			}
			else {
				return $this->return_instances($result);
			}
		}
		else {
			return false;
		}
	}


	/**
	 *
	 *
	 * @param unknown $data
	 * @param unknown $once (optional)
	 * @return unknown
	 */
	public function create($data, $once=false, $view = true) {
		global $LANG;

		//potentially check for existing
		if ($once==true) {
			$sql = "SELECT * FROM ".$this->table."
					WHERE ".$this->name_field." = '".$data[$this->name_field]."'";
			$query = $this->db->query($sql);
			$result = $query->result;
			if ($query->num_rows > 0) {
				return $this->create_form($LANG->line($this->noun.'_exists'), false);
			}
		}

		//no existing, create new
		//create sql
		$columns = array();
		$values = array();
		foreach ($data as $key=>$value) {
			$columns[] = $key;
			$values[] = "'".$value."'";
		}
		$sql = "INSERT INTO ".$this->table."(".implode(",", $columns).")
				VALUES(".implode(",", $values).")";

		//insert into db
		$query = $this->db->query($sql);
		$new = $this->db->insert_id;
		if ($new) {
			//find object and return it
			$sql = "SELECT * FROM ".$this->table."
                    WHERE ".$this->pk."=".$this->db->escape_str($new) ."
                    LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result;
			if ($result) {
				$Obj = new $this->classname($result[0]);
				if ($view == true) {
					$Obj->view($LANG->line($this->noun.'_added'), true);
				}
				return $Obj;
			}
		}
	}


	/**
	 *
	 *
	 * @param unknown $rows
	 * @return unknown
	 */
	public function return_instances($rows) {
		$out = array();
		//return array of multiple objects
		foreach ($rows as $row) {
			$out[] = new $this->classname($row);
		}
		return $out;
	}


	//ExpressionEngine-specific methods

	//Title, crumb links, action
	/**
	 *
	 *
	 * @param unknown $msg       (optional)
	 * @param unknown $response  (optional)
	 * @param unknown $action    (optional)
	 * @param unknown $heading   (optional)
	 * @param unknown $highlight (optional)
	 * @param unknown $extraid   (optional)
	 * @param unknown $Obj       (optional)
	 */
	public function page_header($msg = false, $response = false, $action = true, $heading = false, $highlight = false, $extraid = false, $Obj = false, $nav = true) {
		global $DSP, $LANG, $DB, $PREFS, $IN, $SESS, $DB;

		//main js
		$DSP->extra_header .= '<script type="text/javascript">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/main.js');
		$DSP->extra_header .= '</script>';
		//main css
		$DSP->extra_header .= '<style type="text/css">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/css/default.css');
		$DSP->extra_header .= '</style>';

		//HTML Title and Navigation Crumblinks
		$DSP->title = $LANG->line('module_name');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG, $LANG->line('module_name'));

		if ($action == true) {
			if ($extraid == false) {
				if ($Obj == false) {
					$DSP->right_crumb($LANG->line($this->noun.'_add'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add');
				}
			}
			else {
				if ($Obj == false) {
					$DSP->right_crumb($LANG->line($this->noun.'_add'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add'.AMP.'id='.$extraid);
				}
				else {
					if (get_class($Obj) == 'Bug') {
						$req = $IN->GBL('P');
						if ($req != 'bug_edit') {
							//get project leader id
							$project_leader = 0;
							$sql = "SELECT exp_earwig_projects.projectLeader FROM exp_earwig_bugs, exp_earwig_projects
									WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
									AND exp_earwig_projects.projectID = '".$Obj->projectID()."'";
							$query = $DB->query($sql);
							if ($query->num_rows > 0) {
								$project_leader = $query->result[0]['projectLeader'];
							}

							//get member id
							$member_id = $SESS->userdata('member_id');

							if ($Obj->bugCreator() == $member_id || $Obj->bugAssignee() == $member_id || $project_leader == $member_id) {
								$DSP->right_crumb($LANG->line($this->noun.'_edit'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_edit'.AMP.'id='.$extraid);
							}
						}
						elseif ($req == 'bug_edit') {
							$DSP->right_crumb($LANG->line($this->noun.'_view'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_view'.AMP.'id='.$extraid);
						}
					}
					if (get_class($Obj) == 'Project') {
						$req = $IN->GBL('P');
						if ($req != 'project_edit') {
							//get member id
							$member_id = $SESS->userdata('member_id');

							if ($Obj->projectLeader() == $member_id || $SESS->userdata('group_id') == '1') {
								$DSP->right_crumb($LANG->line($this->noun.'_edit'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_edit'.AMP.'id='.$extraid);
							}
						}
						elseif ($req == 'project_edit') {
							$DSP->right_crumb($LANG->line($this->noun.'_view'), BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_view'.AMP.'id='.$extraid);
						}
					}
				}
			}
		}

		if ($nav == true) {
			//navigation, if required
			if (count($this->navigation_tabs) > 1) {
				$navigation = array();
				$width = round(100 / count($this->navigation_tabs));
	
				foreach ($this->navigation_tabs as $tab) {
					if ($tab != 'inbox') {
						$array = array ( 'class' => 'altTabs',
							'width' => $width.'%',
							'text' => $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$tab.'_list', $LANG->line($tab.'_plural'))
						);
					}
					elseif ($tab == 'inbox') {
						$Bugs = new Bugs();
						$array = array ( 'class' => 'altTabs',
							'width' => $width.'%',
							'text' => $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$tab.'_list', $LANG->line($tab.'_plural').' (<span class="inbox_count">'.$Bugs->get_inbox_bugs_count().'</span> '.$LANG->line('bug_plural').')')
						);
					}
					$navigation[$tab] = $array;
				}
	
				if ($highlight != false && isset($navigation[$highlight])) {
					$navigation[$highlight]['class'] = "altTabSelected";
				}
	
				$DSP->body .= $DSP->table_open(array('width' => '100%')).$DSP->table_row($navigation).$DSP->table_close().BR;
			}
		}

		//Print msg if set
		if ($msg != false) {
			if ($response == true) {
				$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg)).BR;
			}
			else {
				$DSP->body .= $DSP->qdiv('errorBox', $DSP->qdiv('alertHeading', $msg)).BR;
			}
		}

		//Page Heading
		if ($heading != false) {
			$DSP->body .= $DSP->heading($LANG->line($heading));
		}
	}


	//Filter
	/**
	 *
	 */
	public function filter() {
		global $DSP, $LANG;

		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line($this->noun.'_filter'));
		$DSP->body .= $DSP->div('box');

		//filter table
		$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_filter'));
		$DSP->body .= $DSP->table_open(array('class'=>'', 'width'=>'100%'));

		//filter
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('itemWrapper');
		foreach ($this->filter_fields as $name=>$options) {
			$select = $DSP->input_select_header($name);
			if ($name == 'per_page') {
				$select .= $DSP->input_select_option('', $LANG->line($name));
			}
			else {
				$select .= $DSP->input_select_option('', $LANG->line('filter_by').' '.$LANG->line($name));
			}
			foreach ($options as $option=>$value) {
				if (isset($_POST[$name]) && $_POST[$name] == $value) {
					$select .= $DSP->input_select_option($value, $option, 'y');
				}
				else {
					$select .= $DSP->input_select_option($value, $option);
				}
			}
			$select .= $DSP->input_select_footer();
			$DSP->body .= $select.'&nbsp;&nbsp;';
		}
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();

		//search
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('itemWrapper');
		$DSP->body .= $DSP->div('default');
		$DSP->body .= '<label for="keywords">'.$LANG->line('keywords').'</label>&nbsp;&nbsp;';
		if (isset($_POST['keywords'])) {
			$DSP->body .= $DSP->input_text('keywords', $_POST['keywords'], '40', '200', 'input', '200px').'&nbsp;';
		}
		else {
			$DSP->body .= $DSP->input_text('keywords', '', '40', '200', 'input', '200px').'&nbsp;';
		}
		$DSP->body .= $DSP->input_submit($LANG->line('search'), '');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();

		$DSP->body .= $DSP->table_close();
		$DSP->body .= $DSP->form_close();
		$DSP->body .= $DSP->div_c();
	}


	//Object list
	/**
	 *
	 *
	 * @param unknown $msg        (optional)
	 * @param unknown $response   (optional)
	 * @param unknown $Objs       (optional)
	 * @param unknown $extraid    (optional)
	 * @param unknown $showheader (optional)
	 * @param unknown $Paging     (optional)
	 */
	public function list_objects($msg = false, $response = false, $Objs = false, $extraid = false, $showheader = true, $Paging = false) {
		global $DSP, $LANG, $SESS;

		//get member_id
		$member_id = $SESS->userdata('member_id');

		//get all objects
		if ($Objs == false && !is_array($Objs)) {
			$AllObjects = $this->find();
		}
		else {
			$AllObjects = $Objs;
		}

		if ($showheader == true) {
			//header
			if ($this->noun == 'project' && $SESS->userdata('group_id') == '1') {
				$this->page_header($msg, $response, true, $this->noun.'_plural', $this->noun, $extraid);
			}
			elseif ($this->noun == 'project' && $SESS->userdata('group_id') != '1') {
				$this->page_header($msg, $response, false, $this->noun.'_plural', $this->noun, $extraid);
			}
			else {
				$this->page_header($msg, $response, true, $this->noun.'_plural', $this->noun, $extraid);
			}
		}

		//filter
		if (isset($this->filter_fields) && is_array($this->filter_fields) && !empty($this->filter_fields) && is_array($AllObjects) && count($AllObjects) > 0) {
			$this->filter();
		}
		elseif (isset($_POST['keywords'])) {
			$this->filter();
		}

		//no objects
		if (!is_array($AllObjects) || count($AllObjects) == 0) {
			$DSP->body .= $DSP->div('box');
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->heading($LANG->line('no_'.$this->noun_plural), 5));
			if ($LANG->line($this->noun.'_add') != '' && $LANG->line($this->noun.'_add') != 'Add comment' && $LANG->line($this->noun.'_add') != 'Add an attachment') {
				if ($extraid == false) {
					$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor( BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add', $LANG->line($this->noun.'_add')));
				}
				else {
					$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->anchor( BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add'.AMP.'id='.$extraid, $LANG->line($this->noun.'_add')));
				}
			}
			$DSP->body .= $DSP->div_c();
		}
		else {
			//table header
			$DSP->body .= $DSP->toggle();
			$DSP->body_props .= ' onload="magic_check()" ';
			$DSP->body .= $DSP->magic_checkboxes();

			if ($extraid == false) {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_delete_confirm', 'id'=>'target'));
			}
			else {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_delete_confirm'.AMP.'id='.$extraid, 'id'=>'target'));
			}

			$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));

			//create columns
			$columns = array();
			foreach ($this->list_columns as $name=>$options) {
				$columns[] = array('text'=>$LANG->line($name), 'class'=>'tableHeadingAlt');
			}

			//delete column
			if ($SESS->userdata('group_id') == '1') {
				$columns[] = array('text'=>$LANG->line('delete'), 'class'=>'tableHeadingAlt');
			}
			else {

			}

			//output table header row
			$DSP->body .= $DSP->table_row($columns);

			//do table contents
			$i = 0;
			$pk = $this->pk;
			$deletecount = 0;
			foreach ($AllObjects as $Object) {
				//get project leader id
				$project_leader = 0;
				if ($this->noun == 'bug' || $this->noun == 'inbox' || $this->noun == 'comment' || $this->noun == 'attachment') {
					if ($this->noun == 'bug' || $this->noun == 'inbox') {
						$sql = "SELECT exp_earwig_projects.projectLeader FROM exp_earwig_bugs, exp_earwig_projects
								WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
								AND exp_earwig_projects.projectID = '".$Object->projectID()."'";
					}
					elseif ($this->noun == 'comment' || $this->noun == 'attachment') {
						$sql = "SELECT exp_earwig_projects.projectLeader FROM exp_earwig_bugs, exp_earwig_projects
								WHERE exp_earwig_bugs.projectID = exp_earwig_projects.projectID
								AND exp_earwig_bugs.bugID = '".$Object->bugID()."'";
					}
					$query = $this->db->query($sql);
					if ($query->num_rows > 0) {
						$project_leader = $query->result[0]['projectLeader'];
					}
				}

				//init row
				$row = array();
				$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

				foreach ($this->list_columns as $name=>$options) {
					//no link, no method
					if ($options['link'] == null && $options['method'] == null) {
						$row[] = array('text'=>$DSP->qdiv('default', $Object->$name()), 'class'=>$class, 'width'=>$options['width']);
					}
					//link
					elseif ($options['link'] != null && $options['method'] == null) {
						switch ($this->noun) {
						case 'inbox':
						case 'bug':
							if ($name == 'edit') {
								if ($Object->bugCreator() == $member_id || $project_leader == $member_id || $Object->bugAssignee() == $member_id || $SESS->userdata('group_id') == '1') {
									$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $LANG->line($options['link']))), 'class'=>$class, 'width'=>$options['width']);
								}
								else {
									$row[] = array('text'=>'', 'class'=>$class, 'width'=>$options['width']);
								}
							}
							break;
						case 'project':
							if ($name == 'export') {
								// Don't know that we really need to restrict this?
								//if ($Object->projectLeader() == $member_id || $SESS->userdata('group_id') == '1') {
									$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $LANG->line($options['link']))), 'class'=>$class, 'width'=>$options['width']);
								//}
								//else {
								//	$row[] = array('text'=>'', 'class'=>$class, 'width'=>$options['width']);
								//}
							}
							if ($name == 'edit') {
								if ($Object->projectLeader() == $member_id || $SESS->userdata('group_id') == '1') {
									$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $LANG->line($options['link']))), 'class'=>$class, 'width'=>$options['width']);
								}
								else {
									$row[] = array('text'=>'', 'class'=>$class, 'width'=>$options['width']);
								}
							}
							break;
						default:
							$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $LANG->line($options['link']))), 'class'=>$class, 'width'=>$options['width']);
							break;
						}
					}
					//method
					elseif ($options['link'] == null && $options['method'] != null) {
						switch ($this->noun) {
						case 'inbox':
						case 'bug':
							if ($name == 'bugStatus') {
								if ($Object->bugCreator() == $member_id || $Object->bugAssignee() == $member_id || $project_leader == $member_id || $SESS->userdata('group_id') == '1') {
									$row[] = array('text'=>$DSP->qdiv('default', $Object->$options['method']()), 'class'=>$class, 'width'=>$options['width']);
								}
								else {
									$row[] = array('text'=>$DSP->qdiv('default', $Object->get_bug_status_no_link()), 'class'=>$class, 'width'=>$options['width']);
								}
							}
							else {
								$row[] = array('text'=>$DSP->qdiv('default', $Object->$options['method']()), 'class'=>$class, 'width'=>$options['width']);
							}
							break;
						default:
							$row[] = array('text'=>$DSP->qdiv('default', $Object->$options['method']()), 'class'=>$class, 'width'=>$options['width']);
							break;
						}
					}
					//link and method
					elseif ($options['link'] != null && $options['method'] != null) {
						switch ($this->noun) {
						case 'project':
							if ($name == 'assignUsers') {
								if ($Object->projectLeader() == $member_id || $SESS->userdata('group_id') == '1') {
									$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $Object->$options['method']())), 'class'=>$class, 'width'=>$options['width']);
								}
								else {
									$row[] = array('text'=>$DSP->qdiv('defaultBold', $Object->$options['method']()), 'class'=>$class, 'width'=>$options['width']);
								}
							}
							else {
								$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $Object->$options['method']())), 'class'=>$class, 'width'=>$options['width']);
							}
							break;
						default:
							$row[] = array('text'=>$DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$options['link'].AMP.'id='.$Object->id(), $Object->$options['method']())), 'class'=>$class, 'width'=>$options['width']);
							break;
						}
					}
				}

				//add var for checking for delete submit
				if ($this->noun == 'tag') {
					if ($SESS->userdata('group_id') == '1') {
						//add delete column
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
				}
				elseif ($this->noun == 'bug' || $this->noun == 'inbox') {
					if ($SESS->userdata('group_id') == '1') {
						//add delete column
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
					else {
						//$row[] = array('text'=>'', 'class'=>$class, 'width'=>'5%');
					}
				}
				elseif ($this->noun == 'project') {
					if ($SESS->userdata('group_id') == '1') {
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
					else {
						//$row[] = array('text'=>'', 'class'=>$class, 'width'=>'5%');
					}
				}
				elseif ($this->noun == 'comment') {
					if ($SESS->userdata('group_id') == '1') {
						//add delete column
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
					else {
						//$row[] = array('text'=>'', 'class'=>$class, 'width'=>'5%');
					}
				}
				elseif ($this->noun == 'attachment') {
					if ($SESS->userdata('group_id') == '1') {
						//add delete column
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
					else {
						//$row[] = array('text'=>'', 'class'=>$class, 'width'=>'5%');
					}
				}
				elseif ($this->noun == 'timeline') {
					if ($SESS->userdata('group_id') == '1') {
						//add delete column
						$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
						$deletecount++;
					}
					else {
						//$row[] = array('text'=>'', 'class'=>$class, 'width'=>'5%');
					}
				}
				else {
					$row[] = array('text'=>$DSP->input_checkbox('toggle[]', $Object->$pk(), '', " id='delete_box_".$Object->$pk()."'"), 'class'=>$class, 'width'=>'5%');
					$deletecount++;
				}

				//output
				$DSP->body .= $DSP->table_row($row);
			}
			
			//finish table
			//add delete button
			if ($deletecount > 0) {
				$class = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				$DSP->body .= $DSP->table_row(array(
						array(
							'text'  => '&nbsp',
							'class'  => $class,
							'colspan' => count($this->list_columns)
						),
						array(
							'text'  => $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('delete'))),
							'class'  => $class,
							'colspan' => '1'
						)
					)
				);
			}

			//close table and form
			$DSP->body .= $DSP->table_close();
			$DSP->body .= $DSP->form_close();

			//pagination
			if ($Paging != false) {
				$DSP->body .= $this->pagination($Paging);
			}
		}
	}


	//Objects delete confirm
	/**
	 *
	 *
	 * @param unknown $extraid (optional)
	 */
	public function delete_confirm($extraid = false) {
		global $DSP, $LANG;

		//add header
		$this->page_header(false, false, false, $this->noun.'_plural_delete', $this->noun);

		//Delete confirm form
		if ($extraid == false) {
			$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_delete'));
		}
		else {
			$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_delete'.AMP.'id='.$extraid));
		}

		//create query
		$sql = "SELECT ".$this->name_field." FROM ".$this->table." WHERE ".$this->pk." IN (";
		$i = 0;
		foreach ($_POST as $key => $val) {
			if (strstr($key, 'toggle') and ! is_array($val)) {
				$DSP->body .= $DSP->input_hidden('delete[]', $val);
				$i++;
				$sql .= "'".$this->db->escape_str($val)."',";
			}
		}
		$sql = substr($sql, 0, -1);
		$sql .= ")";

		//get objects to be deleted
		$query = $this->db->query($sql);

		//output rest of form and make sure user is aware of permanent nature of action
		$DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('delete'));
		$DSP->body .= $DSP->div('box');
		$DSP->body .= $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $LANG->line('delete_question')));

		//output object titles to be deleted
		foreach ($query->result as $row) {
			$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->qdiv('highlight', NBS.NBS.NBS.NBS.$row[$this->name_field]));
		}

		//warn user and add button
		$DSP->body .= $DSP->qdiv('alert', BR.$LANG->line('action_can_not_be_undone'));
		$DSP->body .= $DSP->qdiv('', BR.$DSP->input_submit($LANG->line('delete')));
		$DSP->body .= $DSP->qdiv('alert', $DSP->div_c());
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->form_close();
	}


	//Objects perform delete
	/**
	 *
	 *
	 * @param unknown $extraid (optional)
	 * @return unknown
	 */
	public function delete($extraid = false, $return = true) {
		global $IN, $DSP, $LANG, $SESS, $DB;

		//find objects and delete
		foreach ($_POST as $key=>$val) {
			if (strstr($key, 'delete') && !is_array($val) && is_numeric($val)) {
				$data[$this->pk] = $val;
				$Obj = $this->find($data, $limit=1);
				$Obj->delete();
			}
		}

		//return
		if ($return == true) {
			return $this->list_objects($LANG->line($this->noun.'_ambiguous').' '.$LANG->line('successfully_deleted'), true, false, $extraid);
		}
	}


	//Object creation form
	/**
	 *
	 *
	 * @param unknown $msg        (optional)
	 * @param unknown $response   (optional)
	 * @param unknown $extraid    (optional)
	 * @param unknown $showheader (optional)
	 * @param unknown $multipart  (optional)
	 */
	public function create_form($msg = false, $response = false, $extraid = false, $showheader = true, $multipart = false) {
		global $DSP, $LANG, $IN, $DB, $PREFS;

		//JS/CSS includes
		$DSP->extra_header .= "<link rel=\"stylesheet\" href=\"".$PREFS->ini('theme_folder_url', TRUE)."jquery_ui/cupertino/jquery-ui-1.7.2.custom.css\" type=\"text/css\" media=\"screen\" />";
		$DSP->extra_header .= '<style type="text/css">';
        $DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/css/autocomplete.css');
       	$DSP->extra_header .= '</style>';
		$DSP->extra_header .= '<script type="text/javascript">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/ui.datepicker.js');
		$DSP->extra_header .= '</script>';
		$DSP->extra_header .= '<script type="text/javascript">';
		$DSP->extra_header .= file_get_contents(PATH_MOD.MODULE_SLUG.'/js/form.js');
		$DSP->extra_header .= '</script>';

		//header
		if ($showheader == true) {
			$this->page_header($msg, $response, false, $this->noun.'_add', $this->noun);
		}

		//start form
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line($this->noun.'_add'));
		if ($extraid == false) {
			if ($multipart == false) {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add'));
			}
			elseif ($multipart == true) {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add', 'enctype'=>'multipart/form-data'));
			}
		}
		else {
			if ($multipart == false) {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add'.AMP.'id='.$extraid));
			}
			elseif ($multipart == true) {
				$DSP->body .= $DSP->form_open(array('action' => 'C=modules'.AMP.'M='.MODULE_SLUG.AMP.'P='.$this->noun.'_add'.AMP.'id='.$extraid, 'enctype'=>'multipart/form-data'));
			}
		}
		$DSP->body .= $DSP->table('tableBorder', '0', '0', '100%');

		//add fields to form
		foreach ($this->form_fields as $fieldname=>$field) {
			//get value from post
			if (isset($_POST[$fieldname]) && !empty($_POST[$fieldname])) {
				$postval = $_POST[$fieldname];
			}
			else {
				$postval = '';
			}

			$DSP->body .= $DSP->tr();
			switch ($field['type']) {
			case 'text':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}				
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text($fieldname, $postval, '35', '', 'input', '100%', 'id="'.$fieldname.'"'))));
				break;
			case 'textarea':
				if ($field['required'] == true) {
					$content = $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>');
				}
				else {
					$content = $DSP->qdiv('defaultBold', $LANG->line($fieldname));
				}				
				//formatting link
				$content .= $DSP->qdiv('default', $DSP->anchor('http://expressionengine.com/docs/general/pmcode.html', $LANG->line('formatting_help'), 'target="_new"'));
				$DSP->body .= $DSP->table_qcell('tableCellOne textareaDescriptor', $content);
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_textarea($fieldname, $postval, '10', '', '100%', 'id="'.$fieldname.'"'))));
				break;
			case 'select':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				$select = '<select name="'.$fieldname.'" class="select" id="'.$fieldname.'">';
				foreach ($field['options'] as $name=>$value) {
					if ($postval == $value) {
						$select .= $DSP->input_select_option($value, $name, 'y');
					}
					else {
						$select .= $DSP->input_select_option($value, $name);
					}
				}
				$select .= '</select>';
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $select));
				break;
			case 'radio':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				
				$radio = '';
				foreach ($field['options'] as $name=>$value) {
					if ($value == $postval) {
						$radio .= $DSP->input_radio($fieldname, $value, 1, 'id="'.$value.'" checked="checked"').'<label for="'.$value.'">'.$name.'</label>&nbsp;';
					}
					else {
						$radio .= $DSP->input_radio($fieldname, $value, 0, 'id="'.$value.'"').'<label for="'.$value.'">'.$name.'</label>&nbsp;';
					}
				}
				
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('default', $radio));
				break;
			case 'datetime':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', $DSP->input_text($fieldname, $postval, '35', '', 'input datePicker', '100%', 'id="'.$fieldname.'"'))));
				break;
			case 'colourselect':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->qdiv('itemWrapper', '<div id="colourSelector"></div>'.$DSP->input_text($fieldname, '#000000', '35', '', 'input colourSelector', '100%', 'id="'.$fieldname.'"'))));
				break;
			case 'file':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				if ($field['options'] == 'multi') {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '<input name="'.$fieldname.'[]" id="'.$fieldname.'" type="file" />&nbsp;&nbsp;<a href="#" class="addFileInput">[+]</a>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', '<input name="'.$fieldname.'" id="'.$fieldname.'" type="file" />'));				
				}
				break;
			case 'checkbox':
				if ($field['required'] == true) {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname).'&nbsp;<span class="required">*</span>'));
				}
				else {
					$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $LANG->line($fieldname)));
				}
				$DSP->body .= $DSP->table_qcell('tableCellOne', $DSP->qdiv('defaultBold', $DSP->input_checkbox($fieldname, '1', 0)));
			}
			$DSP->body .= $DSP->tr_c();
		}

		//finish form
		$DSP->body .= $DSP->table_c();
		if ($this->noun == 'bug') {
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('add'), 'add').'&nbsp;'.$DSP->input_submit($LANG->line('bug_add_another'), 'addanother').'&nbsp;'.$DSP->input_submit($LANG->line('cancel'), 'cancel'));
		}
		else {
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('add')));
		}
		$DSP->body .= $DSP->form_close();
	}



	/**
	 *
	 *
	 * @param unknown $Paging
	 * @return unknown
	 */
	private function pagination($Paging) {
		$url = $_SERVER['REQUEST_URI'];
		$url = preg_replace('%&page=([0-9]+)%', '', $url);

		if (isset($_POST['keywords'])) {
			$encoded = base64_encode(serialize($_POST));
			$url .= '&encsearch='.$encoded;
		}

		$out = '<table border="0"  cellspacing="0" cellpadding="0" style="width:100%;" >
					<tr>
						<td  class="default">
							<div class="crumblinks">';

		if ($Paging->number_of_pages() > 1) {
			if (!$Paging->is_first_page()) {
				$out .=  '<a href="'.$url.'&page=1'.'">&laquo; First&nbsp;</a>';
				$prev = $Paging->current_page() - 1;
				$out .=  '<a href="'.$url.'&page='.$prev.'">&lt;&nbsp;&nbsp;</a>';
			}

			if ($Paging->number_of_pages() > 1) {
				if (!isset($_GET['page'])) {
					$out .=  '<strong>1</strong>';
				}
				else {
					$out .=  '<strong>'.$Paging->current_page().'</strong>';
				}
				$i = 1;
				while ($i < 3) {
					$page = $Paging->current_page() + $i;
					if ($page <= $Paging->number_of_pages()) {
						$out .= '&nbsp;';
						$out .= '<a href="'.$url.'&page='.$page.'">'.$page.'</a>';
					}
					$i++;
				}
			}

			if (!$Paging->is_last_page()) {
				$next = $Paging->current_page() + 1;
				$out .=  '<a href="'.$url.'&page='.$next.'">&nbsp;&nbsp;&gt;</a>';
				$out .=  '<a href="'.$url.'&page='.$Paging->number_of_pages().'">&nbsp;Last &raquo;</a>';
			}
		}

		$out .= '			</div>
						</td>
					</tr>
				</table>';


		return $out;
	}
	
	public function fourohfour() {
		global $LANG;
	
		Factory::page_header($LANG->line('fourohfour'), false, false, false, false, false, false, false);
	}


}


?>