import {useBlockProps,} from '@wordpress/block-editor';


export const Edit = ({attributes, setAttributes}) => {
    const blockProps = useBlockProps();
    return (
        <div id="alma-widget" {...useBlockProps()}>Widget ALMA</div>
    );
};