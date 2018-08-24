// @flow
import log from 'loglevel';
import IntlMessageFormat from 'intl-messageformat';
import type {TranslationMap} from './types';

let translationMap;
let currentLocale;

function setLocale(locale: string) {
    currentLocale = locale;
}

function setTranslations(translations: TranslationMap) {
    translationMap = Object.keys(translations).reduce((messages, translationKey) => {
        // TODO add locale for correct translation of numbers, dates, ...
        messages[translationKey] = new IntlMessageFormat(translations[translationKey], currentLocale);
        return messages;
    }, {});
}

function clearTranslations() {
    translationMap = null;
}

function translate(key: string, parameters: ?Object) {
    if (!translationMap || !(key in translationMap)) {
        log.warn('The translation key "' + key + '" has not been translated. The key itself will be returned instead.');
        return key;
    }

    return translationMap[key].format(parameters);
}

export {
    clearTranslations,
    setLocale,
    setTranslations,
    translate,
};
