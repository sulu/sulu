// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import Dropzone from 'react-dropzone';
import FileUploadButton from '../FileUploadButton';

test('Render a FileUploadButton', () => {
    expect(render(<FileUploadButton onUpload={jest.fn()}>Upload something!</FileUploadButton>)).toMatchSnapshot();
});

test('Render a disabled FileUploadButton', () => {
    expect(render(
        <FileUploadButton disabled={true} onUpload={jest.fn()}>Upload something!</FileUploadButton>
    )).toMatchSnapshot();
});

test('Render a FileUploadButton with other skin and icon', () => {
    expect(render(
        <FileUploadButton icon="su-image" onUpload={jest.fn()} skin="link">Upload something!</FileUploadButton>
    )).toMatchSnapshot();
});

test('Call onUpload callback when a file is uploaded', () => {
    const uploadSpy = jest.fn();

    const fileUploadButton = mount(<FileUploadButton onUpload={uploadSpy}>Upload something!</FileUploadButton>);

    const testFileData = {name: 'test-file'};
    fileUploadButton.find(Dropzone).prop('onDrop')([testFileData]);

    expect(uploadSpy).toBeCalledTimes(1);
    expect(uploadSpy).toBeCalledWith(testFileData);
});
