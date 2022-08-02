// @flow
import React from 'react';
import {fireEvent, render, screen, waitFor, act} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Call onUpload callback when a file is uploaded', async() => {
    const uploadSpy = jest.fn();
    const testFile = new File(['test-file'], 'test-file', {type: 'image/png'});

    const {container, debug} = render(<FileUploadButton onUpload={uploadSpy}>Upload something!</FileUploadButton>);
    debug();
    const dropzone = screen.queryByText('Upload something!');
    const input = container.querySelector('input');

    await act(async() => {
        await waitFor(() => {
            userEvent.upload(input, testFile);
        });
    });

    // fireEvent.drop(dropzone, testFile);

    expect(uploadSpy).toBeCalledTimes(1);
    expect(uploadSpy).toBeCalledWith(testFile);
});
