// @flow
import React from 'react';
import {act, render, waitFor} from '@testing-library/react';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MediaFormatStore from '../../../stores/MediaFormatStore';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');
    const Overlay = jest.fn((props) => <div>{props.children}</div>);
    const ImageRectangleSelection = jest.fn(() => null);
    const SingleSelect: any = jest.fn((props) => <div>{props.children}</div>);
    const SingleSelectOption = jest.fn(() => null);

    SingleSelect.Option = SingleSelectOption;

    return {
        ...actual,
        ImageRectangleSelection,
        Overlay,
        SingleSelect,
    };
});

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn();
    this.loading = false;
    this.saving = false;
}));

const componentsMock: any = jest.requireMock('sulu-admin-bundle/components');
const MediaFormatStoreMock: any = jest.requireMock('../../../stores/MediaFormatStore');
const formatStoreMock: any = jest.requireMock('../../../stores/formatStore');

const getMediaFormatStore = () => MediaFormatStoreMock.mock.instances[MediaFormatStoreMock.mock.instances.length - 1];

beforeEach(() => {
    jest.clearAllMocks();
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    render(
        <CropOverlay
            id={4}
            image="test.jpg"
            locale="de"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    getLatestMockProps(componentsMock.Overlay).onClose();

    expect(MediaFormatStore).toBeCalledWith(4, 'de');
    expect(closeSpy).toBeCalledWith();
});

test('Reset format croppings when closing overlay', async() => {
    const formats = [
        {
            key: 'test1',
            internal: true,
        },
        {
            key: 'test2',
            scale: {
                x: 400,
                y: 500,
            },
        },
        {
            key: 'test3',
            scale: {
                x: 700,
                y: 300,
            },
        },
        {
            key: 'test4',
            scale: {
                x: 500,
                y: 300,
            },
        },
    ];

    formatStoreMock.loadFormats.mockResolvedValue(formats);

    render(
        <CropOverlay
            id={7}
            image="test.jpg"
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const cropData = {
        test2: {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
        test3: {
            cropHeight: 20,
            cropWidth: 70,
            cropX: 10,
            cropY: 100,
        },
    };
    getMediaFormatStore().getFormatOptions.mockImplementation((formatKey) => cropData[formatKey]);

    await waitFor(() => expect(componentsMock.ImageRectangleSelection).toBeCalled());

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(true);

    act(() => {
        getLatestMockProps(componentsMock.ImageRectangleSelection).onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(false);

    act(() => {
        getLatestMockProps(componentsMock.SingleSelect).onChange('test3');
    });
    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(false);

    act(() => {
        getLatestMockProps(componentsMock.SingleSelect).onChange('test4');
        getLatestMockProps(componentsMock.Overlay).onClose();
    });

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(true);
});

test('Select first non-internal image format as default and change dimensions of ImageRectangleSelection', async() => {
    const formats = [
        {
            key: 'test1',
            internal: true,
        },
        {
            key: 'test2',
            scale: {
                x: 400,
                y: 500,
            },
        },
        {
            key: 'test3',
            scale: {
                x: 700,
                y: 300,
            },
        },
        {
            key: 'test4',
            scale: {
                x: 500,
                y: 300,
            },
        },
    ];

    formatStoreMock.loadFormats.mockResolvedValue(formats);

    render(
        <CropOverlay
            id={7}
            image="test.jpg"
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const cropData = {
        test2: {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
        test3: {
            cropHeight: 20,
            cropWidth: 70,
            cropX: 10,
            cropY: 100,
        },
    };
    getMediaFormatStore().getFormatOptions.mockImplementation((formatKey) => cropData[formatKey]);

    await waitFor(() => expect(componentsMock.ImageRectangleSelection).toBeCalled());
    expect(getLatestMockProps(componentsMock.ImageRectangleSelection)).toEqual(expect.objectContaining({
        minHeight: 500,
        minWidth: 400,
        value: {
            height: 30,
            left: 100,
            top: 10,
            width: 60,
        },
    }));

    act(() => {
        getLatestMockProps(componentsMock.ImageRectangleSelection).onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(false);

    act(() => {
        getLatestMockProps(componentsMock.SingleSelect).onChange('test3');
    });
    expect(getLatestMockProps(componentsMock.ImageRectangleSelection)).toEqual(expect.objectContaining({
        minHeight: 300,
        minWidth: 700,
        value: {
            height: 20,
            left: 10,
            top: 100,
            width: 70,
        },
    }));
    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(false);

    act(() => {
        getLatestMockProps(componentsMock.SingleSelect).onChange('test4');
    });
    expect(getLatestMockProps(componentsMock.ImageRectangleSelection)).toEqual(expect.objectContaining({
        minHeight: 300,
        minWidth: 500,
        value: undefined,
    }));
});

test('Save changes of formats', async() => {
    const confirmSpy = jest.fn();

    const formats = [
        {
            key: 'test1',
            scale: {
                x: 400,
                y: 500,
            },
        },
        {
            key: 'test2',
            scale: {
                x: 300,
                y: 200,
            },
        },
        {
            key: 'test3',
            scale: {
                x: 600,
                y: 400,
            },
        },
    ];

    formatStoreMock.loadFormats.mockResolvedValue(formats);

    render(
        <CropOverlay
            id={7}
            image="test.jpg"
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const cropData = {
        test1: {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };
    const mediaFormatStore = getMediaFormatStore();
    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => cropData[formatKey]);

    await waitFor(() => expect(componentsMock.ImageRectangleSelection).toBeCalled());
    expect(getLatestMockProps(componentsMock.ImageRectangleSelection)).toEqual(expect.objectContaining({
        minHeight: 500,
        minWidth: 400,
        value: {
            height: 30,
            left: 100,
            top: 10,
            width: 60,
        },
    }));

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(true);

    act(() => {
        getLatestMockProps(componentsMock.ImageRectangleSelection).onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(false);
    expect(getLatestMockProps(componentsMock.ImageRectangleSelection)).toEqual(expect.objectContaining({
        minHeight: 500,
        minWidth: 400,
        value: {
            height: 60,
            left: 200,
            top: 20,
            width: 20,
        },
    }));

    act(() => {
        getLatestMockProps(componentsMock.SingleSelect).onChange('test2');
        getLatestMockProps(componentsMock.ImageRectangleSelection).onChange(
            {height: 120, left: 100, top: 70, width: 30}
        );
    });

    const putPromise = Promise.resolve({});
    mediaFormatStore.updateFormatOptions.mockReturnValue(putPromise);

    act(() => {
        getLatestMockProps(componentsMock.Overlay).onConfirm();
    });

    expect(mediaFormatStore.updateFormatOptions).toBeCalledWith(
        {
            test1: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
            test2: {cropHeight: 120, cropWidth: 30, cropX: 100, cropY: 70},
        }
    );
    expect(confirmSpy).not.toBeCalled();

    await putPromise;
    await waitFor(() => expect(confirmSpy).toBeCalledWith());
    expect(getLatestMockProps(componentsMock.Overlay).confirmDisabled).toEqual(true);
});

test('Show which formats have already been cropped', async() => {
    const confirmSpy = jest.fn();

    const formats = [
        {
            key: 'test1',
            scale: {
                x: 400,
                y: 500,
            },
            title: 'Test 1',
        },
        {
            key: 'test2',
            scale: {
                x: 300,
                y: 200,
            },
            title: 'Test 2',
        },
        {
            key: 'test3',
            scale: {
                x: 600,
                y: 400,
            },
            title: 'Test 3',
        },
    ];

    formatStoreMock.loadFormats.mockResolvedValue(formats);

    render(
        <CropOverlay
            id={7}
            image="test.jpg"
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const cropData = {
        test1: {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };
    getMediaFormatStore().getFormatOptions.mockImplementation((formatKey) => cropData[formatKey]);

    await waitFor(() => expect(componentsMock.SingleSelect.Option).toBeCalled());
    const optionProps = componentsMock.SingleSelect.Option.mock.calls.reduce((result, [props]) => ({
        ...result,
        [props.value]: props,
    }), {});

    expect(optionProps.test1.children).toEqual('Test 1 (sulu_media.cropped)');
    expect(optionProps.test2.children).toEqual('Test 2');
    expect(optionProps.test3.children).toEqual('Test 3');
});
