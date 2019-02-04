// @flow
import React from 'react';
import {mount} from 'enzyme';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import CKEditor5 from '../CKEditor5';

jest.mock('@ckeditor/ckeditor5-editor-classic/src/classiceditor', () => ({
    create: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Create a CKEditor5 instance', () => {
    const editor = {
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
        setData: jest.fn(),
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalledWith(expect.anything(), expect.objectContaining({
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
    }));
});

test('Create a CKEditor5 instance with given formats', () => {
    const editor = {
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
        setData: jest.fn(),
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 formats={['h2', 'h3']} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalledWith(expect.anything(), expect.objectContaining({
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
            ],
        },
    }));
});

test('Set data on editor when value is updated', () => {
    const editor = {
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
        element: {
            classList: {
                add: jest.fn(),
                remove: jest.fn(),
            },
        },
        getData: jest.fn(),
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).toBeCalledWith('<p>Test</p>');
    });
});

test('Do not set data on editor when value is not changed when props change', () => {
    const editor = {
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
        element: {
            classList: {
                add: jest.fn(),
                remove: jest.fn(),
            },
        },
        getData: jest.fn().mockReturnValue('<p>Test</p>'),
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).not.toBeCalled();
    });
});

test('Do not set data on editor when value and editorData is undefined', () => {
    const editor = {
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
        element: {
            classList: {
                add: jest.fn(),
                remove: jest.fn(),
            },
        },
        getData: jest.fn().mockReturnValue(),
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({});

        expect(editor.setData).not.toBeCalled();
    });
});

test('Set disabled class and isReadOnly property to CKEditor5', () => {
    const editor = {
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
        element: {
            classList: {
                add: jest.fn(),
            },
        },
        isReadOnly: false,
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 disabled={true} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        expect(ClassicEditor.create).toBeCalled();
        expect(editor.element.classList.add).toBeCalledWith('disabled');
        expect(editor.isReadOnly).toEqual(true);
    });
});

test('Call onChange prop when something changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        editing: {
            view: {
                document: {
                    on: jest.fn(),
                },
            },
        },
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toBeCalledWith('test');
    });
});

test('Call onChange prop with undefined if editor only contains an empty paragraph', () => {
    const changeSpy = jest.fn();
    const editor = {
        editing: {
            view: {
                document: {
                    on: jest.fn(),
                },
            },
        },
        getData: jest.fn().mockReturnValue('<p>&nbsp;</p>'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toBeCalledWith(undefined);
    });
});

test('Do not call onChange prop when nothing changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        editing: {
            view: {
                document: {
                    on: jest.fn(),
                },
            },
        },
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).not.toBeCalled();
    });
});

test('Call onBlur prop when CKEditor5 fires its blur event', () => {
    const blurSpy = jest.fn();
    const editor = {
        editing: {
            view: {
                document: {
                    on: jest.fn(),
                },
            },
        },
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
        setData: jest.fn(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={blurSpy} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.editing.view.document.on.mock.calls[0][1]();
        expect(blurSpy).toBeCalled();
    });
});
