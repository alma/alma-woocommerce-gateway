import {useBlockProps} from '@wordpress/block-editor';

const Save = () => {
    return (
        <div {...useBlockProps.save()} id="alma-widget-container"></div>
    );
};

export default Save;