// @flow
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import {Dialog, Overlay} from 'sulu-admin-bundle/components';
import {resourceFormStoreFactory} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import CollectionFormOverlay from '../CollectionFormOverlay';

jest.mock('sulu-admin-bundle/services/initializer', () => jest.fn());
jest.mock('sulu-admin-bundle/components', () => ({
    Dialog: jest.fn(() => null),
    Overlay: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: jest.fn(() => null),
        resourceFormStoreFactory: {
            createFromResourceStore: jest.fn(() => ({
                destroy: jest.fn(),
            })),
        },
    };
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

const createProps = (props = {}) => {
    const resourceStore = props.resourceStore || new ResourceStore('test');
    return {
        onClose: jest.fn(),
        onConfirm: jest.fn(),
        operationType: null,
        overlayType: 'overlay',
        ...props,
        resourceStore,
    };
};

beforeEach(() => {
    resourceFormStoreFactory.createFromResourceStore.mockClear();
});

test('Render as overlay', () => {
    const props = createProps({
        overlayType: 'overlay',
    });
    render(<CollectionFormOverlay {...props} />);

    expect((Overlay: any)).toBeCalled();
});

test('Render as dialog', () => {
    const props = createProps({
        overlayType: 'dialog',
    });
    render(<CollectionFormOverlay {...props} />);

    expect((Dialog: any)).toBeCalled();
});

test('Keep title when closing overlay until new overlay opens', async() => {
    const props = createProps({
        overlayType: 'overlay',
    });
    const {rerender} = render(<CollectionFormOverlay {...props} />);
    const {resourceStore} = props;

    rerender(<CollectionFormOverlay {...props} operationType="create" resourceStore={resourceStore} />);
    await waitFor(() => {
        expect(getLatestMockProps((Overlay: any)).title).toEqual('sulu_media.add_collection');
    });
    let overlayProps: any = getLatestMockProps((Overlay: any));
    expect(overlayProps.open).toEqual(true);

    rerender(<CollectionFormOverlay {...props} operationType={null} resourceStore={resourceStore} />);
    overlayProps = getLatestMockProps((Overlay: any));
    expect(overlayProps.open).toEqual(false);
    expect(overlayProps.title).toEqual('sulu_media.add_collection');

    rerender(<CollectionFormOverlay {...props} operationType="update" resourceStore={resourceStore} />);
    await waitFor(() => {
        expect(getLatestMockProps((Overlay: any)).title).toEqual('sulu_media.edit_collection');
    });
    overlayProps = getLatestMockProps((Overlay: any));
    expect(overlayProps.open).toEqual(true);

    rerender(<CollectionFormOverlay {...props} operationType={null} resourceStore={resourceStore} />);
    overlayProps = getLatestMockProps((Overlay: any));
    expect(overlayProps.open).toEqual(false);
    expect(overlayProps.title).toEqual('sulu_media.edit_collection');
});

test('Call destroy of ResourceFormStore when unmounted', () => {
    const props = createProps();
    const {unmount} = render(<CollectionFormOverlay {...props} />);
    const formStore = resourceFormStoreFactory.createFromResourceStore.mock.results[0].value;

    unmount();

    expect(formStore.destroy).toBeCalledWith();
});
