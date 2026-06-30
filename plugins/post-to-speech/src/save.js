import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { audioUrl } = attributes;

	if ( ! audioUrl ) {
		return null;
	}

	const blockProps = useBlockProps.save( {
		className: 'post-to-speech-block',
	} );

	return (
		<figure { ...blockProps }>
			<audio controls src={ audioUrl } />
		</figure>
	);
}
