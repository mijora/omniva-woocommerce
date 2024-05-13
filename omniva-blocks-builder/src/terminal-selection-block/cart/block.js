import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

import { getActiveShippingRates } from '../global/wc-cart';
import { isOmnivaTerminalMethod } from '../global/omniva';
import { txt } from '../global/text';
import { addTokenToValue } from '../global/utils';
import { debug, enableStateDebug } from '../global/debug';

export const Block = ({ className }) => {
    const [selectedRateId, setSelectedRateId] = useState('');
    const [showBlock, setShowBlock] = useState(addTokenToValue(false)); //Need token to avoid undetected change when changing true>false>true in other useEffect functions

    enableStateDebug('Block show', showBlock);
    enableStateDebug('Selected rate ID', selectedRateId);

    const shippingRates = useSelect((select) => {
        const store = select('wc/store/cart');
        return store.getCartData().shippingRates;
    });

    useEffect(() => {
        setShowBlock(addTokenToValue(false));
        if ( shippingRates.length ) {
            const activeRates = getActiveShippingRates(shippingRates);
            debug('Received ' + activeRates.length + ' active rates:', activeRates);
            for ( let i = 0; i < activeRates.length; i++ ) {
                if ( ! activeRates[i].rate_id ) {
                    continue;
                }
                if ( isOmnivaTerminalMethod(activeRates[i].rate_id) && activeRates[i].selected ) {
                    setShowBlock(addTokenToValue(true));
                }
                if ( activeRates[i].selected ) {
                    setSelectedRateId(activeRates[i].rate_id);
                }
            }
        } else {
            debug('Empty param: shippingRates');
        }
    }, [
        shippingRates
    ]);

    if ( ! showBlock.value ) {
        return <></>
    }

    return (
        <div className={'wc-block-components-totals-wrapper'}>
            <span className={'wc-block-components-totals-item'}>{txt.cart_terminal_info}</span>
        </div>
    );
};
