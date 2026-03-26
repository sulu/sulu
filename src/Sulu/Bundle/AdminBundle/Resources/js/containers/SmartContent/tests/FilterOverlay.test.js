// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import Button from '../../../components/Button';
import Number from '../../../components/Number';
import Overlay from '../../../components/Overlay';
import SingleSelect from '../../../components/SingleSelect';
import Toggler from '../../../components/Toggler';
import MultiSelect from '../../../components/MultiSelect';
import MultiListOverlay from '../../../containers/MultiListOverlay';
import SingleListOverlay from '../../../containers/SingleListOverlay';
import SmartContentStore from '../stores/SmartContentStore';
import FilterOverlay from '../FilterOverlay';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

jest.mock('../stores/SmartContentStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/Button', () => jest.fn(({children, onClick}) => (
    <button onClick={onClick} type="button">{children}</button>
)));

jest.mock('../../../components/Toggler', () => jest.fn(({children}) => (
    <div>{children}</div>
)));

jest.mock('../../../components/Number', () => jest.fn(() => null));

jest.mock('../../../components/SingleSelect', () => {
    const SingleSelect: any = jest.fn(() => null);
    SingleSelect.Option = function SingleSelectOption() {
        return null;
    };

    return SingleSelect;
});

jest.mock('../../../components/MultiSelect', () => {
    const MultiSelect: any = jest.fn(() => null);
    MultiSelect.Option = function MultiSelectOption() {
        return null;
    };

    return MultiSelect;
});

jest.mock('../../../components/Overlay', () => jest.fn(({children}) => (
    <div data-testid="overlay">{children}</div>
)));

jest.mock('../../../containers/MultiListOverlay', () => jest.fn(() => null));
jest.mock('../../../containers/SingleListOverlay', () => jest.fn(() => null));
jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function() {
    mockExtendObservable(this, {
        items: [],
    });
}));

const ButtonMock = (Button: any);
const NumberMock = (Number: any);
const OverlayMock = (Overlay: any);
const SingleSelectMock = (SingleSelect: any);
const TogglerMock = (Toggler: any);
const MultiSelectMock = (MultiSelect: any);
const MultiListOverlayMock = (MultiListOverlay: any);
const SingleListOverlayMock = (SingleListOverlay: any);
const MultiSelectionStoreMock = (MultiSelectionStore: any);

const defaultValue = {
    dataSource: 1,
    includeSubFolders: true,
    categories: [],
    categoryOperator: 'and',
    tags: [],
    tagOperator: 'or',
    audienceTargeting: true,
    sortBy: 'title',
    sortMethod: 'asc',
    presentAs: 'two',
    limitResult: 5,
    types: [],
};

const getMockCallProps = (mockComponent) => {
    return mockComponent.mock.calls.map(([props]) => props);
};

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);

    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getLastMockCallPropsMatching = (mockComponent, matcher) => {
    const props = getMockCallProps(mockComponent).filter(matcher);

    if (props.length === 0) {
        throw new Error('Expected matching mock component props');
    }

    return props[props.length - 1];
};

const getOverlayProps = () => getLastMockCallProps(OverlayMock);
const getNumberProps = () => getLastMockCallProps(NumberMock);
const getMultiSelectProps = () => getLastMockCallProps(MultiSelectMock);
const getButtonProps = (children) => getLastMockCallPropsMatching(ButtonMock, (props) => props.children === children);
const getTogglerProps = (children) => getLastMockCallPropsMatching(
    TogglerMock,
    (props) => props.children === children
);
const getSingleSelectPropsAt = (index) => {
    const singleSelectCallsPerRender = 5;
    const singleSelectProps = getMockCallProps(SingleSelectMock).slice(-singleSelectCallsPerRender);

    if (!singleSelectProps[index]) {
        throw new Error('Expected SingleSelect props at index "' + index + '"');
    }

    return singleSelectProps[index];
};
const getMultiListOverlayProps = (matcher) => getLastMockCallPropsMatching(MultiListOverlayMock, matcher);
const getSingleListOverlayProps = (matcher) => getLastMockCallPropsMatching(SingleListOverlayMock, matcher);

const getTagSelectionStore = () => {
    const stores = MultiSelectionStoreMock.mock.instances;

    if (stores.length === 0) {
        throw new Error('Expected MultiSelectionStore to be instantiated');
    }

    return stores[stores.length - 1];
};

const getFilterOverlayInstance = (filterOverlayRef) => {
    if (!filterOverlayRef.current) {
        throw new Error('Expected FilterOverlay ref to be available');
    }

    return (filterOverlayRef.current: any);
};

const renderFilterOverlay = ({
    categoryRootKey = undefined,
    dataSourceAdapter = undefined,
    dataSourceListKey = undefined,
    dataSourceResourceKey = undefined,
    defaultValue: filterDefaultValue = defaultValue,
    filterOverlayRef = undefined,
    onClose = jest.fn(),
    open = true,
    presentations = {},
    sections = [],
    smartContentStore = new SmartContentStore('content'),
    sortings = [],
    title = 'Test',
    types = [],
}: Object = {}) => {
    return {
        ...render(
            <FilterOverlay
                categoryRootKey={categoryRootKey}
                dataSourceAdapter={dataSourceAdapter}
                dataSourceListKey={dataSourceListKey}
                dataSourceResourceKey={dataSourceResourceKey}
                defaultValue={filterDefaultValue}
                onClose={onClose}
                open={open}
                presentations={presentations}
                ref={filterOverlayRef}
                sections={sections}
                smartContentStore={smartContentStore}
                sortings={sortings}
                title={title}
                types={types}
            />
        ),
        onClose,
        smartContentStore,
    };
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Do not display if open is set to false', () => {
    const smartContentStore = new SmartContentStore('content');

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        open: false,
        smartContentStore,
    });

    expect(getOverlayProps().open).toEqual(false);
});

test('Pass rootKey for categories to options for category list', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = false;

    renderFilterOverlay({
        categoryRootKey: 'test1',
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        open: false,
        sections: ['categories'],
        smartContentStore,
    });

    expect(getMultiListOverlayProps((props) => props.resourceKey === 'categories').options)
        .toEqual({rootKey: 'test1'});
});

test('Render with ListOverlays if smartContentStore is loaded', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = false;

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
    });

    expect(SingleListOverlayMock).toHaveBeenCalledTimes(1);
    expect(MultiListOverlayMock).toHaveBeenCalledTimes(1);
});

test('Render without ListOverlays if smartContentStore is not loaded', () => {
    const smartContentStore = new SmartContentStore('content');
    // $FlowFixMe
    smartContentStore.loading = true;

    renderFilterOverlay({
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
    });

    expect(SingleListOverlayMock).not.toBeCalled();
    expect(MultiListOverlayMock).not.toBeCalled();
});

test('Render with all fields', () => {
    const smartContentStore = new SmartContentStore('content');

    renderFilterOverlay({
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
    });

    expect(screen.getByText('sulu_admin.data_source')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.filter_by_categories')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.filter_by_tags')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.target_groups')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.sort_by')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.present_as')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.limit_result_to')).toBeInTheDocument();
});

test('Render with no fields', () => {
    const smartContentStore = new SmartContentStore('content');

    renderFilterOverlay({
        sections: [],
        smartContentStore,
    });

    expect(screen.queryByText('sulu_admin.data_source')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.filter_by_categories')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.filter_by_tags')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.target_groups')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.sort_by')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.present_as')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.limit_result_to')).not.toBeInTheDocument();
});

test('Fill all fields using and update SmartContentStore on confirm', () => {
    const smartContentStore = new SmartContentStore('content');
    const closeSpy = jest.fn();
    const filterOverlayRef = React.createRef();
    const pagesOverlayMatcher = (props) => props.listKey === 'pages_list' && props.resourceKey === 'pages';
    const categoriesOverlayMatcher = (props) => props.resourceKey === 'categories';

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'pages_list',
        dataSourceResourceKey: 'pages',
        filterOverlayRef,
        onClose: closeSpy,
        presentations: {
            small: 'Small',
            large: 'Large',
        },
        sections: [
            'datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit', 'types',
        ],
        smartContentStore,
        sortings: [
            {name: 'title', value: 'Title'},
            {name: 'changed', value: 'Changed'},
        ],
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    act(() => {
        getButtonProps('sulu_admin.choose_data_source').onClick();
    });
    expect(getSingleListOverlayProps(pagesOverlayMatcher).open).toEqual(true);

    act(() => {
        getSingleListOverlayProps(pagesOverlayMatcher).onConfirm({id: 2, title: 'Test'});
    });
    expect(getSingleListOverlayProps(pagesOverlayMatcher).open).toEqual(false);
    expect(screen.getByText(/sulu_admin\.data_source:\s*Test/)).toBeInTheDocument();

    act(() => {
        getTogglerProps('sulu_admin.include_sub_elements').onChange(true);
    });
    expect(getTogglerProps('sulu_admin.include_sub_elements').checked).toEqual(true);

    act(() => {
        getButtonProps('sulu_admin.choose_categories').onClick();
    });
    expect(getMultiListOverlayProps(categoriesOverlayMatcher).open).toEqual(true);

    act(() => {
        getMultiListOverlayProps(categoriesOverlayMatcher).onConfirm([
            {id: 1, name: 'Test1'},
            {id: 3, name: 'Test2'},
        ]);
    });
    expect(getMultiListOverlayProps(categoriesOverlayMatcher).open).toEqual(false);
    expect(screen.getByText(/sulu_category\.categories:\s*Test1,\s*Test2/)).toBeInTheDocument();

    act(() => {
        getSingleSelectPropsAt(0).onChange('and');
    });
    expect(getSingleSelectPropsAt(0).value).toEqual('and');

    act(() => {
        getTagSelectionStore().items.push({id: 1, name: 'Test 1'}, {id: 2, name: 'Test 3'});
    });
    expect(getFilterOverlayInstance(filterOverlayRef).tags).toEqual(['Test 1', 'Test 3']);

    act(() => {
        getSingleSelectPropsAt(1).onChange('or');
    });
    expect(getSingleSelectPropsAt(1).value).toEqual('or');

    act(() => {
        getMultiSelectProps().onChange(['default']);
    });
    expect(getMultiSelectProps().values).toEqual(['default']);

    act(() => {
        getTogglerProps('sulu_admin.use_target_groups').onChange(false);
    });
    expect(getTogglerProps('sulu_admin.use_target_groups').checked).toEqual(false);

    act(() => {
        getSingleSelectPropsAt(2).onChange('changed');
    });
    expect(getSingleSelectPropsAt(2).value).toEqual('changed');

    act(() => {
        getSingleSelectPropsAt(3).onChange('asc');
    });
    expect(getSingleSelectPropsAt(3).value).toEqual('asc');

    act(() => {
        getSingleSelectPropsAt(4).onChange('large');
    });
    expect(getSingleSelectPropsAt(4).value).toEqual('large');

    act(() => {
        getNumberProps().onChange(7);
    });
    expect(getNumberProps().value).toEqual(7);

    expect(smartContentStore.dataSource).toEqual(undefined);
    expect(smartContentStore.includeSubElements).toEqual(undefined);
    expect(smartContentStore.categories).toEqual(undefined);
    expect(smartContentStore.categoryOperator).toEqual(undefined);
    expect(smartContentStore.tags).toEqual(undefined);
    expect(smartContentStore.tagOperator).toEqual(undefined);
    expect(smartContentStore.audienceTargeting).toEqual(undefined);
    expect(smartContentStore.sortBy).toEqual(undefined);
    expect(smartContentStore.sortOrder).toEqual(undefined);
    expect(smartContentStore.presentation).toEqual(undefined);
    expect(smartContentStore.limit).toEqual(undefined);
    expect(smartContentStore.types).toEqual(undefined);

    act(() => {
        getOverlayProps().onConfirm();
    });

    expect(smartContentStore.dataSource).toEqual({id: 2, title: 'Test'});
    expect(smartContentStore.includeSubElements).toEqual(true);
    expect(smartContentStore.categories).toEqual([{id: 1, name: 'Test1'}, {id: 3, name: 'Test2'}]);
    expect(smartContentStore.categoryOperator).toEqual('and');
    expect(smartContentStore.tags).toEqual(['Test 1', 'Test 3']);
    expect(smartContentStore.tagOperator).toEqual('or');
    expect(smartContentStore.audienceTargeting).toEqual(false);
    expect(smartContentStore.sortBy).toEqual('changed');
    expect(smartContentStore.sortOrder).toEqual('asc');
    expect(smartContentStore.presentation).toEqual('large');
    expect(smartContentStore.limit).toEqual(7);
    expect(smartContentStore.types).toEqual(['default']);

    expect(closeSpy).toBeCalledWith();
});

test('Prefill all fields with correct values', () => {
    const smartContentStore = new SmartContentStore('content');
    smartContentStore.dataSource = {id: 4, title: 'Homepage'};
    smartContentStore.includeSubElements = true;
    smartContentStore.categories = [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}];
    smartContentStore.categoryOperator = 'or';
    smartContentStore.tags = [1, 2];
    smartContentStore.tagOperator = 'and';
    smartContentStore.audienceTargeting = true;
    smartContentStore.sortBy = 'created';
    smartContentStore.sortOrder = 'desc';
    smartContentStore.presentation = 'small';
    smartContentStore.limit = 8;
    smartContentStore.types = ['default', 'homepage'];
    const categoryOverlayMatcher = (props) => props.listKey === 'categories' && props.resourceKey === 'categories';

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'pages',
        dataSourceResourceKey: 'pages',
        presentations: {
            small: 'Small',
            large: 'Large',
        },
        sections: [
            'datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit', 'types',
        ],
        smartContentStore,
        sortings: [
            {name: 'title', value: 'Title'},
            {name: 'created', value: 'Created'},
        ],
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    expect(screen.getByText(/sulu_admin\.data_source:\s*Homepage/)).toBeInTheDocument();
    expect(getSingleListOverlayProps((props) => props.resourceKey === 'pages').preSelectedItem)
        .toEqual({id: 4, title: 'Homepage'});
    expect(getTogglerProps('sulu_admin.include_sub_elements').checked).toEqual(true);

    expect(screen.getByText(/sulu_category\.categories:\s*Test1,\s*Test3/)).toBeInTheDocument();
    expect(getMultiListOverlayProps(categoryOverlayMatcher).preSelectedItems)
        .toEqual([{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}]);
    expect(getSingleSelectPropsAt(0).value).toEqual('or');

    expect(MultiSelectionStore).toBeCalledWith('tags', [1, 2], undefined, 'names');
    expect(getSingleSelectPropsAt(1).value).toEqual('and');

    expect(getMultiSelectProps().values).toEqual(['default', 'homepage']);

    expect(getTogglerProps('sulu_admin.use_target_groups').checked).toEqual(true);

    expect(getSingleSelectPropsAt(2).value).toEqual('created');
    expect(getSingleSelectPropsAt(3).value).toEqual('desc');

    expect(getSingleSelectPropsAt(4).value).toEqual('small');
    expect(getNumberProps().value).toEqual(8);
});

test('Reset all fields when reset action is clicked', () => {
    const smartContentStore = new SmartContentStore('content');
    const filterOverlayRef = React.createRef();
    smartContentStore.dataSource = {id: 4, url: '/home'};
    smartContentStore.includeSubElements = true;
    smartContentStore.categories = [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}];
    smartContentStore.categoryOperator = 'or';
    smartContentStore.tags = ['Test5', 'Test7'];
    smartContentStore.tagOperator = 'and';
    smartContentStore.audienceTargeting = true;
    smartContentStore.sortBy = 'created';
    smartContentStore.sortOrder = 'desc';
    smartContentStore.presentation = 'large';
    smartContentStore.limit = 5;
    smartContentStore.types = ['default', 'homepage'];

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'pages',
        dataSourceResourceKey: 'pages',
        filterOverlayRef,
        presentations: {
            small: 'Small',
            large: 'Large',
        },
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
        sortings: [
            {name: 'title', value: 'Title'},
            {name: 'created', value: 'Created'},
        ],
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    act(() => {
        getOverlayProps().actions[0].onClick();
    });

    expect(getFilterOverlayInstance(filterOverlayRef).dataSource).toEqual(1);
    expect(getFilterOverlayInstance(filterOverlayRef).includeSubElements).toEqual(true);
    expect(getFilterOverlayInstance(filterOverlayRef).categories).toEqual([]);
    expect(getFilterOverlayInstance(filterOverlayRef).categoryOperator).toEqual('and');
    expect(getFilterOverlayInstance(filterOverlayRef).tags).toEqual([]);
    expect(getFilterOverlayInstance(filterOverlayRef).tagOperator).toEqual('or');
    expect(getFilterOverlayInstance(filterOverlayRef).audienceTargeting).toEqual(true);
    expect(getFilterOverlayInstance(filterOverlayRef).sortBy).toEqual('title');
    expect(getFilterOverlayInstance(filterOverlayRef).sortOrder).toEqual('asc');
    expect(getFilterOverlayInstance(filterOverlayRef).presentation).toEqual('two');
    expect(getFilterOverlayInstance(filterOverlayRef).limit).toEqual(5);
    expect(getFilterOverlayInstance(filterOverlayRef).types).toEqual([]);
});
