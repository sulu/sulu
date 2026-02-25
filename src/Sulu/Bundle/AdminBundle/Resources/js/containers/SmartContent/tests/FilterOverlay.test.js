// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import Button from '../../../components/Button';
import Number from '../../../components/Number';
import Overlay from '../../../components/Overlay';
import MultiSelect from '../../../components/MultiSelect';
import SingleSelect from '../../../components/SingleSelect';
import Toggler from '../../../components/Toggler';
import MultiAutoComplete from '../../../containers/MultiAutoComplete';
import MultiListOverlay from '../../../containers/MultiListOverlay';
import SingleListOverlay from '../../../containers/SingleListOverlay';
import SmartContentStore from '../stores/SmartContentStore';
import FilterOverlay from '../FilterOverlay';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import findMockCallArg from '../../../utils/TestHelper/findMockCallArg';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../stores/SmartContentStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/Button', () => {
    const ButtonMock: any = jest.fn(function ButtonMock({children, onClick}: any) {
        return (
            <button onClick={onClick} type="button">
                {children}
            </button>
        );
    });

    return ButtonMock;
});

jest.mock('../../../components/Toggler', () => {
    const TogglerMock: any = jest.fn(function TogglerMock({checked, children}: any) {
        return <div data-checked={checked}>{children}</div>;
    });

    return TogglerMock;
});

jest.mock('../../../components/Number', () => {
    const NumberMock: any = jest.fn(function NumberMock() {
        return <div data-testid="number" />;
    });

    return NumberMock;
});

jest.mock('../../../components/SingleSelect', () => {
    const SingleSelectMock: any = jest.fn(function SingleSelectMock() {
        return <div data-testid="single-select" />;
    });

    SingleSelectMock.Option = function OptionMock({children}: any) {
        return <>{children}</>;
    };

    return SingleSelectMock;
});

jest.mock('../../../components/MultiSelect', () => {
    const MultiSelectMock: any = jest.fn(function MultiSelectMock() {
        return <div data-testid="multi-select" />;
    });

    MultiSelectMock.Option = function OptionMock({children}: any) {
        return <>{children}</>;
    };

    return MultiSelectMock;
});

jest.mock('../../../components/Overlay', () => {
    const OverlayMock: any = jest.fn(function OverlayMock({children}: any) {
        return <div data-testid="overlay">{children}</div>;
    });

    return OverlayMock;
});

jest.mock('../../../containers/MultiAutoComplete', () => jest.fn(() => null));
jest.mock('../../../containers/MultiListOverlay', () => jest.fn(() => null));
jest.mock('../../../containers/SingleListOverlay', () => jest.fn(() => null));
jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function() {
    mockExtendObservable(this, {
        items: [],
    });
}));

const ButtonMock: any = Button;
const TogglerMock: any = Toggler;
const NumberMock: any = Number;
const SingleSelectMock: any = SingleSelect;
const MultiSelectMock: any = MultiSelect;
const OverlayMock: any = Overlay;
const MultiAutoCompleteMock: any = MultiAutoComplete;
const MultiListOverlayMock: any = MultiListOverlay;
const SingleListOverlayMock: any = SingleListOverlay;
const MultiSelectionStoreMock: any = MultiSelectionStore;

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

function createSmartContentStore(initialValues: Object = {}) {
    const smartContentStore: any = new SmartContentStore('content');
    Object.assign(smartContentStore, initialValues);

    return smartContentStore;
}

function renderFilterOverlay(smartContentStore: any, overrides: Object = {}) {
    return render(
        <FilterOverlay
            categoryRootKey={undefined}
            dataSourceAdapter={undefined}
            dataSourceListKey={undefined}
            dataSourceResourceKey={undefined}
            defaultValue={defaultValue}
            onClose={jest.fn()}
            open={true}
            presentations={{}}
            sections={[]}
            smartContentStore={smartContentStore}
            sortings={[]}
            title="Test"
            types={[]}
            {...overrides}
        />
    );
}

function getOverlayProps() {
    return getLatestMockProps(OverlayMock);
}

function getButtonProps(buttonText: string) {
    return findMockCallArg(ButtonMock, ([props]) => props.children === buttonText);
}

function getTogglerProps(toggleText: string) {
    return findMockCallArg(TogglerMock, ([props]) => props.children === toggleText);
}

function getSingleListOverlayProps(resourceKey: string) {
    return findMockCallArg(SingleListOverlayMock, ([props]) => props.resourceKey === resourceKey);
}

function getMultiListOverlayProps(resourceKey: string) {
    return findMockCallArg(MultiListOverlayMock, ([props]) => props.resourceKey === resourceKey);
}

function getSingleSelectPropsByOptionText(optionText: string) {
    return findMockCallArg(SingleSelectMock, ([props]) =>
        React.Children.toArray(props.children)
            .some((option: any) => option.props && option.props.children === optionText)
    );
}

function getSingleSelectPropsByOptionValue(optionValue: string) {
    return findMockCallArg(SingleSelectMock, ([props]) =>
        React.Children.toArray(props.children)
            .some((option: any) => option.props && option.props.value === optionValue)
    );
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Do not display if open is set to false', () => {
    const smartContentStore = createSmartContentStore();

    renderFilterOverlay(smartContentStore, {
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        open: false,
    });

    expect(getOverlayProps().open).toEqual(false);
});

test('Pass rootKey for categories to options for category list', () => {
    const smartContentStore = createSmartContentStore({loading: false});

    renderFilterOverlay(smartContentStore, {
        categoryRootKey: 'test1',
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        open: false,
        sections: ['categories'],
    });

    expect(getMultiListOverlayProps('categories').options).toEqual({rootKey: 'test1'});
});

test('Render with ListOverlays if smartContentStore is loaded', () => {
    const smartContentStore = createSmartContentStore({loading: false});

    renderFilterOverlay(smartContentStore, {
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
    });

    expect(SingleListOverlayMock).toHaveBeenCalledTimes(1);
    expect(MultiListOverlayMock).toHaveBeenCalledTimes(1);
});

test('Render without ListOverlays if smartContentStore is not loaded', () => {
    const smartContentStore = createSmartContentStore({loading: true});

    renderFilterOverlay(smartContentStore, {
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
    });

    expect(SingleListOverlayMock).not.toBeCalled();
    expect(MultiListOverlayMock).not.toBeCalled();
});

test('Render with all fields', () => {
    const smartContentStore = createSmartContentStore();

    const {asFragment} = renderFilterOverlay(smartContentStore, {
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Render with no fields', () => {
    const smartContentStore = createSmartContentStore();

    const {asFragment} = renderFilterOverlay(smartContentStore, {
        sections: [],
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Fill all fields using and update SmartContentStore on confirm', () => {
    const smartContentStore = createSmartContentStore();
    const closeSpy = jest.fn();

    renderFilterOverlay(smartContentStore, {
        dataSourceAdapter: 'table',
        dataSourceListKey: 'pages_list',
        dataSourceResourceKey: 'pages',
        onClose: closeSpy,
        presentations: {
            small: 'Small',
            large: 'Large',
        },
        sections: [
            'datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit', 'types',
        ],
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

    expect(getSingleListOverlayProps('pages').open).toEqual(true);

    act(() => {
        getSingleListOverlayProps('pages').onConfirm({id: 2, title: 'Test'});
    });

    expect(getSingleListOverlayProps('pages').open).toEqual(false);
    expect(screen.getByText('sulu_admin.data_source: Test')).toBeInTheDocument();

    act(() => {
        getTogglerProps('sulu_admin.include_sub_elements').onChange(true);
    });
    expect(getTogglerProps('sulu_admin.include_sub_elements').checked).toEqual(true);

    act(() => {
        getButtonProps('sulu_admin.choose_categories').onClick();
    });

    expect(getMultiListOverlayProps('categories').open).toEqual(true);

    act(() => {
        getMultiListOverlayProps('categories').onConfirm([
            {id: 1, name: 'Test1'},
            {id: 3, name: 'Test2'},
        ]);
    });

    expect(getMultiListOverlayProps('categories').open).toEqual(false);
    expect(screen.getByText('sulu_category.categories: Test1, Test2')).toBeInTheDocument();

    act(() => {
        getSingleSelectPropsByOptionText('sulu_admin.any_category_description').onChange('and');
    });
    expect(getSingleSelectPropsByOptionText('sulu_admin.any_category_description').value).toEqual('and');

    const tagSelectionStore = getLatestMockProps(MultiAutoCompleteMock).selectionStore;
    act(() => {
        tagSelectionStore.items.push({id: 1, name: 'Test 1'}, {id: 2, name: 'Test 3'});
    });

    act(() => {
        getSingleSelectPropsByOptionText('sulu_admin.any_tag_description').onChange('or');
    });
    expect(getSingleSelectPropsByOptionText('sulu_admin.any_tag_description').value).toEqual('or');

    act(() => {
        getLatestMockProps(MultiSelectMock).onChange(['default']);
    });
    expect(getLatestMockProps(MultiSelectMock).values).toEqual(['default']);

    act(() => {
        getTogglerProps('sulu_admin.use_target_groups').onChange(false);
    });
    expect(getTogglerProps('sulu_admin.use_target_groups').checked).toEqual(false);

    act(() => {
        getSingleSelectPropsByOptionValue('changed').onChange('changed');
    });
    expect(getSingleSelectPropsByOptionValue('changed').value).toEqual('changed');

    act(() => {
        getSingleSelectPropsByOptionValue('asc').onChange('asc');
    });
    expect(getSingleSelectPropsByOptionValue('asc').value).toEqual('asc');

    act(() => {
        getSingleSelectPropsByOptionValue('small').onChange('large');
    });
    expect(getSingleSelectPropsByOptionValue('small').value).toEqual('large');

    act(() => {
        getLatestMockProps(NumberMock).onChange(7);
    });
    expect(getLatestMockProps(NumberMock).value).toEqual(7);

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
    const smartContentStore = createSmartContentStore({
        dataSource: {id: 4, title: 'Homepage'},
        includeSubElements: true,
        categories: [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}],
        categoryOperator: 'or',
        tags: [1, 2],
        tagOperator: 'and',
        audienceTargeting: true,
        sortBy: 'created',
        sortOrder: 'desc',
        presentation: 'small',
        limit: 8,
        types: ['default', 'homepage'],
    });

    renderFilterOverlay(smartContentStore, {
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
        sortings: [
            {name: 'title', value: 'Title'},
            {name: 'created', value: 'Created'},
        ],
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    });

    expect(screen.getByText('sulu_admin.data_source: Homepage')).toBeInTheDocument();
    expect(getSingleListOverlayProps('pages').preSelectedItem).toEqual({id: 4, title: 'Homepage'});
    expect(getTogglerProps('sulu_admin.include_sub_elements').checked).toEqual(true);

    expect(screen.getByText('sulu_category.categories: Test1, Test3')).toBeInTheDocument();
    expect(getMultiListOverlayProps('categories').preSelectedItems)
        .toEqual([{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}]);
    expect(getSingleSelectPropsByOptionText('sulu_admin.any_category_description').value).toEqual('or');

    expect(MultiSelectionStoreMock).toBeCalledWith('tags', [1, 2], undefined, 'names');
    expect(getSingleSelectPropsByOptionText('sulu_admin.any_tag_description').value).toEqual('and');

    expect(getLatestMockProps(MultiSelectMock).values).toEqual(['default', 'homepage']);

    expect(getTogglerProps('sulu_admin.use_target_groups').checked).toEqual(true);

    expect(getSingleSelectPropsByOptionValue('created').value).toEqual('created');
    expect(getSingleSelectPropsByOptionValue('asc').value).toEqual('desc');

    expect(getSingleSelectPropsByOptionValue('small').value).toEqual('small');
    expect(getLatestMockProps(NumberMock).value).toEqual(8);
});

test('Reset all fields when reset action is clicked', () => {
    const smartContentStore = createSmartContentStore({
        dataSource: {id: 4, url: '/home'},
        includeSubElements: true,
        categories: [{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}],
        categoryOperator: 'or',
        tags: ['Test5', 'Test7'],
        tagOperator: 'and',
        audienceTargeting: true,
        sortBy: 'created',
        sortOrder: 'desc',
        presentation: 'large',
        limit: 5,
        types: ['default', 'homepage'],
    });

    renderFilterOverlay(smartContentStore, {
        dataSourceAdapter: 'table',
        dataSourceListKey: 'pages',
        dataSourceResourceKey: 'pages',
        presentations: {
            small: 'Small',
            large: 'Large',
        },
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
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

    act(() => {
        getOverlayProps().onConfirm();
    });

    expect(smartContentStore.dataSource).toEqual(1);
    expect(smartContentStore.includeSubElements).toEqual(true);
    expect(smartContentStore.categories).toEqual([]);
    expect(smartContentStore.categoryOperator).toEqual('and');
    expect(smartContentStore.tags).toEqual([]);
    expect(smartContentStore.tagOperator).toEqual('or');
    expect(smartContentStore.audienceTargeting).toEqual(true);
    expect(smartContentStore.sortBy).toEqual('title');
    expect(smartContentStore.sortOrder).toEqual('asc');
    expect(smartContentStore.presentation).toEqual('two');
    expect(smartContentStore.limit).toEqual(5);
    expect(smartContentStore.types).toEqual([]);
});
