// @flow
import React from 'react';
import {findAllElementsByType, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import formatStore from '../../../stores/formatStore';
import MediaFormatStore from '../../../stores/MediaFormatStore';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn();
    this.loading = false;
}));

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const {instance: cropOverlay} = renderWithRef(
        <CropOverlay
            id={4}
            image="test.jpg"
            locale="de"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    findElementByType(cropOverlay.render(), 'Overlay').props.onClose();

    expect(MediaFormatStore).toHaveBeenCalledWith(4, 'de');
    expect(closeSpy).toHaveBeenCalledWith();
});

test('Convert selection to format options with edge-based integer coordinates', () => {
    const {instance: cropOverlay} = renderWithRef(
        <CropOverlay
            id={4}
            image="test.jpg"
            locale="de"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(cropOverlay.convertSelectionToFormatOptions({
        left: 100.6,
        top: 74.63,
        width: 152.4,
        height: 125.37,
    })).toEqual({
        cropX: 100,
        cropY: 74,
        cropWidth: 153,
        cropHeight: 126,
    });
});

test('Reset format croppings when closing overlay', () => {
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

    const {instance: cropOverlay} = renderWithRef(
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

    cropOverlay.mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        const getOverlayProps = () => findElementByType(cropOverlay.render(), 'Overlay').props;
        const getRectangleProps = () => findElementByType(
            cropOverlay.render(),
            'withContainerSize(ImageRectangleSelection)'
        ).props;
        const getSingleSelectProps = () => findElementByType(cropOverlay.render(), 'SingleSelect').props;

        expect(getOverlayProps().confirmDisabled).toEqual(true);

        getRectangleProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(getOverlayProps().confirmDisabled).toEqual(false);

        getSingleSelectProps().onChange('test3');
        expect(getOverlayProps().confirmDisabled).toEqual(false);

        getSingleSelectProps().onChange('test4');

        getOverlayProps().onClose();

        expect(getOverlayProps().confirmDisabled).toEqual(true);
    });
});

test('Select first non-internal image format as default and change dimensions of ImageRectangleSelection', () => {
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

    const {instance: cropOverlay} = renderWithRef(
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

    cropOverlay.mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        const getRectangleProps = () => findElementByType(
            cropOverlay.render(),
            'withContainerSize(ImageRectangleSelection)'
        ).props;
        const getOverlayProps = () => findElementByType(cropOverlay.render(), 'Overlay').props;
        const getSingleSelectProps = () => findElementByType(cropOverlay.render(), 'SingleSelect').props;

        expect(getRectangleProps()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 30,
                left: 100,
                top: 10,
                width: 60,
            },
        }));

        getRectangleProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(getOverlayProps().confirmDisabled).toEqual(false);

        getSingleSelectProps().onChange('test3');
        expect(getRectangleProps()).toEqual(expect.objectContaining({
            minHeight: 300,
            minWidth: 700,
            value: {
                height: 20,
                left: 10,
                top: 100,
                width: 70,
            },
        }));

        expect(getOverlayProps().confirmDisabled).toEqual(false);

        getSingleSelectProps().onChange('test4');
        expect(getRectangleProps()).toEqual(expect.objectContaining({
            minHeight: 300,
            minWidth: 500,
            value: undefined,
        }));
    });
});

test('Save changes of formats', () => {
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

    const {instance: cropOverlay} = renderWithRef(
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
        'test1': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };

    cropOverlay.mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        const getRectangleProps = () => findElementByType(
            cropOverlay.render(),
            'withContainerSize(ImageRectangleSelection)'
        ).props;
        const getOverlayProps = () => findElementByType(cropOverlay.render(), 'Overlay').props;
        const getSingleSelectProps = () => findElementByType(cropOverlay.render(), 'SingleSelect').props;

        expect(getRectangleProps()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 30,
                left: 100,
                top: 10,
                width: 60,
            },
        }));

        expect(getOverlayProps().confirmDisabled).toEqual(true);

        getRectangleProps().onChange(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(getOverlayProps().confirmDisabled).toEqual(false);

        expect(getRectangleProps()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 60,
                left: 200,
                top: 20,
                width: 20,
            },
        }));

        getSingleSelectProps().onChange('test2');
        getRectangleProps().onChange(
            {height: 120, left: 100, top: 70, width: 30}
        );

        const putPromise = Promise.resolve({});
        cropOverlay.mediaFormatStore.updateFormatOptions.mockReturnValue(putPromise);
        getOverlayProps().onConfirm();

        expect(cropOverlay.mediaFormatStore.updateFormatOptions).toHaveBeenCalledWith(
            {
                test1: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
                test2: {cropHeight: 120, cropWidth: 30, cropX: 100, cropY: 70},
            }
        );
        expect(confirmSpy).not.toHaveBeenCalled();

        return putPromise.then(() => {
            expect(confirmSpy).toHaveBeenCalledWith();
            expect(getOverlayProps().confirmDisabled).toEqual(true);
        });
    });
});

test('Show which formats have already been cropped', () => {
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

    const {instance: cropOverlay} = renderWithRef(
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
        'test1': {
            cropHeight: 30,
            cropWidth: 60,
            cropX: 100,
            cropY: 10,
        },
    };

    cropOverlay.mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        const options = findAllElementsByType(cropOverlay.render(), 'Option');

        expect(options[0].props.children).toEqual('Test 1 (sulu_media.cropped)');
        expect(options[1].props.children).toEqual('Test 2');
        expect(options[2].props.children).toEqual('Test 3');
    });
});
