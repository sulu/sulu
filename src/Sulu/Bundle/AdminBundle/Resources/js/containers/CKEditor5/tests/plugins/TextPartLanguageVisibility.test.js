// @flow
import {_stringifyLanguageAttribute} from '@ckeditor/ckeditor5-language';
import TextPartLanguageVisibility from '../../plugins/TextPartLanguageVisibility';

jest.mock('../../../../utils', () => ({
    translate: jest.fn((key, parameters) => (parameters ? key + ':' + parameters.language : key)),
}));

function setupConverter(languages) {
    let registeredView: any = null;
    const editor = {
        config: {
            get: jest.fn((key) => (key === 'language.textPartLanguage' ? languages : undefined)),
        },
        conversion: {
            for: jest.fn(() => ({
                attributeToElement: jest.fn((definition) => {
                    registeredView = definition.view;
                }),
            })),
        },
    };

    TextPartLanguageVisibility.prototype.afterInit.call({editor});

    return {editor, view: registeredView};
}

test('Register an editing downcast converter for the language attribute', () => {
    const {editor} = setupConverter([{title: 'English', languageCode: 'en'}]);

    expect(editor.conversion.for).toHaveBeenCalledWith('editingDowncast');
});

test('Enrich the editing view span with a translated language title', () => {
    const {view} = setupConverter([
        {title: 'English', languageCode: 'en'},
        {title: 'Arabic', languageCode: 'ar'},
    ]);

    const createAttributeElement = jest.fn();
    const data = {item: {is: (type) => type === '$textProxy'}};

    view(_stringifyLanguageAttribute('ar', 'rtl'), {writer: {createAttributeElement}}, data);

    expect(createAttributeElement).toHaveBeenCalledWith('span', {
        'class': 'sulu-text-part-language',
        'dir': 'rtl',
        'lang': 'ar',
        'title': 'sulu_admin.text_part_language_marker:Arabic',
    });
});

test('Fall back to the language code when no configured title matches', () => {
    const {view} = setupConverter([{title: 'English', languageCode: 'en'}]);

    const createAttributeElement = jest.fn();
    const data = {item: {is: (type) => type === '$textProxy'}};

    view(_stringifyLanguageAttribute('fr', 'ltr'), {writer: {createAttributeElement}}, data);

    expect(createAttributeElement).toHaveBeenCalledWith('span', expect.objectContaining({
        'lang': 'fr',
        'title': 'sulu_admin.text_part_language_marker:fr',
    }));
});

test('Ignore elements that are neither a text proxy nor the document selection', () => {
    const {view} = setupConverter([{title: 'English', languageCode: 'en'}]);

    const createAttributeElement = jest.fn();
    const data = {item: {is: () => false}};

    const result = view(_stringifyLanguageAttribute('en', 'ltr'), {writer: {createAttributeElement}}, data);

    expect(result).toBeUndefined();
    expect(createAttributeElement).not.toHaveBeenCalled();
});
