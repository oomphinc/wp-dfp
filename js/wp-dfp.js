(function ($, window) {

	wpdfp.init = function() {
		var sizeMapping = {}, $ads = $('.wp-dfp-ad-unit');

		// If a network code is not set, bail and attempt to display an error message
		if (!wpdfp.network.length) {
			if (wpdfp.messages.noNetworkCode) {
				// Display the error message in place of each in-page ad unit
				$ads.not('[data-outofpage]').replaceWith(wpdfp.messages.noNetworkCode);
			}

			return;
		}

		// Calculate size mappings for each ad space
		$.each(wpdfp.slots, function(name) {
			$ads.filter(function() {
				var regex = new RegExp(name)
				  , $this = $(this);

				return $this.data('adunit') && $this.data('adunit').match(name);
			}).each(function() {
				var $this      = $(this)
			  	  , id         = $this.data('adunit')
			  	  , $container = $this.closest('.wp-dfp-ad-slot')
			  	  , rules      = wpdfp.slots[id]
			  	  , adSizes    = null
				  , maxWidth   = 0
				;

				// Using the defined sizing rules for this ad slot, determine which set
				// of ad sizes should be used.
				if (rules != 'oop') {
					$.each(rules, function(width) {
						width = parseInt(width);
						if ($container.width() >= width && width > maxWidth) {
							maxWidth = width;
							adSizes = rules[width];
					  	}
					});

					if (adSizes) {
					  	sizeMapping[id] = [
					  		{ browser: [0, 0], ad_sizes: adSizes }
					  	];
					}
					else {
						sizeMapping[id] = [
							{ browser: [0, 0], ad_sizes: [] }
						];
					}
				}
		  	});
		});

		if ($.fn.dfp) {
			$ads.dfp({
				dfpID:               wpdfp.network,
				collapseEmptyDivs:   false,
				setUrlTargeting:     false,
				setTargeting:        wpdfp.targeting,
				sizeMapping:         sizeMapping,
				afterEachAdLoaded:   wpdfp.afterEachAdLoaded,
				enableSingleRequest: true
			});
		}
	};

	wpdfp.afterEachAdLoaded = function($adUnit, event) {
		if (!event.isEmpty) {
			$adUnit.show();
		}
		else {
			$adUnit.hide();
		}
	};

	wpdfp.init();
	$(window).resize(wpdfp.init);

})(window.jQuery, window);

