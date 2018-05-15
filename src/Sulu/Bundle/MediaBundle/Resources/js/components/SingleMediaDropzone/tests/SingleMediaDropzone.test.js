// @flow
import {shallow, render} from 'enzyme';
import React from 'react';
import SingleMediaDropzone from '../SingleMediaDropzone';

test('Render a SingleMediaDropzone', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            uploading={false}
            progress={0}
            onDrop={jest.fn()}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the round skin', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            skin="round"
            uploading={false}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone while uploading', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            uploading={true}
            progress={50}
            onDrop={jest.fn()}
        />
    )).toMatchSnapshot();
});

test('Dragging a file over the area will show the upload indicator', () => {
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            uploading={false}
            progress={0}
            onDrop={jest.fn()}
        />
    );

    singleMediaDropzone.instance().handleDragEnter();
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(true);
});

test('Dragging a file outside of the area will hide the upload indicator', () => {
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            uploading={false}
            progress={0}
            onDrop={jest.fn()}
        />
    );

    singleMediaDropzone.instance().handleDragLeave();
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(false);
});

test('Dropping a file on the area will hide the upload indicator and call the "onDrop" handler', () => {
    const dropSpy = jest.fn();
    const testFileData = { name: 'test-file' };
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            uploading={false}
            progress={0}
            onDrop={dropSpy}
        />
    );

    singleMediaDropzone.instance().handleDrop([testFileData]);
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(false);
    expect(dropSpy).toBeCalledWith(testFileData);
});
