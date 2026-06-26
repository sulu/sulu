// @flow
import React from 'react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import FocusPointOverlay from '../FocusPointOverlay';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.clone = jest.fn().mockReturnValue(this);
        this.change = jest.fn();
        this.save = jest.fn();
        this.set = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

test('Should not create a ResourceStore before overlay was opened', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    renderWithRef(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    expect(resourceStore.clone).not.toHaveBeenCalled();
});

test('Should select the middle by default', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const {instance: focusPointOverlay, rerender} = renderWithRef(
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

    expect(findElementByType(focusPointOverlay.render(), 'ImageFocusPoint').props.value).toEqual({x: 1, y: 1});
});

test('Initialize with data from resourceStore when overlay opens', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    const {instance: focusPointOverlay, rerender} = renderWithRef(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    focusPointOverlay.focusPointX = 0;
    focusPointOverlay.focusPointY = 0;

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceStore={resourceStore}
        />
    );
    expect(findElementByType(focusPointOverlay.render(), 'ImageFocusPoint').props.value).toEqual({x: 2, y: 1});
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    const {instance: focusPointOverlay} = renderWithRef(
        <FocusPointOverlay
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    findElementByType(focusPointOverlay.render(), 'Overlay').props.onClose();

    expect(closeSpy).toHaveBeenCalledWith();
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

    const {instance: focusPointOverlay, rerender} = renderWithRef(
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

    expect(findElementByType(focusPointOverlay.render(), 'Overlay').props.confirmDisabled).toEqual(true);
    findElementByType(focusPointOverlay.render(), 'ImageFocusPoint').props.onChange({x: 0, y: 2});
    expect(findElementByType(focusPointOverlay.render(), 'Overlay').props.confirmDisabled).toEqual(false);
    findElementByType(focusPointOverlay.render(), 'Overlay').props.onConfirm();

    const clonedResourceStore = focusPointOverlay.resourceStore;

    expect(clonedResourceStore.change).toHaveBeenCalledWith('focusPointX', 0);
    expect(clonedResourceStore.change).toHaveBeenCalledWith('focusPointY', 2);
    expect(clonedResourceStore.save).toHaveBeenCalledWith();

    return savePromise.then(() => {
        expect(resourceStore.set).toHaveBeenCalledWith('focusPointX', 0);
        expect(resourceStore.set).toHaveBeenCalledWith('focusPointY', 2);
        expect(confirmSpy).toHaveBeenCalled();
    });
});
