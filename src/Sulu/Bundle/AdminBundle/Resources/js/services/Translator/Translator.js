// @flow
import type {TranslationMap} from './types';

class Translator {
    translations: ?TranslationMap;

    set(translations: TranslationMap) {
        this.translations = translations;
    }

    clear() {
        this.translations = null;
    }

    translate(key: string) {
        if (!this.translations || !(key in this.translations)) {
            throw new Error('Translation for key "' + key + '" not found');
        }

        return this.translations[key];
    }
}

export default new Translator();
