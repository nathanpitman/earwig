<?

class Util {

	public function debug($data) {
		if (is_array($data) || is_object($data)) {
			echo '<pre>';
			print_r($data);
			echo '</pre>';
		}
		else {
			echo $data;
		}
	}

	public function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return round(((float)$usec + (float)$sec));
	}

	//create csv and output it
    public function download_csv($titles, $values, $filename) {
		//build csv
		$csv = $this->output_csv($titles, $values);

		//output file for download
		header("Content-Type: text/csv");
		header("Content-Length: ".strlen($csv) );
		header("Content-Transfer-Encoding: binary\n");
		header("Content-Disposition: attachment; filename=\"".$filename.".csv\"");
		echo($csv);
		exit();
    }

	//output a csv array
    public function output_csv($titles, $values) {
		$out = '';
		if (is_array($titles) && count($titles) > 0){
			foreach($titles as $title) {
				$out .= '"' . $title . '",';
			}
			$out .= "\n";

			foreach($values as $value) {
				$row = array();
				foreach ($value as $val) {
					$row[] = '"'.str_replace(', ', ' ', $val).'"';
				}
				$out .= implode(',', $row);
				$out .= "\n";
			}
		}
		return $out;
	}

	public function check_php_version() {
		global $DSP, $LANG;

		$phpversion = phpversion();
		$phpversion = explode('.', $phpversion);
		$phpversion = $phpversion[0];
		if ($phpversion < 5) {
			$DSP->body .= $DSP->qdiv('errorBox', $DSP->qdiv('alertHeading', $LANG->line('no_php_5'))).BR;
		}
		return;
	}

	public function check_jquery_version() {
		global $DB, $DSP, $LANG;

		$query = $DB->query("SELECT * FROM exp_extensions
							 WHERE class = 'Cp_jquery'
							 AND method = 'add_js'");
		if ($query->num_rows < 1) {
			$DSP->body .= $DSP->qdiv('errorBox', $DSP->qdiv('alertHeading', $LANG->line('no_jquery_13'))).BR;
		}
		else {
			$settings = $query->result[0]['settings'];
			$settings = unserialize($settings);
			$jquery_src = $settings['jquery_src'];
			$match = preg_match('%1.3%', $jquery_src);
			if ($match == 0) {
				$DSP->body .= $DSP->qdiv('errorBox', $DSP->qdiv('alertHeading', $LANG->line('no_jquery_13'))).BR;
			}
		}
		return;
	}

	public function tagparse($str) {
		$tags = array();
		$i=0;
		//remove quoted segments
		$quoted = array();
		preg_match_all('/\\\\"(.*?)\\\\"/', $str, $quoted);
		if (is_array($quoted[1])) {
			foreach ($quoted[1] as $tag) {
				$tag = trim($tag);
				if ($tag != '' && !in_array($tag, $tags)) {
					$tags[$i]=Util::urlify($tag);
					$str = preg_replace('/\\\\"(.*?)\\\\",?\s?/', '', $str);
					$i++;
				}
		    }
		}
		//find comma separated
		$commas=preg_split('/,/', $str);
        if (is_array($commas) && count($commas) > 1) {
        	foreach ($commas as $tag) {
        		$tag = trim($tag);
        	    if ($tag != '' && !in_array($tag, $tags)) {
					$tags[$i] = $tag;
					$str = preg_replace('/(.*?),(\s*?)/', '', $str);
					$i++;
				}
        	}
		}
		//if user hasn't delimited by commas, find space seperated
        else {
		$spaces=preg_split('/\s/', $str);
        	foreach ($spaces as $tag) {
            	$tag = trim($tag);
		        if ($tag != '' && !in_array($tag, $tags)) {
		           	$tags[$i] = $tag;
    				$str = preg_replace('/(.*?)/', '', $str);
			    	$i++;
				}
		    }
       	}
		return $tags;
	}
	
	public function get_file($path, $mimetype) {
		//output file for download
		header("Content-Type: ".$mimetype);
		header("Content-Length: ".filesize($path) );
		header("Content-Transfer-Encoding: binary\n");
		header("Content-Disposition: attachment; filename=\"".basename($path));
		readfile($path);
		exit();
	}
	
	public function urlify($string) {	
		$s	= strtolower($string);
		$s	= preg_replace('/[^a-z0-9\s]/', '', $s);
		$s	= trim($s);
		$s	= preg_replace('/\s+/', '-', $s);
		
		if (strlen($s)>0){
			return $s;
		}else{
			$md5	= md5($string);
			$s		= strtolower($md5);
			return 'ra-'.substr($s, 0, 4).'-'.substr($s, 5, 4);
		}		
	}
	
	public function parse_for_bugs($text) {
		global $FNS;
	
		preg_match('%bug #([0-9]+)%', $text, $links);		
		if (!empty($links)) {
			$id = str_replace('bug #', '', $links[0]);
			$text = preg_replace('%'.$links[0].'%', '<a href="http://'.$_SERVER['HTTP_HOST'].'/index.php/earwig?ACT='.$FNS->fetch_action_id('Earwig_CP', 'bug_view_remote').'&id='.$id.'" class="external">bug #'.$id.'</a>', $text);
		}	
		return $text;
	}

}

?>