/**
 * Export functions
 */
export const getOmnivaData = () => {
    if ( ! wcSettings || ! wcSettings["omnivalt-blocks_data"] ) {
        return [];
    }

    return wcSettings["omnivalt-blocks_data"];
};

export const isOmnivaTerminalMethod = (methodKey) => {
    for ( let [key, value] of Object.entries(getOmnivaData().methods) ) {
        if ( methodKey == value ) {
            return true;
        }
    }
    return false;
};
