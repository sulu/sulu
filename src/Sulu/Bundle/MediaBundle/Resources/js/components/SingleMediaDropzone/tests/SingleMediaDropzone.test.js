// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SingleMediaDropzone from '../SingleMediaDropzone';

jest.mock('react-dropzone', () => {
    const React = require('react');

    return jest.fn(function DropzoneMock(props) {
        return (
            <div data-testid="dropzone">
                {props.children({
                    getInputProps: () => ({'data-testid': 'dropzone-input'}),
                    getRootProps: (rootProps = {}) => ({'data-testid': 'dropzone-root', ...rootProps}),
                })}
            </div>
        );
    });
});

const dropzone = ((jest.requireMock('react-dropzone'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

let OriginalImage;
let lastImageInstance: any;

beforeEach(() => {
    jest.clearAllMocks();
    lastImageInstance = undefined;

    OriginalImage = window.Image;
    window.Image = class MockImage {
        onerror: ?() => void;
        onload: ?() => void;
        src: ?string;

        constructor() {
            this.onerror = undefined;
            this.onload = undefined;
            this.src = undefined;
            lastImageInstance = (this: any);
        }
    };
});

afterEach(() => {
    window.Image = OriginalImage;
});

function getLastDropzoneProps(): any {
    return getLatestMockProps(dropzone);
}

test('Render a SingleMediaDropzone', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the default empty icon', () => {
    const {asFragment} = render(<SingleMediaDropzone image={undefined} onDrop={jest.fn()} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the passed empty icon', () => {
    const {asFragment} = render(<SingleMediaDropzone emptyIcon="su-user" image={undefined} onDrop={jest.fn()} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with an error text', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            emptyIcon="su-user"
            errorText="some-custom-error-message"
            image={undefined}
            onDrop={jest.fn()}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a loader if image has not been loaded yet', () => {
    const {asFragment} = render(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);
    expect(lastImageInstance).toBeDefined();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded after an error occured before', () => {
    const {asFragment} = render(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);
    expect(lastImageInstance).toBeDefined();

    act(() => {
        if (lastImageInstance && lastImageInstance.onerror) {
            lastImageInstance.onerror();
        }
        if (lastImageInstance && lastImageInstance.onload) {
            lastImageInstance.onload();
        }
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded yet', () => {
    const {asFragment} = render(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);
    expect(lastImageInstance).toBeDefined();

    act(() => {
        if (lastImageInstance && lastImageInstance.onload) {
            lastImageInstance.onload();
        }
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a MimeTypeIndicator if an error appeared during image loading', () => {
    const {asFragment} = render(
        <SingleMediaDropzone emptyIcon="su-user" image="test.jpg" mimeType="video/x-m4v" onDrop={jest.fn()} />
    );
    expect(lastImageInstance).toBeDefined();

    act(() => {
        if (lastImageInstance && lastImageInstance.onerror) {
            lastImageInstance.onerror();
        }
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone in disabled state', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            disabled={true}
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the round skin', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            skin="round"
            uploading={false}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone while uploading', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={50}
            uploading={true}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render img tag for image preview', () => {
    render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    expect(screen.getByRole('img')).toBeInTheDocument();
});

test('Component pass correct props to Dropzone component', () => {
    render(
        <SingleMediaDropzone
            accept="application/json"
            disabled={true}
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            uploading={false}
        />
    );

    expect(getLastDropzoneProps()).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: true,
        noClick: false,
        multiple: false,
    }));
});

test('Dragging a file over the area will show the upload indicator', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    act(() => {
        getLastDropzoneProps().onDragEnter();
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Dragging a file outside of the area will hide the upload indicator', () => {
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    act(() => {
        getLastDropzoneProps().onDragEnter();
        getLastDropzoneProps().onDragLeave();
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Dropping a file on the area will hide the upload indicator and call the "onDrop" handler', () => {
    const dropSpy = jest.fn();
    const testFileData = {name: 'test-file'};
    const {asFragment} = render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={dropSpy}
            progress={0}
            uploading={false}
        />
    );

    act(() => {
        getLastDropzoneProps().onDragEnter();
        getLastDropzoneProps().onDrop([testFileData]);
    });

    expect(dropSpy).toHaveBeenCalledWith(testFileData);
    expect(asFragment()).toMatchSnapshot();
});
