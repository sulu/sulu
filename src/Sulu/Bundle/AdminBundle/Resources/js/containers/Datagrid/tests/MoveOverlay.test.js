// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import MoveOverlay from '../MoveOverlay';
import DatagridStore from '../stores/DatagridStore';

jest.mock('../stores/DatagridStore', () => function() {
    this.clearSelection = jest.fn();
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(),
}));

test('Pass the correct properties to the Overlay and Datagrid', () => {
    const store = new DatagridStore('snippets', {page: observable.box()});
    const closeSpy = jest.fn();
    const confirmSpy = jest.fn();

    const moveOverlay = shallow(
        <MoveOverlay
            adapters={['test1', 'test2']}
            disabledId={3}
            loading={true}
            onClose={closeSpy}
            onConfirm={confirmSpy}
            open={true}
            store={store}
        />
    );

    expect(moveOverlay.find('Overlay').props()).toEqual(expect.objectContaining({
        confirmLoading: true,
        onClose: closeSpy,
        open: true,
    }));

    expect(moveOverlay.find('Datagrid').props()).toEqual(expect.objectContaining({
        adapters: ['test1', 'test2'],
        disabledIds: [3],
        store,
    }));
});

test('Closing the overlay should reset the selection', () => {
    const store = new DatagridStore('snippets', {page: observable.box()});

    const moveOverlay = shallow(
        <MoveOverlay
            adapters={['test1', 'test2']}
            disabledId={3}
            loading={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            store={store}
        />
    );

    moveOverlay.setProps({open: false});

    expect(moveOverlay.find('Overlay').prop('open')).toEqual(false);
    expect(store.clearSelection).toBeCalledWith();
});

test('Confirming should call the onConfirm callback with the selected id', () => {
    const store = new DatagridStore('snippets', {page: observable.box()});
    // $FlowFixMe
    store.selectionIds = [5];

    const confirmSpy = jest.fn();

    const moveOverlay = shallow(
        <MoveOverlay
            adapters={['test1', 'test2']}
            disabledId={3}
            loading={true}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            store={store}
        />
    );

    moveOverlay.find('Overlay').prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith(5);
});
