(function($) {

	$(document).on('ready.metakeys', function() {
		$('.metakeys-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
  		});
	});

})(window.jQuery);
