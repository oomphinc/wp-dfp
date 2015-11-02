( function( $ ) {

	/**
	 * Refers to the last known width of the viewport
	 * @since 1.1.6
	 * @var int
	 */
	wpdfp.winWidth = null;

	/**
	 * Refers to whether or not wpdfp has been initialized already.
	 * @since 1.1.6
	 * @var bool
	 */
	wpdfp.didInit = false;

	wpdfp.init = function() {
		var sizeMapping = {},
		    $ads = $( '.wp-dfp-ad-unit' );

		// If a network code is not set, bail and attempt to display an error message
		if ( !wpdfp.network.length ) {
			if ( wpdfp.messages.noNetworkCode ) {
				// Display the error message in place of each in-page ad unit
				$ads.not( '[data-outofpage]' ).replaceWith( wpdfp.messages.noNetworkCode );
			}

			return;
		}

		// Calculate size mappings for each ad space
		$.each( wpdfp.slots, function( name, sizes ) {
			$ads.filter( function() {
				var regex = new RegExp( name )
				  , $this = $( this );

				return $this.data( 'adunit' ) && $this.data( 'adunit' ).match( name );
			} ).each( function( index ) {
				var $this      = $( this )
				  , id         = $this.data( 'adunit' )
				  , $container = $this.closest( '.wp-dfp-ad-slot' )
				  , rules      = wpdfp.slots[ id ]
				  , adSizes    = null
				  , currRule   = $this.data( 'wpdfp.sizerule' ) || null
				  , newRule    = null;

				// Using the defined sizing rules for this ad slot, determine which set
				// of ad sizes should be used.
				if ( rules != 'oop' ) {
					$.each( rules, function( width, sizes ) {
						width = parseInt( width );
						if ( $container.width() >= width ) {
							adSizes = rules[ width ];
							newRule = width;
						}
					} );

					// If the ad sizing rule hasn't changed for this ad unit
					// then remove it from the $ads object and move on. This
					// fixes an issue with ads being reloaded when nothing
					// has changed.
					if ( currRule === newRule ) {
						$ads.splice( index, 1 );
						return;
					}

					$this.data( 'wpdfp.sizerule', newRule );

					if ( adSizes ) {
						sizeMapping[ id ] = [
							{ browser: [ 0, 0 ], ad_sizes: adSizes }
						];
					}
					else {
						sizeMapping[ id ] = [
							{ browser: [ 0, 0 ], ad_sizes: [] }
						];
					}
				}
			} );
		} );

		if ( $.fn.dfp && $ads.length && !$.isEmptyObject( sizeMapping ) ) {
			$ads.dfp( {
				dfpID:               wpdfp.network,
				collapseEmptyDivs:   false,
				setUrlTargeting:     false,
				setTargeting:        wpdfp.targeting,
				sizeMapping:         sizeMapping,
				afterEachAdLoaded:   wpdfp.afterEachAdLoaded,
				enableSingleRequest: true
			} );
		}
	};

	wpdfp.afterEachAdLoaded = function( $adUnit, event ) {
		if ( !event.isEmpty ) {
			$adUnit.show();
		}
		else {
			$adUnit.hide();
		}
	};

	/**
	 * Respond to window resize events.
	 * @since 1.1.6
	 */
	wpdfp.onResize = function() {
		var $win = $( window );

		// Ensure that the window size has actually changed. Some
		// mobile devices trigger a resize event when scrolling.
		if ( wpdfp.winWidth !== $win.width() ) {
			wpdfp.winWidth = $win.width();
			wpdfp.init();
		}
	};

	wpdfp.init();
	$( window ).resize( wpdfp.onResize );

} )( jQuery );

