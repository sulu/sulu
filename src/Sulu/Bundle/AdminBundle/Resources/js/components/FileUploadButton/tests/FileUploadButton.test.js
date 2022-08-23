// @flow
import React from 'react';
import {render} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FileUploadButton from '../FileUploadButton';

test('Render a FileUploadButton', () => {
    const {container} = render(
        <FileUploadButton onUpload={jest.fn()}>
            Upload something!
        </FileUploadButton>
    );
    expect(container).toMatchSnapshot();
});

test('Render a disabled FileUploadButton', () => {
    const {container} = render(
        <FileUploadButton disabled={true} onUpload={jest.fn()}>
            Upload something!
        </FileUploadButton>
    );
    expect(container).toMatchSnapshot();
});

test('Render a FileUploadButton with other skin and icon', () => {
    const {container} = render(
        <FileUploadButton
            icon="su-image"
            onUpload={jest.fn()}
            skin="link"
        >
            Upload something!
        </FileUploadButton>
    );
    expect(container).toMatchSnapshot();
});

test('Call onUpload callback when a file is uploaded', async() => {
    const uploadSpy = jest.fn();
    const file = new File(['hello'], 'hello.png', {type: 'image/png'});

    const {container} = render(
        <FileUploadButton onUpload={uploadSpy}>
            Upload something!
        </FileUploadButton>
    );

    // eslint-disable-next-line testing-library/no-container
    const input = container.querySelector('input');
    await userEvent.upload(input, file);

    expect(uploadSpy).toBeCalledTimes(1);
    expect(uploadSpy).toBeCalledWith(file);
});

test('Filter dropped files by accept prop', async() => {
    const uploadSpy = jest.fn();
    const rejectedFile = new File(['hello'], 'hello.png', {type: 'image/png'});
    const acceptedFile = new File(['hello'], 'data.json', {type: 'application/json'});

    const {container} = render(
        <FileUploadButton accept="application/json" onUpload={uploadSpy}>
            Upload something!
        </FileUploadButton>
    );

    // eslint-disable-next-line testing-library/no-container
    const input = container.querySelector('input');
    await userEvent.upload(input, rejectedFile);
    expect(uploadSpy).toBeCalledTimes(0);

    await userEvent.upload(input, acceptedFile);
    expect(uploadSpy).toBeCalledWith(acceptedFile);
    expect(uploadSpy).toBeCalledTimes(1);
});
