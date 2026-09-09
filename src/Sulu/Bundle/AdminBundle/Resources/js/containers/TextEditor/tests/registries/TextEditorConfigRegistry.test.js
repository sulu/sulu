// @flow
import textEditorConfigRegistry from '../../registries/textEditorConfigRegistry';

beforeEach(() => {
    textEditorConfigRegistry.clear();
});

test('Add and get a config', () => {
    const config = {enterMode: 'p', features: ['align'], tags: ['strong']};

    textEditorConfigRegistry.add('default', config);

    expect(textEditorConfigRegistry.has('default')).toEqual(true);
    expect(textEditorConfigRegistry.get('default')).toEqual(config);
});

test('Clear all configs', () => {
    textEditorConfigRegistry.add('default', {enterMode: 'p', features: [], tags: []});
    textEditorConfigRegistry.clear();

    expect(textEditorConfigRegistry.has('default')).toEqual(false);
});

test('Throw if a key is used twice', () => {
    textEditorConfigRegistry.add('default', {enterMode: 'p', features: [], tags: []});

    expect(() => textEditorConfigRegistry.add('default', {enterMode: 'br', features: [], tags: []}))
        .toThrow(/"default" has already been used/);
});

test('Throw with the registered keys if a config does not exist', () => {
    textEditorConfigRegistry.add('default', {enterMode: 'p', features: [], tags: []});
    textEditorConfigRegistry.add('mini', {enterMode: 'br', features: [], tags: []});

    expect(() => textEditorConfigRegistry.get('teaser')).toThrow(/Registered keys: default, mini/);
});
