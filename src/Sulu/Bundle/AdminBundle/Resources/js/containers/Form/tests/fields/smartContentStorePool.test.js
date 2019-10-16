// @flow
import {extendObservable as mockExtendObservable} from 'mobx';
import smartContentStorePool from '../../fields/smartContentStorePool';
import SmartContentStore from '../../../SmartContent/stores/SmartContentStore';

jest.mock('../../../SmartContent/stores/SmartContentStore', () => jest.fn(function() {
    this.excludedIds = [];
    this.setExcludedIds = jest.fn((excludedIds) => {
        this.excludedIds = excludedIds;
    });

    mockExtendObservable(this, {itemsLoading: false});
}));

beforeEach(() => {
    smartContentStorePool.clear();
});

test('Add and remove SmartContentStores', () => {
    const smartContentStore1 = new SmartContentStore('pages');
    const smartContentStore2 = new SmartContentStore('pages');

    smartContentStorePool.add(smartContentStore1);
    smartContentStorePool.add(smartContentStore2);

    expect(smartContentStorePool.stores).toEqual([smartContentStore1, smartContentStore2]);

    smartContentStorePool.remove(smartContentStore1);
    expect(smartContentStorePool.stores).toEqual([smartContentStore2]);
});

test('Add same SmartContentStore twice should throw an error', () => {
    const smartContentStore = new SmartContentStore('pages');

    smartContentStorePool.add(smartContentStore);

    expect(() => smartContentStorePool.add(smartContentStore)).toThrow(/twice/);
});

test('Updated excluded ids', () => {
    const smartContentStore1 = new SmartContentStore('pages');
    const smartContentStore2 = new SmartContentStore('pages');
    const smartContentStore3 = new SmartContentStore('pages');

    smartContentStorePool.add(smartContentStore1);
    smartContentStorePool.add(smartContentStore2);
    smartContentStorePool.add(smartContentStore3);

    smartContentStore1.items = [{id: 1}];
    smartContentStore2.items = [{id: 2}, {id: 3}];

    smartContentStorePool.updateExcludedIds();

    expect(smartContentStore1.excludedIds).toEqual([]);
    expect(smartContentStore2.excludedIds).toEqual([1]);
    expect(smartContentStore3.excludedIds).toEqual([1, 2, 3]);
});

test('Updated excluded ids should wait if something is currently loading', () => {
    const smartContentStore1 = new SmartContentStore('pages');
    const smartContentStore2 = new SmartContentStore('pages');
    const smartContentStore3 = new SmartContentStore('pages');

    smartContentStore1.itemsLoading = true;
    smartContentStore2.itemsLoading = true;
    smartContentStore3.itemsLoading = true;

    smartContentStorePool.add(smartContentStore1);
    smartContentStorePool.add(smartContentStore2);
    smartContentStorePool.add(smartContentStore3);

    smartContentStore1.items = [{id: 1}];
    smartContentStore2.items = [{id: 2}, {id: 3}];

    smartContentStorePool.updateExcludedIds();

    expect(smartContentStore1.excludedIds).toEqual([]);
    expect(smartContentStore2.excludedIds).toEqual([]);
    expect(smartContentStore3.excludedIds).toEqual([]);

    smartContentStore1.itemsLoading = false;
    expect(smartContentStore1.excludedIds).toEqual([]);
    expect(smartContentStore2.excludedIds).toEqual([1]);
    expect(smartContentStore3.excludedIds).toEqual([]);

    smartContentStore2.itemsLoading = false;
    expect(smartContentStore1.excludedIds).toEqual([]);
    expect(smartContentStore2.excludedIds).toEqual([1]);
    expect(smartContentStore3.excludedIds).toEqual([1, 2, 3]);
});
