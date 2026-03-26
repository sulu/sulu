// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import SingleMediaSelection from '../SingleMediaSelection';
import SingleMediaSelectionOverlay from '../../SingleMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../SingleMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn());

function renderSingleMediaSelection(props: Object = {}) {
    return render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
            {...props}
        />
    );
}

function getLatestOverlayProps() {
    const calls = ((SingleMediaSelectionOverlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getSingleSelectionStoreInstance(index: number = 0) {
    const instances = ((SingleSelectionStore: any).mock.instances: any);
    return instances[index];
}

beforeEach(() => {
    jest.clearAllMocks();

    // $FlowFixMe
    SingleSelectionStore.mockImplementation(function() {
        this.item = undefined;
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });
});

test('Component should render without selected media', () => {
    const {container} = renderSingleMediaSelection();

    expect(SingleSelectionStore).toBeCalledWith('media', undefined, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should render with display options', () => {
    const {container} = renderSingleMediaSelection({
        displayOptions: ['top', 'bottom'],
    });

    expect(container).toMatchSnapshot();
});

test('Component should render with display options and correctly selected icon', () => {
    const {container} = renderSingleMediaSelection({
        displayOptions: ['top', 'bottom'],
        value: {displayOption: 'left', id: undefined},
    });

    expect(container).toMatchSnapshot();
});

test('Component should render with selected media', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        };
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderSingleMediaSelection({
        value: {displayOption: undefined, id: 33},
    });

    expect(SingleSelectionStore).toBeCalledWith('media', 33, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should render with selected media without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'application/pdf',
        };
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {container} = renderSingleMediaSelection({
        value: {displayOption: undefined, id: 33},
    });

    expect(SingleSelectionStore).toBeCalledWith('media', 33, expect.anything());
    expect(container).toMatchSnapshot();
});

test('Component should pass className to SingleItemSelection', () => {
    renderSingleMediaSelection({
        className: 'test',
    });

    expect(document.querySelector('.singleItemSelection.test')).not.toBeNull();
});

test('Component should pass types to SingleMediaSelectionOverlay', () => {
    renderSingleMediaSelection({
        types: ['image', 'video'],
    });

    expect(getLatestOverlayProps().types).toEqual(['image', 'video']);
});

test('Click on media-button should open an overlay', async() => {
    const user = userEvent.setup();

    renderSingleMediaSelection();

    expect(getLatestOverlayProps().open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'su-image'}));
    expect(getLatestOverlayProps().open).toEqual(true);
});

test('Click on remove-button should clear the selection store', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        };
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    renderSingleMediaSelection({
        value: {displayOption: undefined, id: 33},
    });

    const singleSelectionStore = getSingleSelectionStoreInstance();

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));
    expect(singleSelectionStore.clear).toBeCalled();
});

test('Media that is selected in the overlay should be set to the selection store on confirm', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    renderSingleMediaSelection();

    const singleSelectionStore = getSingleSelectionStoreInstance();

    getLatestOverlayProps().onConfirm({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(singleSelectionStore.set).toBeCalledWith(expect.objectContaining({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    }));
});

test('Should call the onChange handler if the displayOption changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    renderSingleMediaSelection({
        displayOptions: ['left'],
        onChange: changeSpy,
    });

    await user.click(screen.getByRole('button', {name: /su-display-default/}));
    await user.click(screen.getByText('sulu_media.left'));

    expect(changeSpy).toBeCalledWith({displayOption: 'left', id: undefined});
});

test('Should call given onChange handler if value of selection store changes', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        this.clear = jest.fn();
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const changeSpy = jest.fn();

    renderSingleMediaSelection({onChange: changeSpy});

    const singleSelectionStore = getSingleSelectionStoreInstance();

    expect(changeSpy).not.toBeCalled();
    act(() => {
        singleSelectionStore.item = {
            id: 77,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {},
        };
    });
    expect(changeSpy).toBeCalledWith({id: 77}, singleSelectionStore.item);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        this.clear = jest.fn();
        this.set = jest.fn();
        this.loading = false;
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    renderSingleMediaSelection({onChange: changeSpy});

    const singleSelectionStore = getSingleSelectionStoreInstance();

    act(() => {
        singleSelectionStore.item = {id: 77, mimeType: 'image/jpeg', thumbnails: {}};
    });
    expect(changeSpy).toBeCalledWith({id: 77}, singleSelectionStore.item);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    act(() => {
        unrelatedObservable.set(55);
    });
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the component props change', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        this.clear = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const changeSpy = jest.fn();

    const {rerender} = renderSingleMediaSelection({
        onChange: changeSpy,
        value: {displayOption: undefined, id: 5},
    });

    rerender(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Should not call the onItemClick callback if no item is available', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = undefined;
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const itemClickSpy = jest.fn();

    renderSingleMediaSelection({
        onItemClick: itemClickSpy,
        value: {displayOption: undefined, id: 5},
    });

    await user.click(screen.getByRole('button', {name: 'sulu_media.select_media_singular'}));
    expect(itemClickSpy).not.toBeCalled();
});

test('Should call the onItemClick callback if the item is clicked', async() => {
    const user = userEvent.setup();

    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {id: 6, mimeType: 'image/jpeg'};
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const itemClickSpy = jest.fn();

    renderSingleMediaSelection({
        onItemClick: itemClickSpy,
        value: {displayOption: undefined, id: 5},
    });

    await user.click(screen.getByRole('button', {name: /fa-file-image-o/i}));
    expect(itemClickSpy).toBeCalledWith(6, {id: 6, mimeType: 'image/jpeg'});
});

test('Should not call the loadItem callback if the component props id change to same value', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        this.clear = jest.fn();
        this.set = jest.fn();
        this.loading = false;
    });

    const {rerender} = renderSingleMediaSelection({
        value: {displayOption: undefined, id: 5},
    });

    const singleSelectionStore = getSingleSelectionStoreInstance();

    rerender(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 5}}
        />
    );

    expect(singleSelectionStore.loadItem).not.toBeCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    renderSingleMediaSelection({
        disabled: true,
        valid: false,
    });

    expect(screen.getByRole('button', {name: 'su-image'})).toBeDisabled();
    expect(document.querySelector('.error')).not.toBeNull();
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.clear = jest.fn();
        this.loadItem = jest.fn();
        this.set = jest.fn();
        mockExtendObservable(this, {
            loading: false,
        });
    });

    renderSingleMediaSelection({disabled: true});

    expect(document.body).toHaveTextContent('sulu_media.select_media_singular');

    const singleSelectionStore = getSingleSelectionStoreInstance();

    act(() => {
        singleSelectionStore.loading = true;
    });

    expect(document.body).toHaveTextContent('…');
});
