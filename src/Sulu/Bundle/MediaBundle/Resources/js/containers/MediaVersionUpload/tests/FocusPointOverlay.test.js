// @flow
import React from 'react';
import {fireEvent, render, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import FocusPointOverlay from '../FocusPointOverlay';

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Overlay: jest.fn(({children}) => <div>{children}</div>),
    };
});

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

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const overlayComponent = ((jest.requireMock('sulu-admin-bundle/components'): any).Overlay: {
    mock: {calls: Array<[Object]>},
    ...
});

const getFocusPointButtons = (container: HTMLElement) => container.querySelectorAll('button');

const loadFocusPointGrid = (container: HTMLElement) => {
    const image = container.querySelector('img');
    if (!image) {
        throw new Error('Expected focus point image');
    }

    fireEvent.load(image);
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should not create a ResourceStore before overlay was opened', () => {
    const resourceStore: any = new ResourceStore('media');
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
    const resourceStore: any = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: undefined,
        focusPointY: undefined,
    };

    const {container, rerender} = render(
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

    loadFocusPointGrid(container);
    const focusPointButtons = getFocusPointButtons(container);

    expect(focusPointButtons).toHaveLength(9);
    expect(focusPointButtons[4]).toBeDisabled();
});

test('Initialize with data from resourceStore when overlay opens', async() => {
    const user = userEvent.setup();
    const resourceStore: any = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 2,
        focusPointY: 1,
    };

    const {container, rerender} = render(
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
    loadFocusPointGrid(container);
    await user.click(getFocusPointButtons(container)[0]);

    rerender(
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

    loadFocusPointGrid(container);
    const focusPointButtons = getFocusPointButtons(container);

    expect(focusPointButtons).toHaveLength(9);
    expect(focusPointButtons[5]).toBeDisabled();
});

test('Closing the overlay should call the onClose callback', () => {
    const closeSpy = jest.fn();
    const resourceStore: any = new ResourceStore('media');
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

    getLatestMockProps(overlayComponent).onClose();

    expect(closeSpy).toBeCalledWith();
});

test('Should save the focus point when confirm button is clicked', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const resourceStore: any = new ResourceStore('media');
    resourceStore.data = {
        focusPointX: 2,
        focusPointY: 1,
    };

    const savePromise = Promise.resolve({});
    resourceStore.save.mockReturnValue(savePromise);

    const {container, rerender} = render(
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

    expect(getLatestMockProps(overlayComponent).confirmDisabled).toEqual(true);
    loadFocusPointGrid(container);
    await user.click(getFocusPointButtons(container)[6]);
    expect(getLatestMockProps(overlayComponent).confirmDisabled).toEqual(false);
    getLatestMockProps(overlayComponent).onConfirm();

    const clonedResourceStore: any = resourceStore.clone.mock.results[0].value;

    expect(clonedResourceStore.change).toBeCalledWith('focusPointX', 0);
    expect(clonedResourceStore.change).toBeCalledWith('focusPointY', 2);
    expect(clonedResourceStore.save).toBeCalledWith();

    await savePromise;
    expect(resourceStore.set).toBeCalledWith('focusPointX', 0);
    expect(resourceStore.set).toBeCalledWith('focusPointY', 2);
    expect(confirmSpy).toBeCalled();
});

test('Should destroy cloned resourceStore when overlay closes', async() => {
    const resourceStore: any = new ResourceStore('media');
    resourceStore.data = {
        url: '/image.jpeg',
        focusPointX: 1,
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

    rerender(
        <FocusPointOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceStore={resourceStore}
        />
    );

    await waitFor(() => expect(resourceStore.destroy).toBeCalled());
});
