import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextareaControl,
	SelectControl,
	RangeControl,
	Button,
	Spinner,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import { useState, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';

function getSettingsPageUrl( settings ) {
	if ( settings?.settingsUrl ) {
		return settings.settingsUrl;
	}

	const adminBase = window.location.href.split( '/wp-admin/' )[ 0 ];

	return `${ adminBase }/wp-admin/options-general.php?page=post-to-speech`;
}

const DEFAULT_VOICES = [
	'Bella',
	'Jasper',
	'Luna',
	'Bruno',
	'Rosie',
	'Hugo',
	'Kiki',
	'Leo',
];

function getPostPlainText( serializedContent ) {
	if ( ! serializedContent ) {
		return '';
	}

	const withoutBlockComments = serializedContent.replace(
		/<!--[\s\S]*?-->/g,
		' '
	);
	const doc = new DOMParser().parseFromString(
		withoutBlockComments,
		'text/html'
	);

	return ( doc.body.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}

async function blobToBase64( blob ) {
	return new Promise( ( resolve, reject ) => {
		const reader = new FileReader();
		reader.onload = () => {
			const result = reader.result;
			if ( typeof result !== 'string' ) {
				reject( new Error( 'Could not read audio blob.' ) );
				return;
			}

			const base64 = result.split( ',' )[ 1 ];
			resolve( base64 );
		};
		reader.onerror = () => reject( reader.error );
		reader.readAsDataURL( blob );
	} );
}

async function uploadWavBlob( wavBlob, postId ) {
	const audio = await blobToBase64( wavBlob );

	return apiFetch( {
		path: '/post-to-speech/v1/upload',
		method: 'POST',
		data: {
			audio,
			post_id: postId,
		},
	} );
}

export default function Edit( { attributes, setAttributes } ) {
	const { textSource, text, audioUrl, voice, speed } = attributes;
	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ progress, setProgress ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ voices, setVoices ] = useState( DEFAULT_VOICES );
	const [ settings, setSettings ] = useState(
		window.postToSpeechSettings || {}
	);

	const { postId, postContent } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			postId: editor?.getCurrentPostId?.() || 0,
			postContent: editor?.getEditedPostContent?.() || '',
		};
	}, [] );

	const postPlainText = useMemo(
		() => getPostPlainText( postContent ),
		[ postContent ]
	);

	const isPostSource = textSource !== 'custom';
	const speechText = isPostSource ? postPlainText : text;

	const isApiMode = settings.generationMode === 'api';

	useEffect( () => {
		apiFetch( { path: '/post-to-speech/v1/config' } )
			.then( ( response ) => {
				setSettings( ( previous ) => ( {
					...previous,
					...response,
				} ) );

				if ( response.voices?.length ) {
					setVoices( response.voices );
				}

				if ( response.defaultVoice && voice === 'Bella' ) {
					setAttributes( { voice: response.defaultVoice } );
				}
			} )
			.catch( () => {
				// Fall back to localized settings when available.
			} );
	}, [] );

	const generateAudio = async () => {
		if ( ! speechText.trim() ) {
			setError(
				isPostSource
					? __(
							'This post has no text content to convert.',
							'post-to-speech'
					  )
					: __(
							'Please enter custom text before generating audio.',
							'post-to-speech'
					  )
			);
			return;
		}

		if ( isApiMode && ! settings.apiConfigured ) {
			setError(
				__(
					'API mode is enabled but the TTS API URL and key are not configured.',
					'post-to-speech'
				)
			);
			return;
		}

		setIsGenerating( true );
		setError( '' );

		try {
			let response;

			if ( isApiMode ) {
				setProgress( __( 'Generating audio via API…', 'post-to-speech' ) );

				response = await apiFetch( {
					path: '/post-to-speech/v1/generate',
					method: 'POST',
					data: {
						text: speechText,
						voice,
						speed,
						post_id: postId,
					},
				} );
			} else {
				setProgress( __( 'Starting browser TTS…', 'post-to-speech' ) );

				const { generateSpeechBlob } = await import( './tts/engine' );
				const wavBlob = await generateSpeechBlob(
					speechText,
					voice,
					speed,
					settings,
					setProgress
				);

				setProgress( __( 'Uploading audio…', 'post-to-speech' ) );
				response = await uploadWavBlob( wavBlob, postId );
			}

			setAttributes( {
				audioUrl: response.audioUrl,
				attachmentId: response.attachmentId,
			} );
			setProgress( '' );
		} catch ( err ) {
			const message =
				err?.message ||
				__(
					'Audio generation failed. Check plugin settings and try again.',
					'post-to-speech'
				);
			setError( message );
			setProgress( '' );
		} finally {
			setIsGenerating( false );
		}
	};

	const blockProps = useBlockProps( {
		className: 'post-to-speech-block',
	} );

	const helpText = isApiMode
		? __(
				'Speech is generated by your configured TTS API service (pay-per-request).',
				'post-to-speech'
		  )
		: __(
				'Speech is generated in your browser. The first run downloads the speech model.',
				'post-to-speech'
		  );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Audio source', 'post-to-speech' ) }>
					<SelectControl
						label={ __( 'Text source', 'post-to-speech' ) }
						value={ textSource }
						options={ [
							{
								label: __( 'Post content', 'post-to-speech' ),
								value: 'post',
							},
							{
								label: __( 'Custom text', 'post-to-speech' ),
								value: 'custom',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { textSource: value } )
						}
						help={
							isPostSource
								? __(
										'Uses the full text of this post when audio is generated.',
										'post-to-speech'
								  )
								: __(
										'Uses the custom text entered below.',
										'post-to-speech'
								  )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Voice settings', 'post-to-speech' ) }>
					<SelectControl
						label={ __( 'Voice', 'post-to-speech' ) }
						value={ voice }
						options={ voices.map( ( item ) => ( {
							label: item,
							value: item,
						} ) ) }
						onChange={ ( value ) => setAttributes( { voice: value } ) }
					/>
					<RangeControl
						label={ __( 'Speed', 'post-to-speech' ) }
						value={ speed }
						onChange={ ( value ) => setAttributes( { speed: value } ) }
						min={ 0.5 }
						max={ 2 }
						step={ 0.1 }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Generation mode', 'post-to-speech' ) }
					initialOpen={ false }
				>
					<p>
						{ isApiMode
							? __( 'API mode (server-side)', 'post-to-speech' )
							: __( 'Browser mode (WASM)', 'post-to-speech' ) }
					</p>
					{ isApiMode && settings.pricePerRequest > 0 && (
						<p>
							{ __(
								'Estimated cost per generation:',
								'post-to-speech'
							) }{ ' ' }
							{ settings.pricePerRequest }
						</p>
					) }
					<p>
						<ExternalLink href={ getSettingsPageUrl( settings ) }>
							{ __(
								'Change generation mode in plugin settings',
								'post-to-speech'
							) }
						</ExternalLink>
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isPostSource ? (
					<TextareaControl
						label={ __( 'Post text preview', 'post-to-speech' ) }
						value={ postPlainText }
						readOnly
						rows={ 6 }
						help={ __(
							'This is the post content that will be converted to audio. Edit the post to change it.',
							'post-to-speech'
						) }
					/>
				) : (
					<TextareaControl
						label={ __( 'Custom text', 'post-to-speech' ) }
						value={ text }
						onChange={ ( value ) => setAttributes( { text: value } ) }
						rows={ 6 }
						help={ helpText }
					/>
				) }

				<div className="post-to-speech-block__actions">
					<Button
						variant="primary"
						onClick={ generateAudio }
						disabled={ isGenerating }
					>
						{ isGenerating
							? __( 'Generating…', 'post-to-speech' )
							: __( 'Generate audio', 'post-to-speech' ) }
					</Button>
					{ isGenerating && <Spinner /> }
				</div>

				{ progress && (
					<p className="post-to-speech-block__progress">{ progress }</p>
				) }

				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				{ audioUrl && (
					<figure className="post-to-speech-block__player">
						<audio controls src={ audioUrl } />
					</figure>
				) }
			</div>
		</>
	);
}
