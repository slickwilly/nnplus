
// event bindings
jQuery(function($){

	// browse.tpl, search.tpl -- show icons on hover
	$('table.data tr').hover(
		function(){	$(this).children('td').children('.reshover').show(); },
		function(){	$(this).children('td').children('.reshover').hide(); }
	);

	// browse.tpl, search.tpl
	$('.add_to_cart').click(function(e){
		if ($(this).hasClass('icon_cart_clicked')) return false;
		$.post( SERVERROOT + "cart.php?add=" + $(this).attr('id'), function(resp){
			$(e.target).addClass('icon_cart_clicked').attr('title','added to cart');
		});
		return false;
	});
	$('.add_to_sab').click(function(e){ // replace with cookies?
		if ($(this).hasClass('icon_sab_clicked')) return false;

		var fullsaburl = $.cookie('sabnzbd_host') + "api/?mode=addurl&priority=1&apikey=" + $.cookie('sabnzbd_apikey');
		var nzburl = SERVERROOT + "download/sab/nzb/" + $(this).attr('id') + "&i=" + UID + "&r=" + RSSTOKEN;

		$.post( fullsaburl+"&name="+escape(nzburl), function(resp){
			$(e.target).addClass('icon_sab_clicked').attr('title','added to queue');
		});
		return false;
	});

	// headermenu.tpl
	$('#headsearch')
		.focus(function(){if(this.value == 'Enter keywords') this.value = '';})
		.blur (function(){if(this.value == '') this.value = 'Enter keywords';});
	$('#headsearch_form').submit(function(){
		$('headsearch_go').trigger('click');
		return false;
	});
	$('#headsearch_go').click(function(){
		if ($('#headsearch').val() && $('#headsearch').val() != 'Enter keywords')
			document.location= WWW_TOP + "/search/" + $.URLEncode($('#headsearch').val()) + ($("#headcat").val()!=-1 ? "&t="+$("#headcat").val() : "");
	});

	// login.tpl, register.tpl, search.tpl, searchraw.tpl
	if ($('#username').length)
		$('#username').focus();
	if ($('#search').length)
		$('#search').focus();

	// search.tpl
	$('#search_search_button').click(function(){
		if ($('#search').val())
			document.location=WWW_TOP + "/search/" + $.URLEncode($('#search').val());
		return false;
	});

	// searchraw.tpl
	$('#searchraw_search_button').click(function(){
		if ($('#search').val())
			document.location=WWW_TOP + "/searchraw/" + $.URLEncode($('#search').val());
		return false;
	});
	$('#searchraw_download_selected').click(function(){
		if ($('#dl input:checked').length)
			$('#dl').trigger('submit');
		return false;
	});

	// viewfilelist.tpl
	$('#viewfilelist_download_selected').click(function(){
		if ($('#fileform input:checked').length)
			$('#fileform').trigger('submit');
		return false;
	});

	// misc
	$('.confirm_action').click(function(){ return confirm('Are you sure?'); });


	// searchraw.tpl, viewfilelist.tpl -- checkbox operations
	// selections
	var last1, last2;
	$(".checkbox_operations .select_all").click(function(){
	    $("table.data INPUT[type='checkbox']").attr('checked', true).trigger('change');
		return false;
	});
	$(".checkbox_operations .select_none").click(function(){
	    $("table.data INPUT[type='checkbox']").attr('checked', false).trigger('change');
		return false;
	});
	$(".checkbox_operations .select_invert").click(function(){
	    $("table.data INPUT[type='checkbox']").each( function() {
	        $(this).attr('checked', !$(this).attr('checked')).trigger('change');
	    });
		return false;
	});
	$(".checkbox_operations .select_range").click(function(){
		if (last1 && last2 && last1 < last2)
	    	$("table.data INPUT[type='checkbox']").slice(last1,last2).attr('checked', true).trigger('change');
		else if (last1 && last2)
	    	$("table.data INPUT[type='checkbox']").slice(last2,last1).attr('checked', true).trigger('change');
		return false;
	});
	$('table.data td.selection INPUT[type="checkbox"]').click(function(e) {
	    // range event interaction -- see further above
		var rowNum = $(e.target).parent().parent()[0].rowIndex ;
	    if (last1) last2 = last1;
		last1 = rowNum;
	});
	$('table.data a.data_filename').click(function(e) { // click filenames to select
	    // range event interaction -- see further above
		var rowNum = $(e.target).parent().parent()[0].rowIndex ;
	    if (last1) last2 = last1;
		last1 = rowNum;

		var $checkbox = $('table.data tr:nth-child('+(rowNum+1)+') td.selection INPUT[type="checkbox"]');
		$checkbox.attr('checked', !$checkbox.attr('checked'));
    
		return false;
	});



	// SABnzbd integration
	if ($.cookie('sabnzbd_host')) {
		$('table.data .icon_sab, .sabnzbd_required').show();	// sab icons hidden by default
		
		// set profile.tpl credentials into profile on page load
		if ($('#profile_sab_host').val()) {
			$('#profile_sab_apikey').val($.cookie('sabnzbd_apikey'));
			$('#profile_sab_host').val($.cookie('sabnzbd_host'));
		}
	}
	// profile.tpl
	$('#profile_sab_save').click(function(){	// store sabnzbd info to cookie
		$.cookie('sabnzbd_apikey', $('#profile_sab_apikey').val(), { expires: 365 });
		$.cookie('sabnzbd_host', $('#profile_sab_host').val(), { expires: 365 });
		$(this).next('.icon').addClass('icon_check'); // save status notification
	});
	$('#profile_sab_clear').click(function(){	// store sabnzbd info to cookie
		$.cookie('sabnzbd_apikey', null);
		$.cookie('sabnzbd_host', null);
		$('#profile_sab_apikey, #profile_sab_host').val('');
		$('#profile_sab_save').next('.icon').removeClass('icon_check'); // save status notification
	});

});


$.extend({ // http://plugins.jquery.com/project/URLEncode
URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
  while(x<c.length){var m=r.exec(c.substr(x));
    if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
URLDecode:function(s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
  while((m=r.exec(o))!=null && m.length>1 && m[1]!=''){b=parseInt(m[1].substr(1),16);
  t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
});
