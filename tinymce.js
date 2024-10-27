; (function ($) {
	'use strict';

	tinymce.PluginManager.add('p3dpmvwp', function (editor, url) {
		// Add a button that is going to add the shortcode
		editor.addButton('p3dpmvwp', {
			icon: '',
			text: 'Phanes 3DP Multiverse',
			tooltip: 'Add Phanes 3DP Multiverse shortcode',
			onclick: function () {
				var sc = wp.shortcode.string({
					tag: 'phanes_3dp_multiverse',
					attrs: {},
					type: 'single'
				});
				editor.insertContent(sc);
			}
		});
	});
}(jQuery));