/* eslint-disable flowtype/require-valid-file-annotation */
import {setTranslations, clearTranslations, translate} from '../Translator';

beforeEach(() => {
    clearTranslations();
});

test('Translator should translate translations', () => {
    setTranslations({'save': 'Save', 'delete': 'Delete'});

    expect(translate('save')).toBe('Save');
    expect(translate('delete')).toBe('Delete');
});

test('Translator should return key when translating non-existing keys', () => {
    expect(translate('not-existing')).toBe('not-existing');
});
