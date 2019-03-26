// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
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

test('Component should render without selected media', () => {
    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(SingleSelectionStore).toBeCalledWith('media', undefined, expect.anything());
    expect(singleMediaSelection.render()).toMatchSnapshot();
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
    });

    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={{id: 33}} />
    );

    expect(SingleSelectionStore).toBeCalledWith('media', 33, expect.anything());
    expect(singleMediaSelection.render()).toMatchSnapshot();
});

test('Component should render with selected media without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.item = {
            id: 33,
            title: 'test media',
            mimeType: 'application/pdf',
        };
    });

    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={{id: 33}} />
    );

    expect(SingleSelectionStore).toBeCalledWith('media', 33, expect.anything());
    expect(singleMediaSelection.render()).toMatchSnapshot();
});

test('Click on media-button should open an overlay', () => {
    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(singleMediaSelection.find(SingleMediaSelectionOverlay).prop('open')).toEqual(false);
    singleMediaSelection.find('.button').simulate('click');
    expect(singleMediaSelection.find(SingleMediaSelectionOverlay).prop('open')).toEqual(true);
});

test('Click on remove-button should clear the selection store', () => {
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
    });

    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={{id: 33}} />
    );

    singleMediaSelection.find('.removeButton').simulate('click');
    expect(singleMediaSelection.instance().singleMediaSelectionStore.clear).toBeCalled();
});

test('Media that is selected in the overlay should be set to the selection store on confirm', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.set = jest.fn();
    });

    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    singleMediaSelection.instance().handleOverlayConfirm({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(singleMediaSelection.instance().singleMediaSelectionStore.set).toBeCalledWith(expect.objectContaining({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    }));
});

test('Should call given onChange handler if value of selection store changes', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const changeSpy = jest.fn();

    const singleMediaSelectionInstance = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />
    ).instance();

    expect(changeSpy).not.toBeCalled();
    singleMediaSelectionInstance.singleMediaSelectionStore.item = {
        id: 77,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {},
    };
    expect(changeSpy).toBeCalledWith({id: 77});
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
        mockExtendObservable(this, {
            item: undefined,
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const singleMediaSelectionInstance = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />
    ).instance();

    // change callback should be called when item of the store mock changes
    singleMediaSelectionInstance.singleMediaSelectionStore.item = {id: 77, thumbnails: {}};
    expect(changeSpy).toBeCalledWith({id: 77});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the component props change', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        this.loadItem = jest.fn();
    });

    const changeSpy = jest.fn();

    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={{id: 5}} />
    );

    singleMediaSelection.setProps({disabled: true});
    expect(changeSpy).not.toBeCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    const singleMediaSelection = shallow(
        <SingleMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            valid={false}
            value={undefined}
        />
    );

    expect(singleMediaSelection.find(SingleItemSelection).prop('disabled')).toEqual(true);
    expect(singleMediaSelection.find(SingleItemSelection).prop('valid')).toEqual(false);
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
    // $FlowFixMe
    SingleSelectionStore.mockImplementationOnce(function() {
        mockExtendObservable(this, {
            loading: false,
        });
    });

    const singleMediaSelection = shallow(
        <SingleMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(singleMediaSelection.find(SingleItemSelection).prop('loading')).toEqual(false);
    singleMediaSelection.instance().singleMediaSelectionStore.loading = true;
    expect(singleMediaSelection.find(SingleItemSelection).prop('loading')).toEqual(true);
});
