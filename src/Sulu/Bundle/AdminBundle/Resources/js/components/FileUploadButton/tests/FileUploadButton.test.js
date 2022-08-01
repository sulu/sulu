// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import Dropzone from 'react-dropzone';
import FileUploadButton from '../FileUploadButton';

test('Render a FileUploadButton', () => {
    const {container} = render(<FileUploadButton onUpload={jest.fn()}>Upload something!</FileUploadButton>);
    expect(container).toMatchSnapshot();
});

test('Render a disabled FileUploadButton', () => {
    const {container} = render(
        <FileUploadButton disabled={true} onUpload={jest.fn()}>Upload something!</FileUploadButton>
    );
    expect(container).toMatchSnapshot();
});

test('Render a FileUploadButton with other skin and icon', () => {
    const {container} = render(
        <FileUploadButton icon="su-image" onUpload={jest.fn()} skin="link">Upload something!</FileUploadButton>
    );
    expect(container).toMatchSnapshot();
});

// test('Call onUpload callback when a file is uploaded', () => {
//     const uploadSpy = jest.fn();
//     const testFile = new File([{name: 'test-file'}], 'test-file');

//     const {container, debug} = render(<FileUploadButton onUpload={uploadSpy}>Upload something!</FileUploadButton>);
//     debug();
//     const dropzone = screen.queryByText('Upload something!');

//     fireEvent.drop(dropzone, testFile);

//     expect(uploadSpy).toBeCalledTimes(1);
//     expect(uploadSpy).toBeCalledWith(testFile);
// });
