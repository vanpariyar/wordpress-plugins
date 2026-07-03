import { unzip } from 'fflate';
import Npyjs from 'npyjs';

function unzipAsync( data ) {
	return new Promise( ( resolve, reject ) => {
		unzip( data, ( error, files ) => {
			if ( error ) {
				reject( error );
				return;
			}

			resolve( files );
		} );
	} );
}

/**
 * Load a voices.npz file into a voice-name -> tensor map.
 *
 * @param {ArrayBuffer} arrayBuffer NPZ file bytes.
 * @return {Promise<Record<string, {data: Float32Array, shape: number[]}>>}
 */
export async function loadVoicesNpz( arrayBuffer ) {
	const files = await unzipAsync( new Uint8Array( arrayBuffer ) );
	const npy = new Npyjs();
	const voices = {};

	for ( const [ filename, content ] of Object.entries( files ) ) {
		const voiceKey = filename.replace( /\.npy$/, '' );
		const parsed = await npy.load( content.buffer );

		voices[ voiceKey ] = {
			data:
				parsed.data instanceof Float32Array
					? parsed.data
					: new Float32Array( parsed.data ),
			shape: parsed.shape,
		};
	}

	return voices;
}
