// @flow
import React from 'react';
import {shallow} from 'enzyme';
import FormatStore from '../../../stores/FormatStore';
import MediaFormatStore from '../../../stores/MediaFormatStore';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/FormatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn();
    this.loading = false;
}));

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const cropOverlay = shallow(
        <CropOverlay
            id={4}
            image="test.jpg"
            locale="de"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    cropOverlay.find('Overlay').prop('onClose')();

    expect(MediaFormatStore).toBeCalledWith(4, 'de');
    expect(closeSpy).toBeCalledWith();
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
    FormatStore.loadFormats.mockReturnValue(formatsPromise);

    const cropOverlay = shallow(
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

    cropOverlay.instance().mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        cropOverlay.update();

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(true);

        cropOverlay.find('withContainerSize(ImageRectangleSelection)').prop('onChange')(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(false);

        cropOverlay.find('SingleSelect').prop('onChange')('test3');
        cropOverlay.update();
        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(false);

        cropOverlay.find('SingleSelect').prop('onChange')('test4');

        cropOverlay.find('Overlay').prop('onClose')();

        cropOverlay.update();
        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(true);
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
    FormatStore.loadFormats.mockReturnValue(formatsPromise);

    const cropOverlay = shallow(
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

    cropOverlay.instance().mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 30,
                left: 100,
                top: 10,
                width: 60,
            },
        }));

        cropOverlay.find('withContainerSize(ImageRectangleSelection)').prop('onChange')(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(false);

        cropOverlay.find('SingleSelect').prop('onChange')('test3');
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 300,
            minWidth: 700,
            value: {
                height: 20,
                left: 10,
                top: 100,
                width: 70,
            },
        }));

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(false);

        cropOverlay.find('SingleSelect').prop('onChange')('test4');
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
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
    FormatStore.loadFormats.mockReturnValue(formatsPromise);

    const cropOverlay = shallow(
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

    cropOverlay.instance().mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => {
        return cropData[formatKey];
    });

    return formatsPromise.then(() => {
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 30,
                left: 100,
                top: 10,
                width: 60,
            },
        }));

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(true);

        cropOverlay.find('withContainerSize(ImageRectangleSelection)').prop('onChange')(
            {height: 60, left: 200, top: 20, width: 20}
        );

        expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(false);

        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
            value: {
                height: 60,
                left: 200,
                top: 20,
                width: 20,
            },
        }));

        cropOverlay.find('SingleSelect').prop('onChange')('test2');
        cropOverlay.find('withContainerSize(ImageRectangleSelection)').prop('onChange')(
            {height: 120, left: 100, top: 70, width: 30}
        );

        const putPromise = Promise.resolve({});
        cropOverlay.instance().mediaFormatStore.updateFormatOptions.mockReturnValue(putPromise);
        cropOverlay.find('Overlay').prop('onConfirm')();

        expect(cropOverlay.instance().mediaFormatStore.updateFormatOptions).toBeCalledWith(
            {
                test1: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
                test2: {cropHeight: 120, cropWidth: 30, cropX: 100, cropY: 70},
            }
        );
        expect(confirmSpy).not.toBeCalled();

        return putPromise.then(() => {
            cropOverlay.update();
            expect(confirmSpy).toBeCalledWith();
            expect(cropOverlay.find('Overlay').prop('confirmDisabled')).toEqual(true);
        });
    });
});
