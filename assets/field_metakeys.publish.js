(function($) {

	$(document).on('ready.metakeys', function() {
		$('.metakeys-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
  		}).on('constructstop.duplicator', function(event) {
  			$(event.target).find('input').on('focus.metakeys click.metakeys', function(event) {
	  			event.preventDefault();
	  			event.stopPropagation();
	  		});
  		});
	});

})(window.jQuery);
