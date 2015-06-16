if (typeof(NINEFOURLABS) == 'undefined') {
	NINEFOURLABS	= {};
	NINEFOURLABS.UI	= {};
}

NINEFOURLABS.UI.Form = function()
{
	var init	= function() {
		datePicker();
		projectSelect();
		projectSimpleAdvanced();
		projectCheckOneInstance();
		autoTagComplete();
		autoURLComplete();
	}
	
	var autoTagComplete = function() {		
		if ($('#bugTags').size() > 0) {
			var opts = 'ajax_method=getTags';
			var array = new Array();
			var href = window.location.href.replace(/#/, '');
			$.get(href, opts, function(data) { 
				if (data != '') {
					var obj = eval('('+data+')');
					jQuery.each(obj, function(i) {
			    		array.push(obj[i]);								
					});
				}
				$("#bugTags").autocompleteArray(array, { autoFill: true });	
			});		
		}
	}
	
	var autoURLComplete = function() {
		if ($('#bugURL').size() > 0) {	
			var opts = 'ajax_method=getURLs';
			var array = new Array();
			var href = window.location.href.replace(/#/, '');
			$.get(href, opts, function(data) { 
				if (data != '') {
					var obj = eval('('+data+')');
					jQuery.each(obj, function(i) {
			    		array.push(obj[i]);								
					});
				}
				$("#bugURL").autocompleteArray(array, { autoFill: true });	
			});		
		}
	}
	
	var projectSelect = function() {
		if ($('#bugAssignee').size() > 0) {
			//get first set of users for first project
			var select = $('#bugAssignee');
			select.after('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" />');
	
			var id = $('#projectID option:selected').val();
			var href = window.location.href.replace(/#/, '');
			
			//attempt to get bug id and add to request
			var gets = getURLVars();
			if (typeof(gets.id) != 'undefined') {
				var opts = 'projectID='+id+'&ajax_method=getAssignees&bugID='+gets.id;
			}
			else {
				var opts = 'projectID='+id+'&ajax_method=getAssignees';
			}		
			
			$.get(href, opts, function(data) {
				select.html(data);
				$('.throbber').remove();
			});
		}
		
		//event
		$('#projectID').change(function(e) {
			id = $('#projectID option:selected').val();
			var opts = 'projectID='+id+'&ajax_method=getAssignees';
			$.get(href, opts, function(data) {
				select.html(data);
				$('.throbber').remove();
			});
		})
	}
	
	var projectSimpleAdvanced = function() {
		if ($('#projectID').size() > 0) {
			//get form for first project
			var select = $('#projectID');
			select.after('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" />');
	
			var id = $('#projectID option:selected').val();
			var href = window.location.href.replace(/#/, '');
			var opts = 'projectID='+id+'&ajax_method=getProjectBugForm';
			$.get(href, opts, function(data) {
				if (data == 'simple') {
					$('#bugActually').parents('tr').hide();
					$('#bugDoing').parents('tr').hide();
					
					//swap label
					$('#bugExpected').parents('tr').find('td:first div.defaultBold').text('Description *');
				}
				$('.throbber').remove();
			});
		}
		
		//event
		$('#projectID').change(function(e) {
			id = $('#projectID option:selected').val();
			var opts = 'projectID='+id+'&ajax_method=getProjectBugForm';
			$.get(href, opts, function(data) {
				if (data == 'simple') {
					$('#bugActually').parents('tr').hide();
					$('#bugDoing').parents('tr').hide();	
					
					//swap label
					$('#bugExpected').parents('tr').find('td:first div.defaultBold').text('Description');			
				}
				else if (data == 'advanced') {
					$('#bugActually').parents('tr').show();
					$('#bugDoing').parents('tr').show();		
					
					//swap label
					$('#bugExpected').parents('tr').find('td:first div.defaultBold').text('What you expected to happen');
				}
				$('.throbber').remove();
			});
		})
	}
	
	var projectCheckOneInstance = function() {
		if ($('#projectID option').size() == 1) {
			$('#projectID').parents('tr').hide();
		}
	}
	
	var datePicker = function() {
		$('.datePicker').datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true
		});
	}
	
	var getURLVars = function() {
		var map = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			map[key] = value;
		});
		return map;
	}
	
	return {
		init: init
	}
}();

jQuery(function($) { NINEFOURLABS.UI.Form.init(); });  