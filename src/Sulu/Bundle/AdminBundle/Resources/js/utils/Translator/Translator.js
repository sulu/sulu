// @flow
import log from 'loglevel';
import IntlMessageFormat from 'intl-messageformat';
import type {TranslationMap} from './types';

let translationMap;

function setTranslations(translations: TranslationMap, locale: string) {
    translationMap = Object.keys(translations).reduce((messages, translationKey) => {
        // TODO add locale for correct translation of numbers, dates, ...
        messages[translationKey] = new IntlMessageFormat(translations[translationKey], locale);
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
    setTranslations,
    translate,
};
