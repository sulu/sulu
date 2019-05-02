// @flow
import TeaserStore from '../../stores/TeaserStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Load and reload teasers when new ones are added', () => {
    const teaserPromise1 = Promise.resolve({
        _embedded: {
            teasers: [],
        },
    });
    ResourceRequester.getList.mockReturnValue(teaserPromise1);
    const teaserStore = new TeaserStore();

    expect(ResourceRequester.getList).toHaveBeenLastCalledWith('teasers', {ids: []});

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
            .toHaveBeenLastCalledWith('teasers', {ids: ['pages;1', 'pages;2', 'contacts;1']});

        return teaserPromise2.then(() => {
            expect(teaserStore.teaserItems).toEqual(teasers);
            teaserStore.destroy();
        });
    });
});

test('Add items only once to teaser store', () => {
    const teaserStore = new TeaserStore();

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
    const teaserStore = new TeaserStore();

    expect(ResourceRequester.getList).toHaveBeenLastCalledWith('teasers', {ids: []});

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
    const teaserStore = new TeaserStore();

    const disposerSpy = jest.fn();
    teaserStore.teaserDisposer = disposerSpy;

    teaserStore.destroy();
    expect(disposerSpy).toBeCalledWith();
});
