// @flow
import React from 'react';
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable} from 'mobx';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import SmartContentStore from '../stores/SmartContentStore';
import FilterOverlay from '../FilterOverlay';

const mockReact = require('react');
let mockMultiListOverlayProps: Array<Object> = [];
let mockSingleListOverlayProps: Array<Object> = [];
let mockMultiSelectionStoreInstances: Array<Object> = [];

jest.mock('../stores/SmartContentStore', () => jest.fn());

jest.mock('../../../utils/Translator');

jest.mock('../../../components/Overlay', () => jest.fn((props) => {
    if (!props.open) {
        return mockReact.createElement(
            'div',
            {
                'data-open': 'false',
                'data-testid': 'overlay',
            }
        );
    }

    return mockReact.createElement(
        'div',
        {
            'data-open': 'true',
            'data-testid': 'overlay',
        },
        mockReact.createElement('h2', {}, props.title),
        props.actions && props.actions.map((action, index) => mockReact.createElement(
            'button',
            {
                'aria-label': 'overlay-action-' + index,
                key: index,
                onClick: action.onClick,
                type: 'button',
            },
            action.title
        )),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'overlay-confirm',
                onClick: props.onConfirm,
                type: 'button',
            },
            props.confirmText
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'overlay-close',
                onClick: props.onClose,
                type: 'button',
            },
            'Close'
        ),
        props.children
    );
}));

jest.mock('../../../components/Toggler', () => jest.fn((props) => mockReact.createElement(
    'label',
    {},
    props.children,
    mockReact.createElement(
        'input',
        {
            checked: !!props.checked,
            onChange: (event) => props.onChange(event.currentTarget.checked),
            type: 'checkbox',
        }
    )
)));

jest.mock('../../../components/Number', () => jest.fn((props) => mockReact.createElement(
    'input',
    {
        'aria-label': 'number',
        onChange: (event) => props.onChange(
            event.currentTarget.value ? parseInt(event.currentTarget.value) : undefined
        ),
        type: 'number',
        value: props.value || '',
    }
)));

jest.mock('../../../components/SingleSelect', () => {
    function SingleSelectMock(props) {
        return mockReact.createElement(
            'select',
            {
                onChange: (event) => props.onChange(event.currentTarget.value || undefined),
                value: props.value || '',
            },
            mockReact.Children.map(props.children, (child) => mockReact.createElement(
                'option',
                {
                    value: child.props.value,
                },
                child.props.children
            ))
        );
    }

    SingleSelectMock.Option = function Option() {
        return null;
    };

    return SingleSelectMock;
});

jest.mock('../../../components/MultiSelect', () => {
    function MultiSelectMock(props) {
        return mockReact.createElement(
            'select',
            {
                multiple: true,
                onChange: (event) => props.onChange(
                    Array.from(event.currentTarget.selectedOptions).map((option) => option.value)
                ),
                value: Array.from(props.values || []),
            },
            mockReact.Children.map(props.children, (child) => mockReact.createElement(
                'option',
                {
                    value: child.props.value,
                },
                child.props.children
            ))
        );
    }

    MultiSelectMock.Option = function Option() {
        return null;
    };

    return MultiSelectMock;
});

jest.mock('../../../containers/MultiAutoComplete', () => jest.fn(() => mockReact.createElement(
    'div',
    {
        'data-testid': 'multi-auto-complete',
    }
)));

jest.mock('../../../containers/MultiListOverlay', () => jest.fn((props) => {
    mockMultiListOverlayProps.push(props);

    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-resource-key': props.resourceKey,
            'data-testid': 'multi-list-overlay',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'confirm-' + props.resourceKey,
                onClick: () => props.onConfirm([
                    {id: 1, name: 'Test1'},
                    {id: 3, name: 'Test2'},
                ]),
                type: 'button',
            },
            'Confirm'
        )
    );
}));

jest.mock('../../../containers/SingleListOverlay', () => jest.fn((props) => {
    mockSingleListOverlayProps.push(props);

    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-resource-key': props.resourceKey,
            'data-testid': 'single-list-overlay',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'confirm-' + props.resourceKey,
                onClick: () => props.onConfirm({id: 2, title: 'Test'}),
                type: 'button',
            },
            'Confirm'
        )
    );
}));

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function(resourceKey, value, locale, displayProperty) {
    this.resourceKey = resourceKey;
    this.value = value;
    this.locale = locale;
    this.displayProperty = displayProperty;

    mockExtendObservable(this, {
        items: [],
    });

    mockMultiSelectionStoreInstances.push(this);
}));

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

function renderFilterOverlay(props: Object = {}) {
    const smartContentStore = props.smartContentStore || new SmartContentStore('content');

    return {
        smartContentStore,
        ...render(
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
                {...props}
            />
        ),
    };
}

function getSection(title: string): HTMLElement {
    const heading = screen.getByRole('heading', {name: title});
    const section = heading.closest('section');

    if (!(section instanceof HTMLElement)) {
        throw new Error('Section "' + title + '" was not rendered.');
    }

    return section;
}

function getLatestSingleListOverlayProps(resourceKey: string): Object {
    const props = mockSingleListOverlayProps.filter((props) => props.resourceKey === resourceKey).pop();

    if (!props) {
        throw new Error('SingleListOverlay "' + resourceKey + '" was not rendered.');
    }

    return props;
}

function getLatestMultiListOverlayProps(resourceKey: string): Object {
    const props = mockMultiListOverlayProps.filter((props) => props.resourceKey === resourceKey).pop();

    if (!props) {
        throw new Error('MultiListOverlay "' + resourceKey + '" was not rendered.');
    }

    return props;
}

function getSelectedValues(select: HTMLSelectElement): Array<string> {
    return Array.from(select.selectedOptions).map((option) => option.value);
}

beforeEach(() => {
    jest.clearAllMocks();
    mockMultiListOverlayProps = [];
    mockSingleListOverlayProps = [];
    mockMultiSelectionStoreInstances = [];
});

test('Do not display if open is set to false', () => {
    renderFilterOverlay({open: false});

    expect(screen.getByTestId('overlay')).toHaveAttribute('data-open', 'false');
});

test('Pass rootKey for categories to options for category list', () => {
    const smartContentStore = new SmartContentStore('content');
    (smartContentStore: any).loading = false;

    renderFilterOverlay({
        categoryRootKey: 'test1',
        sections: ['categories'],
        smartContentStore,
    });

    expect(getLatestMultiListOverlayProps('categories').options).toEqual({rootKey: 'test1'});
});

test('Render with ListOverlays if smartContentStore is loaded', () => {
    const smartContentStore = new SmartContentStore('content');
    (smartContentStore: any).loading = false;

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
    });

    expect(screen.getByTestId('single-list-overlay')).toBeInTheDocument();
    expect(screen.getByTestId('multi-list-overlay')).toBeInTheDocument();
});

test('Render without ListOverlays if smartContentStore is not loaded', () => {
    const smartContentStore = new SmartContentStore('content');
    (smartContentStore: any).loading = true;

    renderFilterOverlay({
        dataSourceAdapter: 'table',
        dataSourceListKey: 'snippets',
        dataSourceResourceKey: 'snippets',
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
        smartContentStore,
    });

    expect(screen.queryByTestId('single-list-overlay')).not.toBeInTheDocument();
    expect(screen.queryByTestId('multi-list-overlay')).not.toBeInTheDocument();
});

test('Render with all fields', () => {
    renderFilterOverlay({
        sections: ['datasource', 'categories', 'tags', 'audienceTargeting', 'sorting', 'presentation', 'limit'],
    });

    expect(screen.getByRole('heading', {name: 'sulu_admin.data_source'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.filter_by_categories'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.filter_by_tags'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.target_groups'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.sort_by'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.present_as'})).toBeInTheDocument();
    expect(screen.getByRole('heading', {name: 'sulu_admin.limit_result_to'})).toBeInTheDocument();
});

test('Render with no fields', () => {
    renderFilterOverlay();

    expect(screen.getByRole('heading', {name: 'Test'})).toBeInTheDocument();
    expect(screen.queryByRole('heading', {name: 'sulu_admin.data_source'})).not.toBeInTheDocument();
    expect(screen.queryByRole('heading', {name: 'sulu_admin.filter_by_categories'})).not.toBeInTheDocument();
});

test('Fill all fields using and update SmartContentStore on confirm', async() => {
    const user = userEvent.setup();
    const smartContentStore = new SmartContentStore('content');
    const closeSpy = jest.fn();

    smartContentStore.audienceTargeting = true;

    renderFilterOverlay({
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

    await user.click(screen.getByRole('button', {name: 'sulu_admin.choose_data_source'}));
    expect(screen.getByTestId('single-list-overlay')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('confirm-pages'));
    expect(screen.getByTestId('single-list-overlay')).toHaveAttribute('data-open', 'false');
    expect(screen.getByText('sulu_admin.data_source: Test')).toBeInTheDocument();

    await user.click(within(getSection('sulu_admin.data_source')).getByRole('checkbox'));
    expect(within(getSection('sulu_admin.data_source')).getByRole('checkbox')).toBeChecked();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.choose_categories'}));
    expect(screen.getByTestId('multi-list-overlay')).toHaveAttribute('data-open', 'true');
    await user.click(screen.getByLabelText('confirm-categories'));
    expect(screen.getByTestId('multi-list-overlay')).toHaveAttribute('data-open', 'false');
    expect(screen.getByText('sulu_category.categories: Test1, Test2')).toBeInTheDocument();

    await user.selectOptions(within(getSection('sulu_admin.filter_by_categories')).getByRole('combobox'), 'and');

    act(() => {
        mockMultiSelectionStoreInstances[0].items.push({id: 1, name: 'Test 1'}, {id: 2, name: 'Test 3'});
    });

    await user.selectOptions(within(getSection('sulu_admin.filter_by_tags')).getByRole('combobox'), 'or');
    await user.selectOptions(within(getSection('sulu_admin.filter_by_types')).getByRole('listbox'), ['default']);

    await user.click(within(getSection('sulu_admin.target_groups')).getByRole('checkbox'));
    expect(within(getSection('sulu_admin.target_groups')).getByRole('checkbox')).not.toBeChecked();

    const sortingSelects = within(getSection('sulu_admin.sort_by')).getAllByRole('combobox');
    await user.selectOptions(sortingSelects[0], 'changed');
    await user.selectOptions(sortingSelects[1], 'asc');
    await user.selectOptions(within(getSection('sulu_admin.present_as')).getByRole('combobox'), 'large');
    await user.clear(within(getSection('sulu_admin.limit_result_to')).getByLabelText('number'));
    await user.type(within(getSection('sulu_admin.limit_result_to')).getByLabelText('number'), '7');

    expect(smartContentStore.dataSource).toEqual(undefined);
    expect(smartContentStore.includeSubElements).toEqual(undefined);
    expect(smartContentStore.categories).toEqual(undefined);
    expect(smartContentStore.categoryOperator).toEqual(undefined);
    expect(smartContentStore.tags).toEqual(undefined);
    expect(smartContentStore.tagOperator).toEqual(undefined);
    expect(smartContentStore.audienceTargeting).toEqual(true);
    expect(smartContentStore.sortBy).toEqual(undefined);
    expect(smartContentStore.sortOrder).toEqual(undefined);
    expect(smartContentStore.presentation).toEqual(undefined);
    expect(smartContentStore.limit).toEqual(undefined);
    expect(smartContentStore.types).toEqual(undefined);

    await user.click(screen.getByLabelText('overlay-confirm'));

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

    expect(closeSpy).toHaveBeenCalledWith();
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

    expect(screen.getByText('sulu_admin.data_source: Homepage')).toBeInTheDocument();
    expect(getLatestSingleListOverlayProps('pages').preSelectedItem).toEqual({id: 4, title: 'Homepage'});
    expect(within(getSection('sulu_admin.data_source')).getByRole('checkbox')).toBeChecked();

    expect(screen.getByText('sulu_category.categories: Test1, Test3')).toBeInTheDocument();
    expect(getLatestMultiListOverlayProps('categories').preSelectedItems)
        .toEqual([{id: 1, name: 'Test1'}, {id: 5, name: 'Test3'}]);
    expect(within(getSection('sulu_admin.filter_by_categories')).getByRole('combobox')).toHaveValue('or');

    expect(MultiSelectionStore).toHaveBeenCalledWith('tags', [1, 2], undefined, 'names');
    expect(within(getSection('sulu_admin.filter_by_tags')).getByRole('combobox')).toHaveValue('and');
    expect(getSelectedValues((within(getSection('sulu_admin.filter_by_types')).getByRole('listbox'): any)))
        .toEqual(['default', 'homepage']);

    expect(within(getSection('sulu_admin.target_groups')).getByRole('checkbox')).toBeChecked();

    const sortingSelects = within(getSection('sulu_admin.sort_by')).getAllByRole('combobox');
    expect(sortingSelects[0]).toHaveValue('created');
    expect(sortingSelects[1]).toHaveValue('desc');

    expect(within(getSection('sulu_admin.present_as')).getByRole('combobox')).toHaveValue('small');
    expect(within(getSection('sulu_admin.limit_result_to')).getByLabelText('number')).toHaveValue(8);
});

test('Reset all fields when reset action is clicked', async() => {
    const user = userEvent.setup();
    const smartContentStore = new SmartContentStore('content');

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
        presentations: {
            small: 'Small',
            large: 'Large',
            two: 'Two',
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

    await user.click(screen.getByLabelText('overlay-action-0'));
    await user.click(screen.getByLabelText('overlay-confirm'));

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
