// @flow
import {observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import TeaserStore from '../../stores/TeaserStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Load and reload teasers when new ones are added', () => {
    const teaserPromise1 = Promise.resolve({
        _embedded: {
            teasers: [],
        },
    });
    ResourceRequester.getList.mockReturnValue(teaserPromise1);
    const teaserStore = new TeaserStore(observable.box('de'));

    expect(ResourceRequester.getList).toHaveBeenLastCalledWith('teasers', {ids: [], locale: 'de'});

    return teaserPromise1.then(() => {
        expect(teaserStore.teaserItems).toHaveLength(0);

        const teasers = [
            {
                id: 1,
                type: 'pages',
            },
            {
                id: 2,
                type: 'pages',
            },
            {
                id: 1,
                type: 'contacts',
            },
        ];

        const teaserPromise2 = Promise.resolve({
            _embedded: {
                teasers,
            },
        });
        ResourceRequester.getList.mockReturnValue(teaserPromise2);

        teaserStore.add('pages', 1);
        teaserStore.add('pages', 2);
        teaserStore.add('contacts', 1);

        expect(ResourceRequester.getList)
            .toHaveBeenLastCalledWith('teasers', {ids: ['pages;1', 'pages;2', 'contacts;1'], locale: 'de'});

        return teaserPromise2.then(() => {
            expect(teaserStore.teaserItems).toEqual(teasers);
            teaserStore.destroy();
        });
    });
});

test('Add items only once to teaser store', () => {
    const teaserStore = new TeaserStore(observable.box('en'));

    teaserStore.add('pages', 1);
    teaserStore.add('pages', 1);

    expect(teaserStore.teaserItemIds).toEqual([{id: 1, type: 'pages'}]);
});

test('Use findById function to load teasers', () => {
    const teasers = [
        {
            id: 1,
            type: 'pages',
        },
        {
            id: 2,
            type: 'pages',
        },
        {
            id: 1,
            type: 'contacts',
        },
    ];

    const teaserPromise = Promise.resolve({
        _embedded: {
            teasers,
        },
    });
    ResourceRequester.getList.mockReturnValue(teaserPromise);
    const teaserStore = new TeaserStore(observable.box('en'));

    expect(ResourceRequester.getList).toHaveBeenLastCalledWith('teasers', {ids: [], locale: 'en'});

    return teaserPromise.then(() => {
        expect(teaserStore.findById('pages', 2)).toEqual(teasers[1]);
        expect(teaserStore.findById('contacts', 1)).toEqual(teasers[2]);
        teaserStore.destroy();
    });
});

test('Destroy should call the autorun disposer', () => {
    const teaserPromise1 = Promise.resolve({
        _embedded: {
            teasers: [],
        },
    });
    ResourceRequester.getList.mockReturnValue(teaserPromise1);
    const teaserStore = new TeaserStore(observable.box('de'));

    const disposerSpy = jest.fn();
    teaserStore.teaserDisposer = disposerSpy;

    teaserStore.destroy();
    expect(disposerSpy).toBeCalledWith();
});
