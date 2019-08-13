// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import CollectionFormOverlay from '../CollectionFormOverlay';

jest.mock('sulu-admin-bundle/services/Initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceFormStore: jest.fn(),
    // $FlowFixMe
    Form: require.requireActual('sulu-admin-bundle/containers').Form,
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Render as overlay', () => {
    const resourceStore = new ResourceStore('test');
    const collectionFormOverlay = shallow(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(collectionFormOverlay.find('Overlay')).toHaveLength(1);
    expect(collectionFormOverlay.find('Dialog')).toHaveLength(0);
});

test('Render as dialog', () => {
    const resourceStore = new ResourceStore('test');
    const collectionFormOverlay = shallow(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="dialog"
            resourceStore={resourceStore}
        />
    );

    expect(collectionFormOverlay.find('Overlay')).toHaveLength(0);
    expect(collectionFormOverlay.find('Dialog')).toHaveLength(1);
});

test('Keep title when closing overlay until new overlay opens', () => {
    const resourceStore = new ResourceStore('test');
    const collectionFormOverlay = shallow(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    collectionFormOverlay.setProps({resourceStore, operationType: 'create'});
    expect(collectionFormOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        open: true,
        title: 'sulu_media.add_collection',
    }));

    collectionFormOverlay.setProps({resourceStore, operationType: null});
    expect(collectionFormOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        open: false,
        title: 'sulu_media.add_collection',
    }));

    collectionFormOverlay.setProps({resourceStore, operationType: 'update'});
    expect(collectionFormOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        open: true,
        title: 'sulu_media.edit_collection',
    }));

    collectionFormOverlay.setProps({resourceStore, operationType: null});
    expect(collectionFormOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        open: false,
        title: 'sulu_media.edit_collection',
    }));
});

test('Call destroy of ResourceFormStore when unmounted', () => {
    const resourceStore = new ResourceStore('test');
    const collectionFormOverlay = mount(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    const resourceFormStore = collectionFormOverlay.instance().formStore;
    resourceFormStore.destroy = jest.fn();

    collectionFormOverlay.unmount();

    expect(resourceFormStore.destroy).toBeCalledWith();
});
