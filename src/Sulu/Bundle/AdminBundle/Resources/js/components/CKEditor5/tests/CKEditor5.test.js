// @flow
import React from 'react';
import {mount} from 'enzyme';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import CKEditor5 from '../CKEditor5';

jest.mock('@ckeditor/ckeditor5-editor-classic/src/classiceditor', () => ({
    create: jest.fn(),
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

    expect(ClassicEditor.create).toBeCalled();
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
