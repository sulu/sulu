// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import FocusPointOverlay from '../FocusPointOverlay';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.change = jest.fn();
        this.save = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Should select the  middle by default', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const focusPointOverlay = shallow(
        <FocusPointOverlay
            onClose={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    expect(focusPointOverlay.find('ImageFocusPoint').prop('value')).toEqual({x: 1, y: 1});
});

test('Initialize with data from resourceStore when overlay opens', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    const focusPointOverlay = shallow(
        <FocusPointOverlay
            onClose={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    expect(focusPointOverlay.find('ImageFocusPoint').prop('image')).toEqual('/image.jpeg');
    expect(focusPointOverlay.find('ImageFocusPoint').prop('value')).toEqual({x: 2, y: 1});

    focusPointOverlay.instance().focusPointX = 0;
    focusPointOverlay.instance().focusPointY = 0;

    focusPointOverlay.update();
    expect(focusPointOverlay.find('ImageFocusPoint').prop('value')).toEqual({x: 0, y: 0});

    focusPointOverlay.setProps({open: true});
    focusPointOverlay.update();
    expect(focusPointOverlay.find('ImageFocusPoint').prop('value')).toEqual({x: 2, y: 1});
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    const focusPointOverlay = shallow(
        <FocusPointOverlay
            onClose={closeSpy}
            open={true}
            resourceStore={resourceStore}
        />
    );

    focusPointOverlay.find('Overlay').prop('onClose')();

    expect(closeSpy).toBeCalledWith();
});

test('Should save the focus point when confirm button is clicked', () => {
    const closeSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    const savePromise = Promise.resolve({});
    resourceStore.save.mockReturnValue(savePromise);

    const focusPointOverlay = shallow(
        <FocusPointOverlay
            onClose={closeSpy}
            open={true}
            resourceStore={resourceStore}
        />
    );

    focusPointOverlay.find('ImageFocusPoint').prop('onChange')({x: 0, y: 2});
    focusPointOverlay.find('Overlay').prop('onConfirm')();

    expect(resourceStore.change).toBeCalledWith('focusPointX', 0);
    expect(resourceStore.change).toBeCalledWith('focusPointY', 2);
    expect(resourceStore.save).toBeCalledWith();

    return savePromise.then(() => {
        expect(closeSpy).toBeCalled();
    });
});
