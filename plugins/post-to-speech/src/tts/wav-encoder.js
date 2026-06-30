/**
 * Encode mono float32 PCM samples into a WAV blob.
 *
 * @param {Float32Array} samples   Audio samples in [-1, 1].
 * @param {number}       sampleRate Sample rate in Hz.
 * @return {Blob}
 */
export function encodeWav( samples, sampleRate = 24000 ) {
	const numChannels = 1;
	const bitsPerSample = 16;
	const bytesPerSample = bitsPerSample / 8;
	const blockAlign = numChannels * bytesPerSample;
	const dataLength = samples.length * bytesPerSample;
	const buffer = new ArrayBuffer( 44 + dataLength );
	const view = new DataView( buffer );

	const writeString = ( offset, value ) => {
		for ( let index = 0; index < value.length; index += 1 ) {
			view.setUint8( offset + index, value.charCodeAt( index ) );
		}
	};

	writeString( 0, 'RIFF' );
	view.setUint32( 4, 36 + dataLength, true );
	writeString( 8, 'WAVE' );
	writeString( 12, 'fmt ' );
	view.setUint32( 16, 16, true );
	view.setUint16( 20, 1, true );
	view.setUint16( 22, numChannels, true );
	view.setUint32( 24, sampleRate, true );
	view.setUint32( 28, sampleRate * blockAlign, true );
	view.setUint16( 32, blockAlign, true );
	view.setUint16( 34, bitsPerSample, true );
	writeString( 36, 'data' );
	view.setUint32( 40, dataLength, true );

	let offset = 44;

	for ( let index = 0; index < samples.length; index += 1 ) {
		const clamped = Math.max( -1, Math.min( 1, samples[ index ] ) );
		const intSample = clamped < 0 ? clamped * 0x8000 : clamped * 0x7fff;
		view.setInt16( offset, intSample, true );
		offset += 2;
	}

	return new Blob( [ buffer ], { type: 'audio/wav' } );
}
