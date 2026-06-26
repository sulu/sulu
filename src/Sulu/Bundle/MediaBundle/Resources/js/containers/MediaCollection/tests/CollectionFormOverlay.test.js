// @flow
import React from 'react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {findAllElementsByType, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import CollectionFormOverlay from '../CollectionFormOverlay';

jest.mock('sulu-admin-bundle/services/initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/MissingTypeDialog', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.data = {};
    this.destroy = jest.fn();
    this.schema = {};
    this.types = {};
    this.validate = jest.fn(() => true);
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

test('Render as overlay', () => {
    const resourceStore = new ResourceStore('test');
    const {instance} = renderWithRef(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    expect(findAllElementsByType(instance.render(), 'Overlay')).toHaveLength(1);
    expect(findAllElementsByType(instance.render(), 'Dialog')).toHaveLength(0);
});

test('Render as dialog', () => {
    const resourceStore = new ResourceStore('test');
    const {instance} = renderWithRef(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="dialog"
            resourceStore={resourceStore}
        />
    );

    expect(findAllElementsByType(instance.render(), 'Overlay')).toHaveLength(0);
    expect(findAllElementsByType(instance.render(), 'Dialog')).toHaveLength(1);
});

test('Keep title when closing overlay until new overlay opens', () => {
    const resourceStore = new ResourceStore('test');
    const {instance, rerender} = renderWithRef(
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
    expect(findElementByType(instance.render(), 'Overlay').props).toEqual(expect.objectContaining({
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
    expect(findElementByType(instance.render(), 'Overlay').props).toEqual(expect.objectContaining({
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
    expect(findElementByType(instance.render(), 'Overlay').props).toEqual(expect.objectContaining({
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
    expect(findElementByType(instance.render(), 'Overlay').props).toEqual(expect.objectContaining({
        open: false,
        title: 'sulu_media.edit_collection',
    }));
});

test('Call destroy of ResourceFormStore when unmounted', () => {
    const resourceStore = new ResourceStore('test');
    const {instance, unmount} = renderWithRef(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );

    const resourceFormStore = instance.formStore;
    resourceFormStore.destroy = jest.fn();

    unmount();

    expect(resourceFormStore.destroy).toHaveBeenCalledWith();
});
