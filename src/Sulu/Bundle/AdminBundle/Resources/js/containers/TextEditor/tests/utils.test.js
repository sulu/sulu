// @flow
import textEditorConfigRegistry from '../registries/textEditorConfigRegistry';
import {resolveTextEditorConfig} from '../utils';

beforeEach(() => {
    textEditorConfigRegistry.clear();
    textEditorConfigRegistry.add('default', {
        enterMode: 'p',
        features: ['align'],
        tags: ['strong', 'em', 'h2', 'h3'],
    });
    textEditorConfigRegistry.add('mini', {
        enterMode: 'br',
        features: [],
        tags: ['a', 'strong'],
    });
});

test('Resolve to the default config if no config param is given', () => {
    expect(resolveTextEditorConfig({})).toEqual({
        enterMode: 'p',
        features: ['align'],
        tags: ['strong', 'em', 'h2', 'h3'],
    });
});

test('Resolve to the config named by the config param', () => {
    const options = {config: {name: 'config', value: 'mini'}};

    expect(resolveTextEditorConfig(options)).toEqual({
        enterMode: 'br',
        features: [],
        tags: ['a', 'strong'],
    });
});

test('Throw if the config param names a config that does not exist', () => {
    const options = {config: {name: 'config', value: 'teaser'}};

    expect(() => resolveTextEditorConfig(options)).toThrow(/Registered keys: default, mini/);
});

test('Throw if the config param is not a string', () => {
    const options = {config: {name: 'config', value: 4}};

    expect(() => resolveTextEditorConfig(options)).toThrow(/"config" must be a string/);
});

test('Let the deprecated enter_mode param override the config', () => {
    const options = {enter_mode: {name: 'enter_mode', value: 'br'}};

    expect(resolveTextEditorConfig(options).enterMode).toEqual('br');
});

test('Let the deprecated formats param replace the heading tags of the config', () => {
    const options = {
        formats: {
            name: 'formats',
            value: [{name: 'h4'}, {name: 'h5'}],
        },
    };

    expect(resolveTextEditorConfig(options).tags).toEqual(['strong', 'em', 'h4', 'h5']);
});

test('Ignore an empty deprecated formats param', () => {
    const options = {formats: {name: 'formats', value: []}};

    expect(resolveTextEditorConfig(options).tags).toEqual(['strong', 'em', 'h2', 'h3']);
});

test('Throw if the deprecated formats param is not an array', () => {
    const options = {formats: {name: 'formats', value: 'Test'}};

    expect(() => resolveTextEditorConfig(options)).toThrow(/"formats" must be an array of strings/);
});

test('Throw if a name of the deprecated formats param is not a string', () => {
    const options = {
        formats: {
            name: 'formats',
            value: [{name: 'h2'}, {name: 3}],
        },
    };

    expect(() => resolveTextEditorConfig(options)).toThrow(/"formats" must be strings/);
});

test('Ignore a non-heading value in the deprecated formats param', () => {
    // The param never selected anything but heading levels, so a legacy value naming a tag must not enable it.
    const options = {
        formats: {
            name: 'formats',
            value: [{name: 'h4'}, {name: 'table'}, {name: 'a'}],
        },
    };

    expect(resolveTextEditorConfig(options).tags).toEqual(['strong', 'em', 'h4']);
});

test('Keep the config tags when the deprecated formats param holds no heading at all', () => {
    const options = {
        formats: {
            name: 'formats',
            value: [{name: 'table'}],
        },
    };

    expect(resolveTextEditorConfig(options).tags).toEqual(['strong', 'em']);
});
