// @flow
import {Plugin} from '@ckeditor/ckeditor5-core';
import {TextPartLanguageEditing, _parseLanguageAttribute} from '@ckeditor/ckeditor5-language';
import {translate} from '../../../../utils';

const LANGUAGE_MARK_CLASS = 'sulu-text-part-language';

export default class TextPartLanguageVisibility extends Plugin {
    static get requires() {
        return [TextPartLanguageEditing];
    }

    static get pluginName() {
        return 'TextPartLanguageVisibility';
    }

    afterInit() {
        const editor = this.editor;

        const titles = new Map();
        (editor.config.get('language.textPartLanguage') || []).forEach(({title, languageCode}) => {
            titles.set(languageCode, title);
        });

        editor.conversion.for('editingDowncast').attributeToElement({
            model: 'language',
            view: (attributeValue, {writer}, data) => {
                if (!attributeValue) {
                    return;
                }

                if (!data.item.is('$textProxy') && !data.item.is('documentSelection')) {
                    return;
                }

                const {languageCode, textDirection} = _parseLanguageAttribute(attributeValue);

                return writer.createAttributeElement('span', {
                    'class': LANGUAGE_MARK_CLASS,
                    'dir': textDirection,
                    'lang': languageCode,
                    'title': translate('sulu_admin.text_part_language_marker', {
                        language: titles.get(languageCode) || languageCode,
                    }),
                });
            },
            converterPriority: 'high',
        });
    }
}
