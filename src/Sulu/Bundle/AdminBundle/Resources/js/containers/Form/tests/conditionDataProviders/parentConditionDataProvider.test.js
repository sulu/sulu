// @flow
import parentConditionDataProvider from '../../conditionDataProviders/parentConditionDataProvider';

test('Return parent for root level', () => {
    const data = {title: 'Test'};

    expect(parentConditionDataProvider(data, '/title')).toEqual({__parent: {title: 'Test'}});
});

test('Return parent for first block', () => {
    const data = {
        title: 'Title',
        blocks: [
            {title: 'Block title 1'},
            {title: 'Block title 2'},
        ],
    };

    expect(parentConditionDataProvider(data, '/blocks/0/title')).toEqual({__parent: {title: 'Block title 1'}});
});

test('Return parent for second block', () => {
    const data = {
        title: 'Title',
        blocks: [
            {title: 'Block title 1'},
            {title: 'Block title 2'},
        ],
    };

    expect(parentConditionDataProvider(data, '/blocks/1/title')).toEqual({__parent: {title: 'Block title 2'}});
});

test('Return parent for nested second block', () => {
    const data = {
        title: 'Title',
        blocks: [
            {
                blocks: [
                    {title: 'Block title 1'},
                    {title: 'Block title 2'},
                ],
            },
        ],
    };

    expect(parentConditionDataProvider(data, '/blocks/0/blocks/1/title')).toEqual({
        __parent: {
            __parent: {
                blocks: [
                    {title: 'Block title 1'},
                    {title: 'Block title 2'},
                ],
            },
            title: 'Block title 2',
        },
    });
});
