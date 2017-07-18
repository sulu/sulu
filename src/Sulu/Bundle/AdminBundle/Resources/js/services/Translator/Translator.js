// @flow
import type {TranslationMap} from './types';

let translationMap: ?TranslationMap;

function setTranslations(translations: TranslationMap) {
    translationMap = translations;
}

function clearTranslations() {
    translationMap = null;
}

function translate(key: string) {
    if (!translationMap || !(key in translationMap)) {
        return key;
    }

    return translationMap[key];
}

export {setTranslations, clearTranslations, translate};
