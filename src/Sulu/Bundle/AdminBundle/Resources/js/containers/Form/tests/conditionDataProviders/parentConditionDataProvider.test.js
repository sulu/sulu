// @flow
import parentConditionDataProvider from '../../conditionDataProviders/parentConditionDataProvider';

test('Return parent for root level', () => {
    const data = {
        title: 'Test',
    };

    expect(parentConditionDataProvider(data, '/title')).toEqual({__parent: data});
});

test('Return parent for first block', () => {
    const blocks = [
        {
            title: 'Block title 1',
        },
        {
            title: 'Block title 2',
        },
    ];

    const data = {
        title: 'Title',
        blocks,
    };

    expect(parentConditionDataProvider(data, '/blocks/0/title')).toEqual({__parent: blocks[0]});
});

test('Return parent for second block', () => {
    const blocks = [
        {
            title: 'Block title 1',
        },
        {
            title: 'Block title 2',
        },
    ];

    const data = {
        title: 'Title',
        blocks,
    };

    expect(parentConditionDataProvider(data, '/blocks/1/title')).toEqual({__parent: blocks[1]});
});

test('Return parent for nested second block', () => {
    const nestedBlocks = [
        {
            title: 'Block title 1',
        },
        {
            title: 'Block title 2',
        },
    ];

    const data = {
        title: 'Title',
        blocks: [
            {
                blocks: nestedBlocks,
            },
        ],
    };

    expect(parentConditionDataProvider(data, '/blocks/0/blocks/1/title')).toEqual({__parent: nestedBlocks[1]});
});
