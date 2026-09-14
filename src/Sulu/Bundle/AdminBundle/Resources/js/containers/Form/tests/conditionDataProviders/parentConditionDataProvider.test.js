// @flow
import MemoryFormStore from '../../stores/MemoryFormStore';
import FormInspector from '../../FormInspector';
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

test('Return null for not existing path', () => {
    const data = {
        title: 'Title',
        blocks: [
            {title: 'Block title 1'},
            {title: 'Block title 2'},
        ],
    };

    expect(parentConditionDataProvider(data, '/not/existing/path')).toEqual({__parent: null});
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
                title: 'Block title',
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
                title: 'Block title',
                blocks: [
                    {title: 'Block title 1'},
                    {title: 'Block title 2'},
                ],
            },
            title: 'Block title 2',
        },
    });
});

test('Return injected parent from form options as parent of a root level property', () => {
    const parent = {type: 'image', settings: {hidden: false}};
    // $FlowFixMe
    const formInspector = ({options: {__parent: parent}}: any);

    expect(parentConditionDataProvider({hidden: false}, '/hidden', formInspector)).toEqual({__parent: parent});
});

test('Return injected parent from form options when no dataPath is given', () => {
    const parent = {type: 'image'};
    // $FlowFixMe
    const formInspector = ({options: {__parent: parent}}: any);

    expect(parentConditionDataProvider({hidden: false}, undefined, formInspector)).toEqual({__parent: parent});
});

test('Graft injected parent as outermost parent of a nested property', () => {
    const parent = {type: 'image'};
    // $FlowFixMe
    const formInspector = ({options: {__parent: parent}}: any);
    const data = {schedules: [{start: 'now'}]};

    expect(parentConditionDataProvider(data, '/schedules/0/start', formInspector)).toEqual({
        __parent: {
            start: 'now',
            __parent: parent,
        },
    });
});

test('Ignore form options without an injected parent', () => {
    // $FlowFixMe
    const formInspector = ({options: {}}: any);

    expect(parentConditionDataProvider({hidden: false}, '/hidden', formInspector)).toEqual({__parent: {hidden: false}});
});

test('Read the injected parent from a real FormInspector over a MemoryFormStore', () => {
    const parent = {type: 'image', settings: {hidden: false}};
    const formStore = new MemoryFormStore({hidden: false}, {}, undefined, undefined, undefined, {__parent: parent});
    const formInspector = new FormInspector(formStore);

    expect(parentConditionDataProvider({hidden: false}, '/hidden', formInspector)).toEqual({__parent: parent});
});
