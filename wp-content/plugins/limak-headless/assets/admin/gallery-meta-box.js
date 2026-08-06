( function ( $ ) {
	'use strict';

	var i18n = window.limakGalleryField || {
		selectTitle: 'Select Gallery Images',
		addButtonText: 'Add to Gallery',
		removeLabel: 'Remove image'
	};

	function addImage( $field, attachment ) {
		var thumb = ( attachment.sizes && attachment.sizes.thumbnail )
			? attachment.sizes.thumbnail.url
			: attachment.url;

		var $item = $(
			'<li class="limak-gallery-field__item" data-id="' + attachment.id + '">' +
				'<img src="' + thumb + '" alt="" />' +
				'<button type="button" class="limak-gallery-field__remove button-link" aria-label="' + i18n.removeLabel + '">&times;</button>' +
			'</li>'
		);

		$field.find( '.limak-gallery-field__list' ).append( $item );
	}

	function syncInput( $field ) {
		var ids = $field.find( '.limak-gallery-field__item' ).map( function () {
			return parseInt( $( this ).data( 'id' ), 10 );
		} ).get();

		$field.find( '.limak-gallery-field__input' ).val( JSON.stringify( ids ) );
	}

	$( function () {
		$( '.limak-gallery-field' ).each( function () {
			var $field = $( this );
			var frame;

			$field.find( '.limak-gallery-field__list' ).sortable( {
				update: function () {
					syncInput( $field );
				}
			} );

			$field.on( 'click', '.limak-gallery-field__add', function ( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: i18n.selectTitle,
					button: { text: i18n.addButtonText },
					multiple: true
				} );

				frame.on( 'select', function () {
					frame.state().get( 'selection' ).each( function ( attachment ) {
						addImage( $field, attachment.toJSON() );
					} );
					syncInput( $field );
				} );

				frame.open();
			} );

			$field.on( 'click', '.limak-gallery-field__remove', function ( e ) {
				e.preventDefault();
				$( this ).closest( '.limak-gallery-field__item' ).remove();
				syncInput( $field );
			} );
		} );
	} );
}( jQuery ) );
