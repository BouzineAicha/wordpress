( function ( $ ) {
	const ignore_gb_notice = function () {
		$( '.wcf_notice_gutenberg_plugin button.notice-dismiss' ).on(
			'click',
			function ( e ) {
				e.preventDefault();

				const data = {
					action: 'cartflows_ignore_gutenberg_notice',
					security: cartflows_notices.ignore_gb_notice,
				};

				$.ajax( {
					type: 'POST',
					url: ajaxurl,
					data,

					success( response ) {
						if ( response.success ) {
							console.log( 'Gutenberg Notice Ignored.' );
						}
					},
				} );
			}
		);
	};

	$( function () {
		ignore_gb_notice();
	} );
} )( jQuery );
