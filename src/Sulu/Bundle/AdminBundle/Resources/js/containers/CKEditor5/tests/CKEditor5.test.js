// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {ClassicEditor} from '@ckeditor/ckeditor5-editor-classic';
import CKEditor5 from '../CKEditor5';
import configRegistry from '../registries/configRegistry';
import pluginRegistry from '../registries/pluginRegistry';

jest.mock('../registries/pluginRegistry', () => ({
    plugins: [],
}));

jest.mock('../registries/configRegistry', () => ({
    configs: [],
}));

jest.mock('@ckeditor/ckeditor5-editor-classic', () => ({
    ClassicEditor: {
        create: jest.fn(),
    },
}));

jest.mock('../../../utils/Translator');

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
    pluginRegistry.plugins = [];
    configRegistry.configs = [];
});

test('Create a CKEditor5 instance', async() => {
    const editor = {
        ...defaultEditor,
    };
    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const locale = observable.box('en');

    render(<CKEditor5 locale={locale} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        heading: {
            options: [
                {
                    class: 'ck-heading_paragraph',
                    model: 'paragraph',
                    title: 'sulu_admin.paragraph',
                },
                {
                    class: 'ck-heading_heading2',
                    model: 'heading2',
                    title: 'sulu_admin.heading2',
                    view: 'h2',
                },
                {
                    class: 'ck-heading_heading3',
                    model: 'heading3',
                    title: 'sulu_admin.heading3',
                    view: 'h3',
                },
                {
                    class: 'ck-heading_heading4',
                    model: 'heading4',
                    title: 'sulu_admin.heading4',
                    view: 'h4',
                },
                {
                    class: 'ck-heading_heading5',
                    model: 'heading5',
                    title: 'sulu_admin.heading5',
                    view: 'h5',
                },
                {
                    class: 'ck-heading_heading6',
                    model: 'heading6',
                    title: 'sulu_admin.heading6',
                    view: 'h6',
                },
            ],
        },
        sulu: {
            locale: 'en',
        },
    }));

    await editorPromise;
});

test('Create a CKEditor5 instance with an additional plugin', async() => {
    const Plugin = class {};
    pluginRegistry.plugins = [Plugin];

    const config = jest.fn((config) => ({
        toolbar: [...config.toolbar, 'plugin1', 'plugin2'],
    }));
    configRegistry.configs = [config];

    const editor = {
        ...defaultEditor,
    };
    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    render(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        plugins: expect.arrayContaining([Plugin]),
        toolbar: expect.arrayContaining(['bold', 'italic', 'underline', 'plugin1', 'plugin2']),
    }));

    await editorPromise;
});

test('Create a CKEditor5 instance with given formats', async() => {
    const editor = {
        ...defaultEditor,
    };
    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    render(<CKEditor5 formats={['h1', 'h2', 'h3']} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toHaveBeenCalledWith(expect.objectContaining({
        attachTo: expect.anything(),
        heading: {
            options: [
                {
                    class: 'ck-heading_paragraph',
                    model: 'paragraph',
                    title: 'sulu_admin.paragraph',
                },
                {
                    class: 'ck-heading_heading1',
                    model: 'heading1',
                    title: 'sulu_admin.heading1',
                    view: 'h1',
                },
                {
                    class: 'ck-heading_heading2',
                    model: 'heading2',
                    title: 'sulu_admin.heading2',
                    view: 'h2',
                },
                {
                    class: 'ck-heading_heading3',
                    model: 'heading3',
                    title: 'sulu_admin.heading3',
                    view: 'h3',
                },
            ],
        },
        sulu: {
            locale: undefined,
        },
    }));

    await editorPromise;
});

test('Set data on editor when value is updated', async() => {
    const editor = {
        ...defaultEditor,
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    await editorPromise;

    rerender(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    expect(editor.setData).toHaveBeenCalledWith('<p>Test</p>');
});

test('Do not set data on editor when value is not changed when props change', async() => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p>Test</p>'),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    await editorPromise;

    editor.setData.mockClear();
    rerender(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    expect(editor.setData).not.toHaveBeenCalled();
});

test('Do not set data on editor when value and editorData is undefined', async() => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const {rerender} = render(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    await editorPromise;

    editor.setData.mockClear();
    rerender(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

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

    render(<CKEditor5 disabled={true} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

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

    render(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

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

    render(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

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

    render(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

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

    render(<CKEditor5 onBlur={blurSpy} onChange={jest.fn()} value={undefined} />);

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

    render(<CKEditor5 onChange={jest.fn()} onFocus={focusSpy} value={undefined} />);

    await editorPromise;

    editor.editing.view.document.on.mock.calls[0][1]();
    expect(focusSpy).toHaveBeenCalledWith({target});
    expect(querySelectorSpy).toHaveBeenCalledWith('div[contenteditable="true"]');
});
