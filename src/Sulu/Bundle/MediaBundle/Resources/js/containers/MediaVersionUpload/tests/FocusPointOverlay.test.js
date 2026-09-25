// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import FocusPointOverlay from '../FocusPointOverlay';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.clone = jest.fn().mockReturnValue(this);
        this.change = jest.fn();
        this.destroy = jest.fn();
        this.save = jest.fn();
        this.set = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

function renderFocusPointOverlay(props: Object = {}) {
    return render(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            {...props}
        />
    );
}

function loadImageWithSize(container, width, height) {
    const image = container.querySelector('img');

    if (!image) {
        throw new Error('Expected image');
    }

    jest.spyOn(image, 'getBoundingClientRect').mockReturnValue({
        bottom: 0,
        height,
        left: 0,
        right: 0,
        top: 0,
        width,
        x: 0,
        y: 0,
        toJSON: jest.fn(),
    });
    fireEvent.load(image);
}

function getFocusPointButtons(container) {
    const focusPoints = container.querySelector('.focusPoints');

    if (!focusPoints) {
        throw new Error('Expected focus points');
    }

    return focusPoints.querySelectorAll('button');
}

test('Should not create a ResourceStore before overlay was opened', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    renderFocusPointOverlay({resourceStore});

    expect(resourceStore.clone).not.toHaveBeenCalled();
});

test('Should select the middle by default', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const {baseElement, rerender} = renderFocusPointOverlay({resourceStore});

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceStore={resourceStore}
        />
    );

    loadImageWithSize(baseElement, 300, 300);
    expect(getFocusPointButtons(baseElement)[4]).toBeDisabled();
});

test('Initialize with data from resourceStore when overlay opens', () => {
    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const {baseElement, rerender} = renderFocusPointOverlay({resourceStore});

    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceStore={resourceStore}
        />
    );

    loadImageWithSize(baseElement, 300, 300);
    expect(getFocusPointButtons(baseElement)[5]).toBeDisabled();
});

test('Closing the overlay should call the onClose callback', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    const resourceStore = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    renderFocusPointOverlay({onClose: closeSpy, open: true, resourceStore});

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(closeSpy).toHaveBeenCalledWith();
});

test('Should save the focus point when confirm button is clicked', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    const resourceStore = new ResourceStore('media');

    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    const savePromise = Promise.resolve({});
    resourceStore.save.mockReturnValue(savePromise);

    const {baseElement, rerender} = renderFocusPointOverlay({onConfirm: confirmSpy, resourceStore});

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            resourceStore={resourceStore}
        />
    );

    loadImageWithSize(baseElement, 300, 300);

    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeDisabled();

    await user.click(getFocusPointButtons(baseElement)[6]);
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    expect(resourceStore.change).toHaveBeenCalledWith('focusPointX', 0);
    expect(resourceStore.change).toHaveBeenCalledWith('focusPointY', 2);
    expect(resourceStore.save).toHaveBeenCalledWith();

    await savePromise;

    expect(resourceStore.set).toHaveBeenCalledWith('focusPointX', 0);
    expect(resourceStore.set).toHaveBeenCalledWith('focusPointY', 2);
    expect(confirmSpy).toHaveBeenCalled();
});
