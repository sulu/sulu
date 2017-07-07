/* eslint-disable flowtype/require-valid-file-annotation */
import translator from '../Translator';

beforeEach(() => {
    translator.clear();
});

test('Translator should set translations', () => {
    const translations = {'save': 'Save'};

    translator.set(translations);

    expect(translator.translations).toBe(translations);
});

test('Translator should clear translations', () => {
    translator.set({'save': 'Save'});
    translator.clear();

    expect(translator.translations).toBe(null);
});

test('Translator should translate translations', () => {
    translator.set({'save': 'Save', 'delete': 'Delete'});

    expect(translator.translate('save')).toBe('Save');
    expect(translator.translate('delete')).toBe('Delete');
});

test('Translator should throw error when translating non-existing keys', () => {
    expect(() => translator.translate('not-existing')).toThrow(/not-existing/);
});
