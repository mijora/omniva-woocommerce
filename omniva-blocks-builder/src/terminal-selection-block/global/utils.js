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

export const insertAfter = (elem, newElem, afterElem = null) => {
    afterElem = (afterElem) ? afterElem.nextSibling : elem.firstChild;
    elem.insertBefore(newElem, afterElem);
};

export const getJsonDataFromUrl = async (url) => {
    let responseData = null;
    try {
        let response = await fetch(url);
        responseData = await response.json();
    } catch( error ) {
        console.error('OMNIVA UTILS:', error);
    }
    return responseData;
};

export const findArrayElemByObjProp = (elemsArray, prop, value) => {
    for ( let i = 0; i < elemsArray.length; i++ ) {
        if ( elemsArray[i][prop] === value ) {
            return elemsArray[i];
        }
    }
    return null;
};
