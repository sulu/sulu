// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
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

jest.mock('../../../utils/Translator');

const textEditorConfig = {enterMode: 'p', attributes: [], tags: ['strong']};
const lineBreakTextEditorConfig = {enterMode: 'br', attributes: ['style'], tags: ['strong']};

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
    destroy: jest.fn().mockReturnValue(Promise.resolve()),
    isReadOnly: false,
    enableReadOnlyMode: () => {
    },
    disableReadOnlyMode: () => {
    },
};

beforeEach(() => {
    jest.clearAllMocks();
    pluginRegistry.getPlugins.mockReturnValue([]);
    configRegistry.getConfigs.mockReturnValue([]);
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    pluginRegistry.keys = [];
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    configRegistry.keys = [];
});

test('Create a CKEditor5 instance', async() => {
    const editor = {
        ...defaultEditor,
    };
    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const locale = observable.box('en');

    render(
        <CKEditor5
            config={textEditorConfig}
            locale={locale}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        licenseKey: 'GPL',
        sulu: {
            locale: 'en',
        },
        toolbar: [],
    }));

    await editorPromise;
});

test('Ask the registries for the tags and attributes enabled by the config', () => {
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));

    render(
        <CKEditor5
            config={{enterMode: 'p', attributes: ['style'], tags: ['strong', 'table']}}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(pluginRegistry.getPlugins).toHaveBeenCalledWith(['strong', 'table', 'style']);
    expect(configRegistry.getConfigs).toHaveBeenCalledWith(['strong', 'table', 'style']);
});

test('Pass the text editor config to every registered config', () => {
    const config = jest.fn(() => ({}));
    configRegistry.getConfigs.mockReturnValue([config]);
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));

    render(
        <CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />
    );

    expect(config).toHaveBeenCalledWith(expect.objectContaining({toolbar: []}), textEditorConfig);
});

test('Warn about a tag or attribute no plugin or config is registered for', () => {
    ClassicEditor.create.mockReturnValue(Promise.resolve({...defaultEditor}));
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    pluginRegistry.keys = ['strong'];
    // $FlowFixMe: the registries are mocked with a plain object in this test file
    configRegistry.keys = ['strong'];

    render(
        <CKEditor5
            config={{enterMode: 'p', attributes: ['style'], tags: ['strong', 'marquee']}}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(log.warn).toHaveBeenCalledWith(expect.stringContaining('marquee, style'));
});

test('Create a CKEditor5 instance with an additional plugin', async() => {
    const Plugin = class {};
    pluginRegistry.getPlugins.mockReturnValue([Plugin]);

    const config = jest.fn((config) => ({
        toolbar: [...config.toolbar, 'plugin1', 'plugin2'],
    }));
    configRegistry.getConfigs.mockReturnValue([config]);

    const editor = {
        ...defaultEditor,
    };
    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    render(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        plugins: expect.arrayContaining([Plugin]),
        toolbar: ['plugin1', 'plugin2'],
    }));

    await editorPromise;
});

test('Set data on editor when value is updated', async() => {
    const editor = {
        ...defaultEditor,
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(
        <CKEditor5
            config={textEditorConfig}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    await editorPromise;

    rerender(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    expect(editor.setData).toHaveBeenCalledWith('<p>Test</p>');
});

test('Do not set data on editor when value is not changed when props change', async() => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p>Test</p>'),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(
        <CKEditor5
            config={textEditorConfig}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value="<p>Test</p>"
        />
    );

    await editorPromise;

    editor.setData.mockClear();
    rerender(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    expect(editor.setData).not.toHaveBeenCalled();
});

test('Do not set data on editor when value and editorData is undefined', async() => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(
        <CKEditor5
            config={textEditorConfig}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    await editorPromise;

    editor.setData.mockClear();
    rerender(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(editor.setData).not.toHaveBeenCalled();
});

test('Set disabled class and isReadOnly property to CKEditor5', async() => {
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

    render(
        <CKEditor5
            config={textEditorConfig}
            disabled={true}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    await editorPromise;

    expect(ClassicEditor.create).toHaveBeenCalled();
    expect(editor.ui.element.classList.add).toHaveBeenCalledWith('disabled');
    expect(editor.isReadOnly).toEqual(true);
});

test('Call onChange prop when something changed', async() => {
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

    render(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    await editorPromise;

    editor.model.document.on.mock.calls[0][1]();
    expect(changeSpy).toHaveBeenCalledWith('test');
});

test('Call onChange prop with undefined if editor is empty', async() => {
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

    render(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    await editorPromise;

    editor.model.document.on.mock.calls[0][1]();
    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Do not call onChange prop when nothing changed', async() => {
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

    render(<CKEditor5 config={textEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    await editorPromise;

    editor.model.document.on.mock.calls[0][1]();
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Call onBlur prop when CKEditor5 fires its blur event', async() => {
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

    render(<CKEditor5 config={textEditorConfig} onBlur={blurSpy} onChange={jest.fn()} value={undefined} />);

    await editorPromise;

    editor.editing.view.document.on.mock.calls[0][1]();
    expect(blurSpy).toHaveBeenCalled();
});

test('Call onFocus prop when CKEditor5 fires its focus event', async() => {
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

    render(<CKEditor5 config={textEditorConfig} onChange={jest.fn()} onFocus={focusSpy} value={undefined} />);

    await editorPromise;

    editor.editing.view.document.on.mock.calls[0][1]();
    expect(focusSpy).toHaveBeenCalledWith({target});
    expect(querySelectorSpy).toHaveBeenCalledWith('div[contenteditable="true"]');
});

test('Store the value of a line break config without paragraph styles', async() => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p style="text-align:center;">one</p>'),
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

    render(
        <CKEditor5 config={lineBreakTextEditorConfig} onBlur={jest.fn()} onChange={changeSpy} value={undefined} />
    );

    await editorPromise;

    editor.model.document.on.mock.calls[0][1]();
    expect(changeSpy).toHaveBeenLastCalledWith('one');

    editor.getData.mockReturnValue(
        '<p style="text-align:center;">one</p><p style="text-align:right;">two <span style="color:red;">red</span></p>'
    );
    editor.model.document.on.mock.calls[0][1]();
    expect(changeSpy).toHaveBeenLastCalledWith(
        '<!--p-->one<!--/p--><br></br><!--p-->two <span style="color:red;">red</span><!--/p-->'
    );
});

test('Load the value of a line break config into the editor as unstyled paragraphs', async() => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p>other</p>'),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(
        <CKEditor5 config={lineBreakTextEditorConfig} onBlur={jest.fn()} onChange={jest.fn()} value="one<br>two" />
    );

    await editorPromise;

    expect(editor.setData).toHaveBeenLastCalledWith('<p>one<br>two</p>');

    rerender(
        <CKEditor5
            config={lineBreakTextEditorConfig}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            value="<!--p-->one<!--/p--><br></br><!--p-->two<!--/p-->"
        />
    );

    expect(editor.setData).toHaveBeenLastCalledWith('<p>one</p><p>two</p>');
});
