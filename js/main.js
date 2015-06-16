if (typeof(NINEFOURLABS) == 'undefined') {
	NINEFOURLABS	= {};
	NINEFOURLABS.UI	= {};
}

NINEFOURLABS.UI.Main = function()
{
	var init	= function() {
		//swapStatus();
		externalLinks();
		assignDiv();
		changeAssignee();
		addFileInput();
		deleteFileInput();
		getMoreTimelines();
		getChart();
	}
	
	var swapStatus = function() {
		$('.swapStatus').live('click', function(e) {
			e.preventDefault();
			
			var parent = $(this).parent();
			parent.append('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" />');
			var id = $(this).attr('id');
			var href = window.location.href.replace(/#/, '');
			
			var opts = 'bugID='+id+'&ajax_method=swapStatus'
			$.get(href, opts, function(data) {
				parent.html(data);
				$('.throbber').remove();
				
				//increment/deincrement inbox count
				var count = $('.inbox_count').text();
				var count = parseInt(count);
				var text = parent.text();
				if (text == 'Open [+]') {
					count++;
				}
				else {
					count--;
				}
				$('.inbox_count').text(count);
			});
		});
	}
	
	var externalLinks = function() {
		$('a.external').each(function(e) {
			$(this).attr('target', '_blank');
		});
	}
	
	var assignDiv = function() {
		$('.assignBug').live('click', function(e) {
			e.preventDefault();
			
			var s = $(this);
			
			//do vars
			var id = $(this).attr('id');			
			var href = window.location.href.replace(/#/, '');			
			var opts = 'bugID='+id+'&ajax_method=getQuickAssignees';
			var holder = s.parents('.assigneeHolder');

			//add throbber
			$(this).after('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" />');
			s.remove();
			
			$.get(href, opts, function(data) {
				$('.throbber').remove();
				if (data != '') {				
					holder.html(data);
				}
			});
		});
	}
	
	var changeAssignee = function() {
		$('.editAssigneeLink').live('click', function(e) {
			e.preventDefault;
			
			//get various attributes
			var s = $(this);			
			var t = s.parents('td').find('select');
			var icon = s.parents('td').find('.editAssigneeLink');
			var bugID = t.attr('id');			
			var member_id = t.find('option:selected').val();
			var holder = s.parents('.assigneeHolder');
						
			//add throbber
			icon.remove();
			t.after('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" />');
			
			var href = window.location.href.replace(/#/, '');			
			var opts = 'bugID='+bugID+'&ajax_method=assignBug&member_id='+member_id;
			
			$.get(href, opts, function(data) {
				$('.throbber').remove();
				if (data != '') {				
					holder.html(data);
				}
			});
		});
	}
	
	var addFileInput = function() {
		$('.addFileInput').live('click', function(e) {
			e.preventDefault();
			var tr = $(this).parents('tr');
			var html = tr.html();
			
			//replace plus link with minus link
			html = html.replace('addFileInput', 'deleteFileInput');
			html = html.replace('[+]', '[-]');
			
			tr.after('<tr>'+html+'</tr>');
		});
	}
	
	var deleteFileInput = function() {
		$('.deleteFileInput').live('click', function(e) {
			e.preventDefault();
			var tr = $(this).parents('tr');
			tr.remove();
		});
	}
	
	var getMoreTimelines = function() {
		$('.getMoreTimelines').live('click', function(e) {
			e.preventDefault();
			
			//get various attributes
			var bugID = $(this).attr('id');
			var tr = $(this).parents('div.section').find('table').find('tr:first');
			var t = $(this);
			
			//add throbber
			t.before('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" style="float: right;" />&nbsp;');
			
			var href = window.location.href.replace(/#/, '');			
			var opts = 'bugID='+bugID+'&ajax_method=getMoreTimelines';
			
			$.get(href, opts, function(data) {
				$('.throbber').remove();
				if (data != '') {				
					tr.after(data);
					t.remove();
				}
			});
		});
	}
	
	var getChart = function() {
	
		$('.chart_select').change(function(e) {	
		// Bah, live doesn't work with Safari, so have replaced the below with the above
		//$('.chart_select option').live('click', function(e) {
			e.preventDefault();
			
			//get various attributes
			var member_id = $('#chart_member option:selected').val();
			var start = $('#chart_start option:selected').val();
			var div = $('#chart_img');
			
			//add throbber
			$('#chart_member').after('&nbsp;<img src="/themes/cp_global_images/indicator.gif" class="throbber" style="float: right;" />&nbsp;');
			
			var href = window.location.href.replace(/#/, '');			
			var opts = 'member_id='+member_id+'&start='+start+'&ajax_method=getChart';
			
			$.get(href, opts, function(data) {
				$('.throbber').remove();
				if (data != '') {
					div.html(data);					
				}
			});
		});
	}

	return {
		init: init
	}
}();

jQuery(function($) { NINEFOURLABS.UI.Main.init(); });  