// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import CollectionFormOverlay from '../CollectionFormOverlay';

let mockResourceFormStoreInstances = [];

jest.mock('sulu-admin-bundle/services/initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/MissingTypeDialog', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.data = {};
    this.destroy = jest.fn();
    this.schema = {};
    this.types = {};
    this.validate = jest.fn(() => true);
    mockResourceFormStoreInstances.push(this);
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

beforeEach(() => {
    mockResourceFormStoreInstances = [];
});

function renderCollectionFormOverlay(props: Object = {}) {
    return render(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={new ResourceStore('test')}
            {...props}
        />
    );
}

function getLatestResourceFormStore() {
    const store = mockResourceFormStoreInstances[mockResourceFormStoreInstances.length - 1];

    if (!store) {
        throw new Error('Expected ResourceFormStore instance');
    }

    return store;
}

test('Render as overlay', () => {
    renderCollectionFormOverlay({operationType: 'create'});

    expect(screen.getByLabelText('su-times')).toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.cancel')).not.toBeInTheDocument();
});

test('Render as dialog', () => {
    renderCollectionFormOverlay({operationType: 'create', overlayType: 'dialog'});

    expect(screen.queryByLabelText('su-times')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.cancel')).toBeInTheDocument();
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
    expect(screen.getByText('sulu_media.add_collection')).toBeInTheDocument();

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );
    expect(screen.getByText('sulu_media.add_collection')).toBeInTheDocument();

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType="update"
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );
    expect(screen.getByText('sulu_media.edit_collection')).toBeInTheDocument();

    rerender(
        <CollectionFormOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            operationType={null}
            overlayType="overlay"
            resourceStore={resourceStore}
        />
    );
    expect(screen.getByText('sulu_media.edit_collection')).toBeInTheDocument();
});

test('Call destroy of ResourceFormStore when unmounted', () => {
    const {unmount} = renderCollectionFormOverlay();
    const resourceFormStore = getLatestResourceFormStore();
    resourceFormStore.destroy = jest.fn();

    unmount();

    expect(resourceFormStore.destroy).toHaveBeenCalledWith();
});
