/* global jQuery, _wpUtilSettings, _wpUtilSettings.ajax.url */
jQuery('#feedback_feedback_status').change(function() {
	jQuery.post(_wpUtilSettings.ajax.url,
		{
			'status': jQuery(this).val(),
			'post_id': jQuery('#post_ID').val(),
			'_feedback_status_nonce': jQuery('#_feedback_status_nonce').val(),
			'action': 'change_feedback_status',
		});
});
