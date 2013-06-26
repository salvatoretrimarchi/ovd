
/* ---- Show/Hide UI elements -----*/

function showLogin() {
	jQuery('#main').fadeIn();
}

function hideLogin() {
	jQuery('#main').fadeOut();
}

function showLock() {
	jQuery('#lock').show();
	jQuery('#overlay').fadeIn();
}

function hideLock() {
	jQuery('#lock').hide();
	jQuery('#overlay').hide();
}

function showSplash() {
	jQuery('#splashContainer').fadeIn();
}

function hideSplash() {
	jQuery('#splashContainer').fadeOut();
}

function showSystemTest() {
	jQuery('#systemTest').show();
}

function hideSystemTest() {
	jQuery('#systemTest').hide();
}

function showNews(title_, content_) {
	var message = '<div style="width: 100%; height: 75%; overflow: auto;">'+
	               content_.replace(new RegExp("\n", "g"), '<br />')+
	              '</div>';
	jQuery('#newsTitle').html(title_);
	jQuery('#newsContent').html(message);
	jQuery('#news').show();
}

function hideNews() {
	jQuery('#news').hide();
}

function showIFrame(url_) {
	jQuery('#iframeContainer').prop('src', url_);
	jQuery('#iframe').show();
}

function hideIFrame() {
	jQuery('#iframe').hide();
}

function showMainContainer() {
	jQuery('#sessionContainer').fadeIn();
}

function hideMainContainer() {
	jQuery('#sessionContainer').fadeOut();
}

function showEnd() {
	jQuery('#endContainer').fadeIn();
}

function hideEnd() {
	jQuery('#endContainer').fadeOut();
}

function showSystemTestError(message) { 
	jQuery("#systemTestErrorMessage").html(message);	
	jQuery('#systemTestError').show();
}

function hideSystemTestError(message) { 
	jQuery('#systemTestError').hide();
}

function showError(errormsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideError(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              errormsg;
	jQuery('#error').html(message).show();
	jQuery('#notification').show();
}

function hideError() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#error').hide();
	});
}

function showOk(okmsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideOk(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              okmsg;
	jQuery('#ok').html(message).show();
	jQuery('#notification').show();
}

function hideOk() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#ok').hide();
	});
}

function showInfo(infomsg) {
	var message = '<div style="width: 16px; height: 16px; float: right; margin-right: 20px;">'+
	              	'<a href="javascript:;" onclick="hideInfo(); return false;">'+
	              		'<img src="media/image/cross.png" width="16" height="16" alt="" title="" />'+
	              	'</a>'+
	              '</div>'+
	              infomsg;
	jQuery('#info').html(message).show();
	jQuery('#notification').show();
}

function hideInfo() {
	jQuery('#notification').fadeOut(400, function() {
		jQuery('#info').hide();
	});
}

/* ------- Generate end messages ------ */

function generateEnd_internal(error) {
	if( ! jQuery('#endContent > *')[0]) {
		var buf = jQuery(document.createElement('span')).css({'font-size' : '1.1em', 'font-weight' : 'bold', 'color' : '#686868'});
		var end_message = null;

		if(error) {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(''+
				'<span class="msg_error">'+
					i18n['session_end_unexpected']+
				'</span>'+
				'<br/>'+
				'<span class="msg_error">'+
					'Cause : '+error+
				'</span>');
		} else {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(i18n['session_end_ok']);
		}

		var close_container = jQuery(document.createElement('div')).css('margin-top', '10px');
		var close_text = jQuery(document.createElement('span')).html(i18n['start_another_session']);
		close_container.append(close_text);

		buf.append(end_message);
		buf.append(close_container);
		jQuery('#endContent').append(buf);

		jQuery("#endContent a").click( function() {
			hideEnd();
			showLogin();
			pullLogin();

			setTimeout( function() {
				/* Wait for animation end */
				resetEnd();
			}, 2000);
		});
	}
}

function generateEnd_external(error) {
	if( ! jQuery('#endContent > *')[0]) {
		var buf = jQuery(document.createElement('span')).css({'font-size' : '1.1em', 'font-weight' : 'bold', 'color' : '#686868'});
		var end_message = null;

		if(error) {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(''+
				'<span class="msg_error">'+
					i18n['session_end_unexpected']+
				'</span>'+
				'<br/>'+
				'<span class="msg_error">'+
					error+
				'</span>');
		} else {
			var end_message = jQuery(document.createElement('span')).prop('id', 'endMessage').html(i18n['session_end_ok']);
		}

		buf.append(end_message);
		jQuery('#endContent').append(buf);
	}
}

function resetEnd() {
	jQuery('#endContent').empty();
}

/* ------- Customize panels ------ */

function configureUI(mode) {
	var session_params = ovd.framework.session_management.parameters;
	var session_settings = ovd.framework.session_management.session.settings;
	if(mode == uovd.SESSION_MODE_APPLICATIONS) {
		/* Configure page layout */
		(function() {
			/* Set page size */
			var page_height = jQuery("body").innerHeight();
			var header_height = jQuery('#applicationsHeader').height();
			var content_height = parseInt(page_height)-parseInt(header_height)-30;
			jQuery('#appsContainer').height(content_height);
			jQuery('#fileManagerContainer').height(content_height);

			/* Hide desktops */
			/* do not use .hide() or applet wil not load */
			jQuery('#desktopContainer').width(1).height(1).css("overflow", "hidden");

			/* Show applications mode components */
			jQuery("#applicationsHeader").show();
			jQuery("#applicationsContainer").show();
			jQuery("#windowsContainer").show();

			/* Set name */
			jQuery('#user_displayname').html(session_settings.user_displayname);
		})();

		/* Suport suspend ? */
		(function() {
			if(session_settings["persistent"]) {
				jQuery('#suspend_button').show();
			}
		})();
	} else {
		/* Configure page layout */
		(function() {
			/* Show desktop */
			jQuery('#desktopContainer').width("100%").height("100%");

			/* Hide applications mode components */
			jQuery("#applicationsHeader").hide();
			jQuery("#applicationsContainer").hide();
			jQuery("#windowsContainer").hide();
		})();
	}
}

function initSplashConnection() {
	jQuery("#unloading_ovd_gettext").hide();
	jQuery("#loading_ovd_gettext").show();
	jQuery('#progressBarContent').css("width", "0%");
}

function initSplashDisconnection() {
	jQuery("#loading_ovd_gettext").hide();
	jQuery("#unloading_ovd_gettext").show();
	jQuery('#progressBarContent').css("width", "100%");
}

function disableLogin() {
  jQuery('#submitButton').hide();
  jQuery('#submitLoader').show();
}

function enableLogin() {
  jQuery('#submitButton').show();
  jQuery('#submitLoader').hide();
}

function pullMainContainer() {
	jQuery('#sessionContainer').animate({top:0}, 800);
}

function pushMainContainer() {
	jQuery('#sessionContainer').animate({top:"-200%"}, 800);
}

function pullLogin() {
	jQuery('#main').show().animate({top:0}, 800);
}

function pushLogin() {
	jQuery('#main').animate({top:"-200%"}, 800);
}


/* ------- Translate UI ------ */

function translateInterface(lang_) {
	jQuery.ajax({
			url: 'translate.php?differentiator='+Math.floor(Math.random()*50000)+'&lang='+lang_,
			type: 'GET',
			dataType: 'xml',
			success: function(xml) {
				if (xml == null)
					return;

				var items = {};
				var translations = xml.getElementsByTagName('translation');
				for (var i = 0; i < translations.length; i++) {
					items[translations[i].getAttribute('id')] = translations[i].getAttribute('string');
				}

				applyTranslations(items);
				
				var js_translations = xml.getElementsByTagName('js_translation');
				for (var i = 0; i < js_translations.length; i++)
					i18n[js_translations[i].getAttribute('id')] = js_translations[i].getAttribute('string');
			}
		}
	);
}

function applyTranslations(translations) {
	for(key in translations) {
		var value = translations[key];

		var obj = jQuery('#'+key+'_gettext')[0];
		if (! obj)
			continue;
		
		if (obj.nodeName.toLowerCase() == 'input')
			obj.value = value;
		else
			obj.innerHTML = value;
	}
		
	if (typeof window.updateSMHostField == 'function')
		updateSMHostField();
}

/* ------- Other ------ */

function confirmLogout() {
	var framework = window.ovd.framework; /* shorten names */
	var defaults = window.ovd.defaults;

	var confirm_mode = defaults.confirm_logout;
	var running_apps = framework.listeners.application_counter.get();

	if(confirm_mode == 'always' || (confirm_mode == 'apps_only' && running_apps > 0)) {
		/* Ask confirmation */
		if(confirm(i18n['want_logout'].replace('#', running_apps)))
			framework.session_management.stop();
	} else {
		/* Logout without asking */
		framework.session_management.stop();
	}
}

function getWebClientBaseURL() {
	/*
		Using any window.location.href as:
		  * http://host/ovd/
		  * http://host/ovd/index.php
		  * http://host/ovd/external.php?args1=val/ue1
		Return: http://host/ovd/
	*/
	var url = window.location.href;
	url = url.replace(/\?[^\?]*$/, "");
	return url.replace(/\/[^\/]*$/, "")+"/";
}
