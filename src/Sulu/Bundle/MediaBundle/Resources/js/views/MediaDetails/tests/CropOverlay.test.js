// @flow
import React from 'react';
import {shallow} from 'enzyme';
import FormatStore from '../../../stores/FormatStore';
import CropOverlay from '../CropOverlay';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/FormatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const cropOverlay = shallow(
        <CropOverlay
            image="test.jpg"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    cropOverlay.find('Overlay').prop('onClose')();

    expect(closeSpy).toBeCalledWith();
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
    ];

    const formatsPromise = Promise.resolve(formats);
    FormatStore.loadFormats.mockReturnValue(formatsPromise);

    const cropOverlay = shallow(
        <CropOverlay
            image="test.jpg"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    return formatsPromise.then(() => {
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 500,
            minWidth: 400,
        }));

        cropOverlay.find('SingleSelect').prop('onChange')('test3');
        cropOverlay.update();
        expect(cropOverlay.find('withContainerSize(ImageRectangleSelection)').props()).toEqual(expect.objectContaining({
            minHeight: 300,
            minWidth: 700,
        }));
    });
});
