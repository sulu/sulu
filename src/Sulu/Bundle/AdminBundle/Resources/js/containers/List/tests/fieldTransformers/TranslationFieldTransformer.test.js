// @flow
import log from 'loglevel';
import TranslationFieldTransformer from '../../fieldTransformers/TranslationFieldTransformer';
import {translate} from '../../../../utils/Translator';

const translationFieldTransformer = new TranslationFieldTransformer();

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../utils/Translator/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Test undefined', () => {
    expect(translationFieldTransformer.transform(undefined, {}))
        .toBe(null);
});

test('Test transform with prefix', () => {
    expect(translationFieldTransformer.transform('<value>', {prefix: 'sulu_admin.test.'}))
        .toBe('sulu_admin.test.<value>');
    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('"prefix" parameter'));
});

test('Test transform with a placeholder in the key', () => {
    expect(translationFieldTransformer.transform('published', {key: 'sulu_admin.state_{value}'}))
        .toBe('sulu_admin.state_published');
    expect(log.warn).not.toHaveBeenCalled();
    expect(translate).toHaveBeenLastCalledWith('sulu_admin.state_published', {value: 'published'});
});

test('Test transform passes the value to the translation', () => {
    expect(translationFieldTransformer.transform(3, {key: 'sulu_admin.version_label'}))
        .toBe('sulu_admin.version_label');
    expect(translate).toHaveBeenLastCalledWith('sulu_admin.version_label', {value: 3});
});

test('Test transform prefers the key over the prefix', () => {
    expect(translationFieldTransformer.transform('a', {key: 'sulu_admin.key_{value}', prefix: 'sulu_admin.prefix.'}))
        .toBe('sulu_admin.key_a');
});
