/*
	By Osvaldas Valutis, www.osvaldas.info
	Available for use under the MIT License
*/

'use strict';

;( function( $, window, document, undefined )
{
	$( '.inputfile' ).each( function()
	{
		var $input	 = $( this ),
			$label	 = $input.next( 'label' ),
			labelVal = $label.html();

		$input.on( 'change', function( e )
		{
			var fileName = '';
			fileName = e.target.value.split( '\\' ).pop();
			if( fileName ) {
				$label.html( fileName );
				$label.removeClass('custom-file-label');
				$label.addClass('custom-file-label-uploaded');
			} else {
				$label.html( labelVal );
				$label.addClass('custom-file-label');
				$label.removeClass('custom-file-label-uploaded');
			}
		});

		// Firefox bug fix
		$input
		.on( 'focus', function(){ $input.addClass( 'has-focus' ); })
		.on( 'blur', function(){ $input.removeClass( 'has-focus' ); });
	});
})( jQuery, window, document );