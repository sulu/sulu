// @flow
import type {TranslationMap} from './types';
import log from 'loglevel';

let translationMap: ?TranslationMap;

function setTranslations(translations: TranslationMap) {
    translationMap = translations;
}

function clearTranslations() {
    translationMap = null;
}

function translate(key: string) {
    if (!translationMap || !(key in translationMap)) {
        log.warn('The translation key "' + key + '" has not been translated. The key itself will be returned instead.');
        return key;
    }

    return translationMap[key];
}

export {setTranslations, clearTranslations, translate};
