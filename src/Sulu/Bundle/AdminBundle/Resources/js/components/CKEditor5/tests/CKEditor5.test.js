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
        setData: jest.fn(),
        model: {
            document: {
                on: jest.fn(),
            },
        },
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalled();
});

test('Call onChange prop when something changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        setData: jest.fn(),
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

    mount(<CKEditor5 onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toBeCalledWith('test');
    });
});

test('Do not call onChange prop when nothing changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        setData: jest.fn(),
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

    mount(<CKEditor5 onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).not.toBeCalled();
    });
});
