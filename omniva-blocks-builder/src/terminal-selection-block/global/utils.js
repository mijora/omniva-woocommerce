export const buildToken = (length) => {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
        counter += 1;
    }
    return result;
};

export const addTokenToValue = (value, tokenLength = 0) => {
    if ( ! tokenLength ) {
        tokenLength = 5;
    }
    return {
        value: value,
        token: buildToken(tokenLength)
    }
};

export const isObjectEmpty = (obj) => {
    for (const prop in obj) {
        if (Object.hasOwn(obj, prop)) {
            return false;
        }
    }
    return true;
};

export const getObjectValue = (obj, key, valueIsNot = null) => {
    if ( isObjectEmpty(obj) || ! (key in obj) ) {
        return valueIsNot;
    }
    return obj[key];
};
