jQuery(document).ready(function() {
	jQuery('div.field-metakeys').each(function() {
		var $container = jQuery(this);

		//	Setup some jQuery objects
		$controls_container = jQuery('<div />', {
			'class': 'control-bar'
		});

		$controls = jQuery('<a />', {
			'class': 'control add',
			'text': 'Add Pair +',
		});

		$remove = jQuery('<a />', {
			'class': 'control remove',
			'text': 'x',
			'title': 'Remove Pair'
		});

		//	Store a clone
		$container.append(
			$container.find('dt:last, dd:last').clone().hide()
		);

		//	Inject the Objects
		$controls_container.append($controls);
		$container
			.find('dl').after($controls_container)
			.end()
			.find('dt').prepend($remove)

		//	Events
		$container.delegate('a.remove', 'click', function() {
			var key = jQuery(this).parent(),
				value = key.next('dd');

			jQuery([key, value]).each(function() {
				var self = jQuery(this);

				self.slideUp('fast', function() {
					self.remove();
				});
			});
		});

		$controls_container.delegate('a.add', 'click', function() {
			var $pair = $container.find('dt:last, dd:last').clone(),
			 	$last = $container.find('dl dd:last-child');
			var lastPairIndex = $container.find('dl dd').index($last);

			$pair.each(function() {
				var self = jQuery(this).find('input');

				self
					.attr('name', self.attr('name').replace(/\d\]$/, (lastPairIndex + 1) + ']'))
					.val('');
			})

			$container.find('dl').append($pair.show());
		});

		jQuery('form').bind('submit', function() {
			$container.find('dt:last, dd:last').remove();

			return true;
		});
	});
});