// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {resourceFormStoreFactory} from 'sulu-admin-bundle/containers';
import {Dialog, Overlay} from 'sulu-admin-bundle/components';
import CollectionFormOverlay from '../CollectionFormOverlay';

jest.mock('sulu-admin-bundle/services/initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => ({
    Form: jest.fn(() => null),
    resourceFormStoreFactory: {
        createFromResourceStore: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Dialog: jest.fn(() => null),
    Overlay: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

function createFormStore() {
    return {
        destroy: jest.fn(),
    };
}

function getLatestOverlayProps() {
    const calls = (Overlay: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
    resourceFormStoreFactory.createFromResourceStore.mockImplementation(() => createFormStore());
});

test('Render as overlay', () => {
    const resourceStore = new ResourceStore('test');
    render(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect((Overlay: any).mock.calls).toHaveLength(1);
    expect((Dialog: any).mock.calls).toHaveLength(0);
});

test('Render as dialog', () => {
    const resourceStore = new ResourceStore('test');
    render(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="dialog"
            resourceStore={resourceStore}
        />
    );

    expect((Overlay: any).mock.calls).toHaveLength(0);
    expect((Dialog: any).mock.calls).toHaveLength(1);
});

test('Keep title when closing overlay until new overlay opens', () => {
    const resourceStore = new ResourceStore('test');
    const {rerender} = render(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType="create"
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        open: true,
        title: 'sulu_media.add_collection',
    }));

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        open: false,
        title: 'sulu_media.add_collection',
    }));

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType="update"
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        open: true,
        title: 'sulu_media.edit_collection',
    }));

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(getLatestOverlayProps()).toEqual(expect.objectContaining({
        open: false,
        title: 'sulu_media.edit_collection',
    }));
});

test('Call destroy of ResourceFormStore when unmounted', () => {
    const resourceStore = new ResourceStore('test');
    const {unmount} = render(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    const resourceFormStore = resourceFormStoreFactory.createFromResourceStore.mock.results[0].value;

    unmount();

    expect(resourceFormStore.destroy).toBeCalledWith();
});
