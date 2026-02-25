/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {render, waitFor} from '@testing-library/react';
import {List} from 'sulu-admin-bundle/containers';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MediaCollection from '../MediaCollection';
import MultiMediaDropzone from '../../MultiMediaDropzone';
import CollectionSection from '../CollectionSection';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Divider: jest.fn(() => <div data-testid="divider" />),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    List: jest.fn(() => <div data-testid="list" />),
    ListStore: jest.fn(),
}));

jest.mock('../../MultiMediaDropzone', () => jest.fn(({children}) => (
    <div data-testid="multi-media-dropzone">{children}</div>
)));

jest.mock('../CollectionSection', () => jest.fn(() => <div data-testid="collection-section" />));

const createCollectionStore = (overrides = {}) => ({
    id: 1,
    loading: false,
    locked: false,
    permissions: {},
    resourceStore: {},
    ...overrides,
});

const createMediaListStore = (overrides = {}) => ({
    loading: false,
    reload: jest.fn(),
    select: jest.fn(),
    ...overrides,
});

const createCollectionListStore = (overrides = {}) => ({
    ...overrides,
});

const createProps = (overrides = {}) => ({
    collectionListStore: createCollectionListStore(),
    collectionStore: createCollectionStore(),
    locale: observable.box('en'),
    mediaListAdapters: ['media_card_overview'],
    mediaListStore: createMediaListStore(),
    onCollectionNavigate: jest.fn(),
    onUploadOverlayClose: jest.fn(),
    onUploadOverlayOpen: jest.fn(),
    uploadOverlayOpen: false,
    ...overrides,
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('renders media collection and upload action by default', () => {
    const props = createProps();
    const {asFragment} = render(<MediaCollection {...props} />);

    const dropzoneProps = getLatestMockProps(MultiMediaDropzone);
    const listProps = getLatestMockProps(List);
    const collectionSectionProps = getLatestMockProps(CollectionSection);

    expect(dropzoneProps.disabled).toBe(false);
    expect(listProps.actions).toEqual([
        {
            disabled: false,
            icon: 'su-upload',
            label: 'sulu_media.upload_file',
            onClick: props.onUploadOverlayOpen,
        },
    ]);
    expect(collectionSectionProps.addable).toBe(true);
    expect(collectionSectionProps.editable).toBe(true);
    expect(collectionSectionProps.deletable).toBe(true);
    expect(collectionSectionProps.securable).toBe(true);
    expect(asFragment()).toMatchSnapshot();
});

test('hides upload action when hideUploadAction is true', () => {
    const props = createProps({hideUploadAction: true});
    render(<MediaCollection {...props} />);

    const listProps = getLatestMockProps(List);
    expect(listProps.actions).toEqual([]);
});

test('respects collection lock and explicit permissions', () => {
    const props = createProps({
        collectionStore: createCollectionStore({
            loading: true,
            locked: false,
            permissions: {
                add: false,
                edit: true,
                delete: false,
                security: true,
            },
        }),
    });
    render(<MediaCollection {...props} />);

    const dropzoneProps = getLatestMockProps(MultiMediaDropzone);
    const collectionSectionProps = getLatestMockProps(CollectionSection);
    const listProps = getLatestMockProps(List);

    expect(dropzoneProps.disabled).toBe(true);
    expect(collectionSectionProps.addable).toBe(false);
    expect(collectionSectionProps.editable).toBe(true);
    expect(collectionSectionProps.deletable).toBe(false);
    expect(collectionSectionProps.securable).toBe(true);
    expect(listProps.actions).toEqual([]);
});

test('forwards collection navigate callback to parent', () => {
    const onCollectionNavigate = jest.fn();
    const props = createProps({onCollectionNavigate});
    render(<MediaCollection {...props} />);

    getLatestMockProps(CollectionSection).onCollectionNavigate(42);
    expect(onCollectionNavigate).toHaveBeenCalledWith(42);
});

test('forwards media click callback to list', () => {
    const onMediaNavigate = jest.fn();
    const props = createProps({onMediaNavigate});
    render(<MediaCollection {...props} />);

    expect(getLatestMockProps(List).onItemClick).toBe(onMediaNavigate);
});

test('reloads and selects uploaded media items', async() => {
    const mediaListStore = createMediaListStore({loading: false});
    const props = createProps({mediaListStore});
    render(<MediaCollection {...props} />);

    getLatestMockProps(MultiMediaDropzone).onUpload([{id: 10}, {id: 11}]);

    await waitFor(() => expect(mediaListStore.reload).toHaveBeenCalled());
    expect(mediaListStore.select).toHaveBeenCalledWith({id: 10});
    expect(mediaListStore.select).toHaveBeenCalledWith({id: 11});
});

test('reloads list and forwards upload errors', async() => {
    const mediaListStore = createMediaListStore({loading: false});
    const onUploadError = jest.fn();
    const props = createProps({mediaListStore, onUploadError});
    render(<MediaCollection {...props} />);

    const errors = [{detail: 'error 1'}];
    getLatestMockProps(MultiMediaDropzone).onUploadError(errors);

    await waitFor(() => expect(mediaListStore.reload).toHaveBeenCalled());
    expect(onUploadError).toHaveBeenCalledWith(errors);
});
