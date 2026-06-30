( function () {
	const modeField = document.getElementById( 'post_to_speech_generation_mode' );

	if ( ! modeField ) {
		return;
	}

	const browserRows = document.querySelectorAll( '.post-to-speech-setting-browser' );
	const apiRows = document.querySelectorAll( '.post-to-speech-setting-api' );

	function toggleRows() {
		const isApi = modeField.value === 'api';

		browserRows.forEach( ( row ) => {
			row.style.display = isApi ? 'none' : '';
		} );

		apiRows.forEach( ( row ) => {
			row.style.display = isApi ? '' : 'none';
		} );
	}

	modeField.addEventListener( 'change', toggleRows );
	toggleRows();
} )();
