// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {ImageRectangleSelection, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import formatStore from '../../../stores/formatStore';
import MediaFormatStore from '../../../stores/MediaFormatStore';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Overlay = jest.fn(({children}) => <div>{children}</div>);
    const Loader = jest.fn(() => null);
    const ImageRectangleSelection = jest.fn(() => null);
    const SingleSelect = jest.fn(({children}) => <div>{children}</div>);
    const SingleSelectAny: any = SingleSelect;
    SingleSelectAny.Option = jest.fn(() => null);

    return {
        ImageRectangleSelection,
        Loader,
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

function renderCropOverlay(props: Object = {}) {
    return render(
        <CropOverlay
            id={4}
            image="test.jpg"
            locale="de"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            {...props}
        />
    );
}

function getLatestOverlayProps() {
    const calls = ((Overlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestSingleSelectProps() {
    const calls = ((SingleSelect: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestImageRectangleSelectionProps() {
    const calls = ((ImageRectangleSelection: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getOptionPropsByValue(value: string) {
    const calls = (((SingleSelect: any).Option: any).mock.calls: any);
    const optionCall = calls.find(([props]) => props.value === value);

    if (!optionCall) {
        throw new Error(`Expected SingleSelect.Option for value "${value}"`);
    }

    return optionCall[0];
}

function getMediaFormatStoreInstance(index: number = 0) {
    const instances = ((MediaFormatStore: any).mock.instances: any);
    return instances[index];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    renderCropOverlay({
        id: 4,
        locale: 'de',
        onClose: closeSpy,
    });

    getLatestOverlayProps().onClose();

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

    const formatsPromise = Promise.resolve(formats);
    formatStore.loadFormats.mockReturnValue(formatsPromise);

    renderCropOverlay({
        id: 7,
        image: 'test.jpg',
        locale: 'en',
    });

    const cropData = {
        'test2': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
        'test3': {
            cropHeight: 20,
            cropWidth: 70,
            cropX: 10,
            cropY: 100,
        },
    };

    const mediaFormatStore = getMediaFormatStoreInstance();

    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    await act(async() => {
        await formatsPromise;
    });

    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);

    act(() => {
        getLatestImageRectangleSelectionProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestSingleSelectProps().onChange('test3');
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestSingleSelectProps().onChange('test4');
    });

    act(() => {
        getLatestOverlayProps().onClose();
    });

    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);
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

    const formatsPromise = Promise.resolve(formats);
    formatStore.loadFormats.mockReturnValue(formatsPromise);

    renderCropOverlay({
        id: 7,
        image: 'test.jpg',
        locale: 'en',
    });

    const cropData = {
        'test2': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
        'test3': {
            cropHeight: 20,
            cropWidth: 70,
            cropX: 10,
            cropY: 100,
        },
    };

    const mediaFormatStore = getMediaFormatStoreInstance();

    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    await act(async() => {
        await formatsPromise;
    });

    expect(getLatestImageRectangleSelectionProps()).toEqual(expect.objectContaining({
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
        getLatestImageRectangleSelectionProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestSingleSelectProps().onChange('test3');
    });

    expect(getLatestImageRectangleSelectionProps()).toEqual(expect.objectContaining({
        minHeight: 300,
        minWidth: 700,
        value: {
            height: 20,
            left: 10,
            top: 100,
            width: 70,
        },
    }));

    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    act(() => {
        getLatestSingleSelectProps().onChange('test4');
    });

    expect(getLatestImageRectangleSelectionProps()).toEqual(expect.objectContaining({
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

    const formatsPromise = Promise.resolve(formats);
    formatStore.loadFormats.mockReturnValue(formatsPromise);

    renderCropOverlay({
        id: 7,
        image: 'test.jpg',
        locale: 'en',
        onConfirm: confirmSpy,
    });

    const cropData = {
        'test1': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };

    const mediaFormatStore = getMediaFormatStoreInstance();

    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    await act(async() => {
        await formatsPromise;
    });

    expect(getLatestImageRectangleSelectionProps()).toEqual(expect.objectContaining({
        minHeight: 500,
        minWidth: 400,
        value: {
            height: 30,
            left: 100,
            top: 10,
            width: 60,
        },
    }));

    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);

    act(() => {
        getLatestImageRectangleSelectionProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );
    });

    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);

    expect(getLatestImageRectangleSelectionProps()).toEqual(expect.objectContaining({
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
        getLatestSingleSelectProps().onChange('test2');
    });

    act(() => {
        getLatestImageRectangleSelectionProps().onChange(
            {height: 120, left: 100, top: 70, width: 30}
        );
    });

    const putPromise = Promise.resolve({});
    mediaFormatStore.updateFormatOptions.mockReturnValue(putPromise);

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(mediaFormatStore.updateFormatOptions).toBeCalledWith(
        {
            test1: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
            test2: {cropHeight: 120, cropWidth: 30, cropX: 100, cropY: 70},
        }
    );
    expect(confirmSpy).not.toBeCalled();

    await act(async() => {
        await putPromise;
    });

    expect(confirmSpy).toBeCalledWith();
    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);
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

    const formatsPromise = Promise.resolve(formats);
    formatStore.loadFormats.mockReturnValue(formatsPromise);

    renderCropOverlay({
        id: 7,
        image: 'test.jpg',
        locale: 'en',
        onConfirm: confirmSpy,
    });

    const cropData = {
        'test1': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };

    const mediaFormatStore = getMediaFormatStoreInstance();

    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    await act(async() => {
        await formatsPromise;
    });

    expect(getOptionPropsByValue('test1').children).toEqual('Test 1 (sulu_media.cropped)');
    expect(getOptionPropsByValue('test2').children).toEqual('Test 2');
    expect(getOptionPropsByValue('test3').children).toEqual('Test 3');
});
