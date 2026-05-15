// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {Overlay} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ImageFocusPoint from '../../../components/ImageFocusPoint';
import FocusPointOverlay from '../FocusPointOverlay';

jest.mock('sulu-admin-bundle/components', () => ({
    Overlay: jest.fn(({children}) => <div>{children}</div>),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.clone = jest.fn().mockReturnValue(this);
        this.change = jest.fn();
        this.destroy = jest.fn();
        this.save = jest.fn();
        this.set = jest.fn();
        this.saving = false;
    }),
}));

jest.mock('../../../components/ImageFocusPoint', () => jest.fn(() => null));

function getLatestOverlayProps() {
    const calls = (Overlay: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestImageFocusPointProps() {
    const calls = (ImageFocusPoint: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should not create a ResourceStore before overlay was opened', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    render(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    expect(resourceStore.clone).not.toBeCalled();
});

test('Should select the middle by default', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const {rerender} = render(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceStore={resourceStore}
        />
    );

    expect(getLatestImageFocusPointProps().value).toEqual({x: 1, y: 1});
});

test('Initialize with data from resourceStore when overlay opens', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    const {rerender} = render(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceStore={resourceStore}
        />
    );

    expect(getLatestImageFocusPointProps().value).toEqual({x: 2, y: 1});
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    render(
        <FocusPointOverlay
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    getLatestOverlayProps().onClose();

    expect(closeSpy).toBeCalledWith();
});

test('Should save the focus point when confirm button is clicked', () => {
    const confirmSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    const savePromise = Promise.resolve({});
    resourceStore.save.mockReturnValue(savePromise);

    const {rerender} = render(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={false}
            resourceStore={resourceStore}
        />
    );

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            resourceStore={resourceStore}
        />
    );

    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);
    act(() => {
        getLatestImageFocusPointProps().onChange({x: 0, y: 2});
    });
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);
    getLatestOverlayProps().onConfirm();

    expect(resourceStore.change).toBeCalledWith('focusPointX', 0);
    expect(resourceStore.change).toBeCalledWith('focusPointY', 2);
    expect(resourceStore.save).toBeCalledWith();

    return savePromise.then(() => {
        expect(resourceStore.set).toBeCalledWith('focusPointX', 0);
        expect(resourceStore.set).toBeCalledWith('focusPointY', 2);
        expect(confirmSpy).toBeCalled();
    });
});
