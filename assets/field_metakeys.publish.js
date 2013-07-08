(function($) {

	$(document).on('ready.metakeys', function() {

		$('.metakeys-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
  		});

  		$('.metakeys-duplicator').on('click.metakeys', 'input:visible', function(event) {
  			event.preventDefault();
  			event.stopPropagation();
  		});
	});

})(window.jQuery);
