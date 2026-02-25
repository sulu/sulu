// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {act, render} from '@testing-library/react';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SingleMediaSelection from '../SingleMediaSelection';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../SingleMediaSelectionOverlay', () => jest.fn(() => null));

jest.mock(
    'sulu-admin-bundle/components/SingleItemSelection',
    () => jest.fn(function SingleItemSelectionMock({children}) {
        return <div>{children}</div>;
    })
);

jest.mock('sulu-admin-bundle/stores/SingleSelectionStore', () => jest.fn());

const SingleItemSelectionMock: any = jest.requireMock('sulu-admin-bundle/components/SingleItemSelection');
const SingleMediaSelectionOverlayMock: any = jest.requireMock('../../SingleMediaSelectionOverlay');
const SingleSelectionStoreMock: any = jest.requireMock('sulu-admin-bundle/stores/SingleSelectionStore');

const getStore = () => SingleSelectionStoreMock.mock.instances[SingleSelectionStoreMock.mock.instances.length - 1];

let initialStoreItem;
let initialStoreLoading;

beforeEach(() => {
    jest.clearAllMocks();
    initialStoreItem = undefined;
    initialStoreLoading = false;

    SingleSelectionStoreMock.mockImplementation(function() {
        this.clear = jest.fn(() => {
            this.item = undefined;
        });
        this.loadItem = jest.fn();
        this.set = jest.fn((item) => {
            this.item = item;
        });
        mockExtendObservable(this, {
            item: initialStoreItem,
            loading: initialStoreLoading,
        });
    });
});

test('Component should render without selected media', () => {
    const {asFragment} = render(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(SingleSelectionStore).toBeCalledWith('media', undefined, expect.anything());
    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with display options', () => {
    const {asFragment} = render(
        <SingleMediaSelection
            displayOptions={['top', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Component should render with selected media', () => {
    initialStoreItem = {
        id: 33,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': 'http://lorempixel.com/25/25',
        },
    };

    const {asFragment} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('media', 33, expect.anything());
    expect(asFragment()).toMatchSnapshot();
});

test('Component should pass className to SingleItemSelection', () => {
    render(
        <SingleMediaSelection
            className="test"
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={undefined}
        />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).className).toEqual('test');
});

test('Component should pass types to SingleMediaSelectionOverlay', () => {
    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            types={['image', 'video']}
            value={undefined}
        />
    );

    expect(getLatestMockProps(SingleMediaSelectionOverlayMock).types).toEqual(['image', 'video']);
});

test('Click on media button should open an overlay', () => {
    render(<SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    expect(getLatestMockProps(SingleMediaSelectionOverlayMock).open).toEqual(false);
    getLatestMockProps(SingleItemSelectionMock).leftButton.onClick();
    expect(getLatestMockProps(SingleMediaSelectionOverlayMock).open).toEqual(true);
});

test('Click on remove button should clear selection store', () => {
    initialStoreItem = {
        id: 33,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {'sulu-25x25': 'http://lorempixel.com/25/25'},
    };

    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 33}}
        />
    );

    getLatestMockProps(SingleItemSelectionMock).onRemove();
    expect(getStore().clear).toBeCalled();
});

test('Media selected in overlay should be set to selection store on confirm', () => {
    render(<SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />);

    getLatestMockProps(SingleMediaSelectionOverlayMock).onConfirm({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(getStore().set).toBeCalledWith(expect.objectContaining({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    }));
});

test('Should call onChange handler if displayOption changes', () => {
    const changeSpy = jest.fn();

    render(
        <SingleMediaSelection
            displayOptions={['left']}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={undefined}
        />
    );

    getLatestMockProps(SingleItemSelectionMock).rightButton.onClick('left');
    expect(changeSpy).toBeCalledWith({displayOption: 'left', id: undefined});
});

test('Should call onChange handler if value of selection store changes', () => {
    const changeSpy = jest.fn();

    render(<SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />);

    expect(changeSpy).not.toBeCalled();

    const store = getStore();
    act(() => {
        store.item = {
            id: 77,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnails: {},
        };
    });

    expect(changeSpy).toBeCalledWith({id: 77}, store.item);
});

test('Should not call onChange callback if unrelated observable changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    render(<SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />);

    const store = getStore();
    act(() => {
        store.item = {id: 77, mimeType: 'image/jpeg', thumbnails: {}};
    });

    expect(changeSpy).toBeCalledWith({id: 77}, store.item);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    act(() => {
        unrelatedObservable.set(55);
    });
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call onChange callback if component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

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

test('Should call onItemClick callback if item is clicked', () => {
    initialStoreItem = {id: 6, mimeType: 'image/jpeg'};
    const itemClickSpy = jest.fn();

    render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, id: 5}}
        />
    );

    getLatestMockProps(SingleItemSelectionMock).onItemClick(6, {id: 6, mimeType: 'image/jpeg'});
    expect(itemClickSpy).toBeCalledWith(6, {id: 6, mimeType: 'image/jpeg'});
});

test('Should not call loadItem callback if props id changes to same value', () => {
    const {rerender} = render(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 5}}
        />
    );

    const store = getStore();
    rerender(
        <SingleMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: undefined, id: 5}}
        />
    );

    expect(store.loadItem).not.toBeCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    render(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            valid={false}
            value={undefined}
        />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).disabled).toEqual(true);
    expect(getLatestMockProps(SingleItemSelectionMock).valid).toEqual(false);
});

test('Set loading prop of SingleItemSelection if store is loading', () => {
    initialStoreLoading = false;
    render(
        <SingleMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).loading).toEqual(false);

    const store = getStore();
    act(() => {
        store.loading = true;
    });
    expect(getLatestMockProps(SingleItemSelectionMock).loading).toEqual(true);
});
