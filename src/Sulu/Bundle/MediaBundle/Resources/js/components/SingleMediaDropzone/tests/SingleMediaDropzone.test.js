/* global global */
// @flow
import {act, render} from '@testing-library/react';
import React from 'react';
import Dropzone from 'react-dropzone';
import SingleMediaDropzone from '../SingleMediaDropzone';

jest.mock('react-dropzone', () => {
    return jest.fn((props) => props.children({
        getInputProps: () => ({}),
        getRootProps: (dropzoneProps = {}) => dropzoneProps,
    }));
});

const imageInstances: Array<MockImage> = [];
const OriginalImage = global.Image;

class MockImage {
    _src: string;
    onerror: () => void;
    onload: () => void;

    constructor() {
        imageInstances.push(this);
    }

    set src(src: string) {
        this._src = src;
    }

    get src() {
        return this._src;
    }
}

beforeEach(() => {
    imageInstances.length = 0;
    jest.clearAllMocks();
    // $FlowFixMe[prop-missing]
    global.Image = MockImage;
});

afterEach(() => {
    // $FlowFixMe[prop-missing]
    global.Image = OriginalImage;
});

function renderSingleMediaDropzone(props: any = {}) {
    return render(
        <SingleMediaDropzone
            image={undefined}
            onDrop={jest.fn()}
            {...props}
        />
    );
}

function getDropzoneProps() {
    return Dropzone.mock.calls[Dropzone.mock.calls.length - 1][0];
}

function triggerImageLoad(index = 0) {
    act(() => {
        imageInstances[index].onload();
    });
}

function triggerImageError(index = 0) {
    act(() => {
        imageInstances[index].onerror();
    });
}

function getMediaContainer(container) {
    const mediaContainer = container.querySelector('.mediaContainer');

    if (!mediaContainer) {
        throw new Error('Expected media container');
    }

    return mediaContainer;
}

test('Render a SingleMediaDropzone', () => {
    const {asFragment} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        uploading: false,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the default empty icon', () => {
    const {asFragment} = renderSingleMediaDropzone({image: undefined});

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the passed empty icon', () => {
    const {asFragment} = renderSingleMediaDropzone({emptyIcon: 'su-user', image: undefined});

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with an error text', () => {
    const {asFragment} = renderSingleMediaDropzone({
        emptyIcon: 'su-user',
        errorText: 'some-custom-error-message',
        image: undefined,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a loader if image has not been loaded yet', () => {
    const {asFragment} = renderSingleMediaDropzone({emptyIcon: 'su-user', image: 'test.jpg'});

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded after an error occured before', () => {
    const {asFragment} = renderSingleMediaDropzone({emptyIcon: 'su-user', image: 'test.jpg'});

    triggerImageError();
    triggerImageLoad();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded yet', () => {
    const {asFragment} = renderSingleMediaDropzone({emptyIcon: 'su-user', image: 'test.jpg'});

    triggerImageLoad();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a MimeTypeIndicator if an error appeared during image loading', () => {
    const {asFragment} = renderSingleMediaDropzone({
        emptyIcon: 'su-user',
        image: 'test.jpg',
        mimeType: 'video/x-m4v',
    });

    triggerImageError();
    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone in disabled state', () => {
    const {asFragment} = renderSingleMediaDropzone({
        disabled: true,
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        uploading: false,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the round skin', () => {
    const {asFragment} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        skin: 'round',
        uploading: false,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone while uploading', () => {
    const {asFragment} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 50,
        uploading: true,
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render img tag with key to avoid keeping old image on new upload', () => {
    const {rerender} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        uploading: false,
    });
    const firstImage = document.querySelector('img');

    rerender(
        <SingleMediaDropzone
            image="http://lorempixel.com/500/500"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );
    const secondImage = document.querySelector('img');

    expect(firstImage).not.toBeNull();
    expect(secondImage).not.toBeNull();
    expect(secondImage).not.toBe(firstImage);
});

test('Component pass correct props to Dropzone component', () => {
    renderSingleMediaDropzone({
        accept: 'application/json',
        disabled: true,
        image: 'http://lorempixel.com/400/400',
        uploading: false,
    });

    expect(getDropzoneProps()).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: true,
        noClick: false,
        multiple: false,
    }));
});

test('Dragging a file over the area will show the upload indicator', () => {
    const {container} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        uploading: false,
    });

    act(() => {
        getDropzoneProps().onDragEnter();
    });
    expect(getMediaContainer(container).className).toContain('showUploadIndicator');
});

test('Dragging a file outside of the area will hide the upload indicator', () => {
    const {container} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        progress: 0,
        uploading: false,
    });

    act(() => {
        getDropzoneProps().onDragEnter();
        getDropzoneProps().onDragLeave();
    });
    expect(getMediaContainer(container).className).not.toContain('showUploadIndicator');
});

test('Dropping a file on the area will hide the upload indicator and call the "onDrop" handler', () => {
    const dropSpy = jest.fn();
    const testFileData = {name: 'test-file'};
    const {container} = renderSingleMediaDropzone({
        image: 'http://lorempixel.com/400/400',
        onDrop: dropSpy,
        progress: 0,
        uploading: false,
    });

    act(() => {
        getDropzoneProps().onDragEnter();
        getDropzoneProps().onDrop([testFileData]);
    });

    expect(getMediaContainer(container).className).not.toContain('showUploadIndicator');
    expect(dropSpy).toHaveBeenCalledWith(testFileData);
});
