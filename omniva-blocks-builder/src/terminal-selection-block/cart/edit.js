import { useBlockProps } from '@wordpress/block-editor';

import { txt } from '../global/text';

export const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps} style={{ display: 'block' }}>
            <div className={'wc-block-components-totals-wrapper'}>
                <span className={'wc-block-components-totals-item'}>{txt.cart_terminal_info}</span>
            </div>
        </div>
    );
};

export const Save = () => {
    return <div { ...useBlockProps.save() } />;
};
