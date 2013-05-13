(function($) {

	$(document).on('ready.metakeys', function() {
		$('.metakeys-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
  		}).find('input').on('click.metakeys', function(event) {
  			event.preventDefault();
  			event.stopPropagation();
  		});
	});

})(window.jQuery);
