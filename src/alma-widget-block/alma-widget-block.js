import {registerBlockType} from '@wordpress/blocks';
import metadata from './block.json';
import './alma-widget-block.css';
import Edit from './edit';
import Save from './save';

const almaIcon = (
    <svg width="451" height="512" viewBox="0 0 451 512" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M347.22 123.046C323.434 29.8196 273.131 0 225.249 0C177.367 0 127.063 29.8196 103.278 123.046L0 512H101.787C118.369 447.034 169.48 410.847 225.249 410.847C281.018 410.847 332.129 447.099 348.71 512H450.56L347.22 123.046ZM225.249 320.219C192.831 320.219 163.456 333.083 141.782 353.937L200.159 127.594C205.748 105.96 214.008 99.0737 225.311 99.0737C236.614 99.0737 244.874 105.96 250.463 127.594L308.778 353.937C287.104 333.083 257.667 320.219 225.249 320.219Z"
            fill="#FA5022"/>
    </svg>
);

registerBlockType(metadata.name, {
    category: metadata.category,
    icon: almaIcon,
    edit: Edit,
    save: Save,
});
