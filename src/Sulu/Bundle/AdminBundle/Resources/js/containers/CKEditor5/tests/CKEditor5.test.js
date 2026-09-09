// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import log from 'loglevel';
import {ClassicEditor} from '@ckeditor/ckeditor5-editor-classic';
import CKEditor5 from '../CKEditor5';
import configRegistry from '../registries/configRegistry';
import pluginRegistry from '../registries/pluginRegistry';

jest.mock('../registries/pluginRegistry', () => ({
    getPlugins: jest.fn(),
    keys: [],
}));

jest.mock('../registries/configRegistry', () => ({
    getConfigs: jest.fn(),
    keys: [],
}));

jest.mock('@ckeditor/ckeditor5-editor-classic', () => ({
    ClassicEditor: {
        create: jest.fn(),
    },
}));

jest.mock('loglevel', () => ({
    error: jest.fn(),
    warn: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const textEditorConfig = {enterMode: 'p', features: [], tags: ['strong']};

beforeEach(() => {
    pluginRegistry.getPlugins.mockReturnValue([]);
    configRegistry.getConfigs.mockReturnValue([]);
});

const defaultEditor = {
    editing: {
        view: {
            document: {
                on: jest.fn(),
            },
        },
    },
    model: {
        document: {
            on: jest.fn(),
        },
    },
    ui: {
        element: {
            classList: {
                add: jest.fn(),
                remove: jest.fn(),
            },
        },
    },
    getData: jest.fn(),
    setData: jest.fn(),
    isReadOnly: false,
    enableReadOnlyMode: () => {
    },
    disableReadOnlyMode: () => {
    },
};

test('Create a CKEditor5 instance', () => {
    const editor = {
        ...defaultEditor,
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    const locale = observable.box('en');

    mount(
        <CKEditor5 config={textEditorConfig} locale={locale} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />
    );

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        licenseKey: 'GPL',
        sulu: {
            locale: 'en',
        },
        toolbar: [],
    }));
});

test('Ask the registries for the tags and features enabled by the config', () => {
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));

    mount(
        <CKEditor5
            config={{enterMode: 'p', features: ['align'], tags: ['strong', 'table']}}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(pluginRegistry.getPlugins).toHaveBeenCalledWith(['strong', 'table', 'align']);
    expect(configRegistry.getConfigs).toHaveBeenCalledWith(['strong', 'table', 'align']);
});

test('Pass the text editor config to every registered config', () => {
    const config = jest.fn(() => ({}));
    configRegistry.getConfigs.mockReturnValue([config]);
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));

    mount(
        <CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />
    );

    expect(config).toHaveBeenCalledWith(expect.objectContaining({toolbar: []}), textEditorConfig);
});

test('Warn about a tag or feature no plugin or config is registered for', () => {
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    pluginRegistry.keys = ['strong'];
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    configRegistry.keys = ['strong'];

    mount(
        <CKEditor5
            config={{enterMode: 'p', features: ['align'], tags: ['strong', 'marquee']}}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('align, marquee'));
});

test('Create a CKEditor5 instance with an additional plugin', () => {
    const Plugin = class {};
    pluginRegistry.getPlugins.mockReturnValue([Plugin]);

    const config = jest.fn((config) => ({
        toolbar: [...config.toolbar, 'plugin1', 'plugin2'],
    }));
    configRegistry.getConfigs.mockReturnValue([config]);

    const editor = {
        ...defaultEditor,
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        plugins: expect.arrayContaining([Plugin]),
        toolbar: ['plugin1', 'plugin2'],
    }));
});

test('Set data on editor when value is updated', () => {
    const editor = {
        ...defaultEditor,
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).toHaveBeenCalledWith('<p>Test</p>');
    });
});

test('Do not set data on editor when value is not changed when props change', () => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p>Test</p>'),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).not.toHaveBeenCalled();
    });
});

test('Do not set data on editor when value and editorData is undefined', () => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({});

        expect(editor.setData).not.toHaveBeenCalled();
    });
});

test('Set disabled class and isReadOnly property to CKEditor5', () => {
    const editor = {
        ...defaultEditor,
        isReadOnly: false,
        enableReadOnlyMode: () => {
            editor.isReadOnly = true;
        },
        disableReadOnlyMode: () => {
            editor.isReadOnly = false;
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} disabled={true} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        expect(ClassicEditor.create).toHaveBeenCalled();
        expect(editor.ui.element.classList.add).toHaveBeenCalledWith('disabled');
        expect(editor.isReadOnly).toEqual(true);
    });
});

test('Call onChange prop when something changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toHaveBeenCalledWith('test');
    });
});

test('Call onChange prop with undefined if editor is empty', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(''),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toHaveBeenCalledWith(undefined);
    });
});

test('Do not call onChange prop when nothing changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).not.toHaveBeenCalled();
    });
});

test('Call onBlur prop when CKEditor5 fires its blur event', () => {
    const blurSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} onBlur={blurSpy} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.editing.view.document.on.mock.calls[0][1]();
        expect(blurSpy).toHaveBeenCalled();
    });
});

test('Call onFocus prop when CKEditor5 fires its focus event', () => {
    const focusSpy = jest.fn();
    const target = new EventTarget();
    const querySelectorSpy = jest.fn().mockReturnValue(target);
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
        ui: {
            element: {
                querySelector: querySelectorSpy,
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 config={textEditorConfig} onChange={jest.fn()} onFocus={focusSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.editing.view.document.on.mock.calls[0][1]();
        expect(focusSpy).toHaveBeenCalledWith({target});
        expect(querySelectorSpy).toHaveBeenCalledWith('div[contenteditable="true"]');
    });
});
