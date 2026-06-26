// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiSelection from '../MultiSelection';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';

let mockMultiListOverlayProps: Object = {};
let mockMultiItemSelectionProps: Object = {};
let mockMultiItemSelectionItemProps: Array<Object> = [];
let mockMultiSelectionStoreInstances: Array<Object> = [];
let mockPublishIndicatorProps: Array<Object> = [];
let mockOverlaySelectedItems: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../utils/Translator');

jest.mock('../../../components/CroppedText', () => jest.fn((props) => (
    mockReact.createElement('span', {}, props.children)
)));

jest.mock('../../../components/PublishIndicator', () => jest.fn((props) => {
    mockPublishIndicatorProps.push(props);

    return mockReact.createElement(
        'div',
        {
            'data-draft': props.draft ? 'true' : 'false',
            'data-published': props.published ? 'true' : 'false',
            'data-testid': 'publish-indicator',
        }
    );
}));

jest.mock('../../../components/MultiItemSelection', () => {
    const MultiItemSelectionMock: any = jest.fn((props) => {
        mockMultiItemSelectionProps = props;

        const children = mockReact.Children.map(props.children, (child) => (
            mockReact.cloneElement(child, {
                onClick: props.onItemClick,
                onRemove: props.onItemRemove,
                sortable: props.sortable,
            })
        ));

        return mockReact.createElement(
            'div',
            {
                'data-disabled': props.disabled ? 'true' : 'false',
                'data-testid': 'multi-item-selection',
            },
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'left-button',
                    disabled: props.disabled,
                    onClick: props.leftButton.onClick,
                    type: 'button',
                },
                props.leftButton.icon
            ),
            props.label && mockReact.createElement('span', {}, props.label),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'sort',
                    onClick: () => props.onItemsSorted(1, 2),
                    type: 'button',
                },
                'Sort'
            ),
            children
        );
    });

    MultiItemSelectionMock.Item = jest.fn((props) => {
        mockMultiItemSelectionItemProps.push(props);

        return mockReact.createElement(
            'div',
            {
                'data-allow-remove-while-disabled': props.allowRemoveWhileDisabled ? 'true' : 'false',
                'data-disabled': props.disabled ? 'true' : 'false',
                'data-testid': 'multi-item',
            },
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'item-' + props.id,
                    onClick: () => props.onClick && props.onClick(props.id, props.value),
                    type: 'button',
                },
                props.id
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'remove-' + props.id,
                    onClick: () => props.onRemove && props.onRemove(props.id),
                    type: 'button',
                },
                'Remove'
            ),
            props.children
        );
    });

    return MultiItemSelectionMock;
});

jest.mock('../../MultiListOverlay', () => jest.fn((props) => {
    mockMultiListOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'multi-list-overlay',
        },
        mockReact.createElement(
            'button',
            {
                'aria-label': 'overlay-close',
                onClick: props.onClose,
                type: 'button',
            },
            'Close'
        ),
        mockReact.createElement(
            'button',
            {
                'aria-label': 'overlay-confirm',
                onClick: () => props.onConfirm(mockOverlaySelectedItems),
                type: 'button',
            },
            'Confirm'
        )
    );
}));

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn());

beforeEach(() => {
    jest.clearAllMocks();
    mockMultiListOverlayProps = {};
    mockMultiItemSelectionProps = {};
    mockMultiItemSelectionItemProps = [];
    mockMultiSelectionStoreInstances = [];
    mockPublishIndicatorProps = [];
    mockOverlaySelectedItems = [{id: 3}, {id: 7}, {id: 2}];

    (MultiSelectionStore: any).mockImplementation(function(resourceKey, value, locale, idFilterParameter, options) {
        createMultiSelectionStoreMock(this, resourceKey, value, locale, idFilterParameter, options, []);
    });
});

function createMultiSelectionStoreMock(
    store,
    resourceKey,
    value,
    locale,
    idFilterParameter,
    options,
    items
) {
    store.resourceKey = resourceKey;
    store.value = value;
    store.locale = locale;
    store.idFilterParameter = idFilterParameter;
    store.options = options;
    store.set = jest.fn((items) => {
        store.items = items;
    });
    store.move = jest.fn();
    store.removeById = jest.fn();
    store.loadItems = jest.fn();
    store.setRequestParameters = jest.fn();

    mockExtendObservable(store, {
        items,
        loading: false,
    });

    mockMultiSelectionStoreInstances.push(store);
}

function mockMultiSelectionStore(items: Array<Object>) {
    (MultiSelectionStore: any).mockImplementationOnce(function(resourceKey, value, locale, idFilterParameter, options) {
        createMultiSelectionStoreMock(this, resourceKey, value, locale, idFilterParameter, options, items);
    });
}

function renderMultiSelection(props: Object = {}) {
    return render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            {...props}
        />
    );
}

function getSelectionStore() {
    return mockMultiSelectionStoreInstances[0];
}

function setStoreItems(items: Array<Object>) {
    act(() => {
        (getSelectionStore(): any).items = items;
    });
}

function getLatestItemProps(id) {
    const itemProps = mockMultiItemSelectionItemProps.filter((props) => props.id === id);

    return itemProps[itemProps.length - 1];
}

test('Show with passed icon and label and open overlay', async() => {
    mockMultiSelectionStore([{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2'}, {id: 5, title: 'Title 5'}]);

    renderMultiSelection({
        displayProperties: ['id', 'title'],
        icon: 'su-document',
        label: 'Select Snippets',
    });

    await userEvent.click(screen.getByLabelText('left-button'));

    expect(screen.getByText('Select Snippets')).toBeInTheDocument();
    expect(screen.getByText('Title 1')).toBeInTheDocument();
    expect(screen.getByText('Title 2')).toBeInTheDocument();
    expect(screen.getByText('Title 5')).toBeInTheDocument();
    expect(mockMultiListOverlayProps.open).toEqual(true);
});

test('Pass correct props to MultiItemSelection component', () => {
    renderMultiSelection({
        disabled: true,
        overlayTitle: 'Selection',
        sortable: false,
    });

    expect(mockMultiItemSelectionProps.disabled).toEqual(true);
    expect(mockMultiItemSelectionProps.sortable).toEqual(false);
});

test('Render with disabled item', () => {
    mockMultiSelectionStore([
        {id: 1},
        {id: 2},
    ]);

    renderMultiSelection({
        disabledIds: [2, 4],
        resourceKey: 'pages',
        value: [1, 2],
    });

    expect(getLatestItemProps(1).disabled).toEqual(false);
    expect(getLatestItemProps(2).disabled).toEqual(true);
});

test('Pass locale to MultiListOverlay', () => {
    const locale = observable.box('de');

    renderMultiSelection({locale});

    expect(mockMultiListOverlayProps.locale.get()).toEqual('de');
});

test('Pass options to MultiListOverlay', () => {
    renderMultiSelection({options: {types: 'test'}});

    expect(mockMultiListOverlayProps.options).toEqual({types: 'test'});
});

test('Pass disabledIds to MultiListOverlay', () => {
    const disabledIds = [1, 2, 4];

    renderMultiSelection({disabledIds});

    expect(mockMultiListOverlayProps.disabledIds).toEqual(disabledIds);
});

test('Pass itemDisabledCondition to MultiListOverlay', () => {
    renderMultiSelection({itemDisabledCondition: 'status == "inactive"'});

    expect(mockMultiListOverlayProps.itemDisabledCondition).toEqual('status == "inactive"');
});

test('Construct MultiSelectionStore with correct parameters', () => {
    const locale = observable.box('en');

    renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [1, 2, 5],
    });

    expect(MultiSelectionStore).toHaveBeenCalledWith('snippets', [1, 2, 5], locale, 'ids', {key: 'value-1'});
});

test('Update requestParameters and reload items of MultiSelectionStore when options prop is changed', async() => {
    const locale = observable.box('en');

    const {rerender} = renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [1, 2, 5],
    });

    expect((getSelectionStore(): any).setRequestParameters).not.toHaveBeenCalled();
    expect((getSelectionStore(): any).loadItems).not.toHaveBeenCalled();

    rerender(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={{key: 'value-2'}}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1, 2, 5]}
        />
    );

    await waitFor(() => expect((getSelectionStore(): any).setRequestParameters).toHaveBeenCalledWith({key: 'value-2'}));
    expect((getSelectionStore(): any).loadItems).toHaveBeenCalledWith([1, 2, 5]);
});

test('Not reload items of MultiSelectionStore when new value of option prop is equal to old value', () => {
    const locale = observable.box('en');

    const {rerender} = renderMultiSelection({
        displayProperties: ['id', 'title'],
        locale,
        options: {key: 'value-1'},
        value: [],
    });

    expect((getSelectionStore(): any).setRequestParameters).not.toHaveBeenCalled();
    expect((getSelectionStore(): any).loadItems).not.toHaveBeenCalled();

    rerender(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={{key: 'value-1'}}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[]}
        />
    );

    expect((getSelectionStore(): any).setRequestParameters).not.toHaveBeenCalled();
    expect((getSelectionStore(): any).loadItems).not.toHaveBeenCalled();
});

test('Should not open an overlay on icon-click when disabled', async() => {
    renderMultiSelection({disabled: true});

    await userEvent.click(screen.getByLabelText('left-button'));

    expect(mockMultiListOverlayProps.open).toEqual(false);
});

test('Should close an overlay using the close button', async() => {
    renderMultiSelection();

    await userEvent.click(screen.getByLabelText('left-button'));
    await waitFor(() => expect(mockMultiListOverlayProps.open).toEqual(true));

    await userEvent.click(screen.getByLabelText('overlay-close'));

    expect(mockMultiListOverlayProps.open).toEqual(false);
});

test('Should close an overlay using the confirm button', async() => {
    mockOverlaySelectedItems = [{id: 1}];

    renderMultiSelection();

    await userEvent.click(screen.getByLabelText('left-button'));
    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect(mockMultiListOverlayProps.open).toEqual(false);
});

test('Should call the onChange callback when clicking the confirm button', async() => {
    const changeSpy = jest.fn();

    renderMultiSelection({onChange: changeSpy});

    await userEvent.click(screen.getByLabelText('left-button'));
    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect((getSelectionStore(): any).set).toHaveBeenCalledWith([{id: 3}, {id: 7}, {id: 2}]);
    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith([3, 7, 2]));
});

test('Should not call the onChange callback when items have not changed', () => {
    const changeSpy = jest.fn();
    mockMultiSelectionStore([{id: 1}]);

    renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should call the onItemClick callback when an item was clicked', async() => {
    const itemClickSpy = jest.fn();
    const item1 = {id: 1, title: 'Title 1'};
    const item2 = {id: 2, title: 'Title 2'};
    mockMultiSelectionStore([item1, item2]);

    renderMultiSelection({
        displayProperties: ['id', 'title'],
        icon: 'su-document',
        label: 'Select Snippets',
        onItemClick: itemClickSpy,
    });

    await userEvent.click(screen.getByLabelText('item-1'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, item1);

    await userEvent.click(screen.getByLabelText('item-2'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(2, item2);
});

test('Should load the items if value prop changes', () => {
    const {rerender} = renderMultiSelection({value: [1]});

    rerender(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1, 3]}
        />
    );

    expect((getSelectionStore(): any).loadItems).toHaveBeenCalledWith([1, 3]);
});

test('Pass listKey and resourceKey to MultiListOverlay', async() => {
    renderMultiSelection({
        listKey: 'pages_list',
        resourceKey: 'pages',
    });

    await userEvent.click(screen.getByLabelText('left-button'));

    expect(mockMultiListOverlayProps.listKey).toEqual('pages_list');
    expect(mockMultiListOverlayProps.resourceKey).toEqual('pages');
});

test('Pass preselected items to MultiListOverlay', async() => {
    const items = [{id: 1}, {id: 5}, {id: 8}];
    mockMultiSelectionStore(items);

    renderMultiSelection({
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    await userEvent.click(screen.getByLabelText('left-button'));

    expect(mockMultiListOverlayProps.preSelectedItems).toEqual(items);
});

test('Should reload items when new props are received', () => {
    const locale = observable.box('en');
    mockMultiSelectionStore([{id: 1}, {id: 5}, {id: 8}]);

    const {rerender} = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    rerender(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 3]}
        />
    );

    expect((getSelectionStore(): any).loadItems.mock.calls[0][0]).toEqual([1, 3]);
});

test('Should not reload items if none of the items changed', () => {
    const locale = observable.box('en');
    mockMultiSelectionStore([{id: 1}, {id: 5}, {id: 8}]);

    const {rerender} = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    rerender(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    expect((getSelectionStore(): any).loadItems).not.toHaveBeenCalled();
});

test('Should not reinstantiate the ListStore with the preselected ids when new props have the same values', () => {
    const locale = observable.box('en');
    mockMultiSelectionStore([{id: 1}, {id: 5}, {id: 8}]);

    const {rerender} = renderMultiSelection({
        locale,
        resourceKey: 'pages',
        value: [1, 5, 8],
    });

    rerender(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    expect((getSelectionStore(): any).loadItems).not.toHaveBeenCalled();
});

test('Should remove an item when the remove button is clicked', async() => {
    mockMultiSelectionStore([{id: 3}, {id: 7}, {id: 9}]);

    renderMultiSelection({value: [3, 7, 9]});

    await userEvent.click(screen.getByLabelText('remove-7'));

    expect((getSelectionStore(): any).removeById).toHaveBeenCalledWith(7);
});

test('Should reorder the items on drag and drop', async() => {
    renderMultiSelection({value: [3, 7, 9]});

    await userEvent.click(screen.getByLabelText('sort'));

    expect((getSelectionStore(): any).move).toHaveBeenCalledWith(1, 2);
});

test('Should call the onChange callback if the value of the selection-store changes', async() => {
    const changeSpy = jest.fn();

    renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    setStoreItems([{id: 22}, {id: 23}]);

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith([22, 23]));
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    rerender(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={changeSpy}
            overlayTitle="New Selection Title"
            resourceKey="snippets"
            value={[1]}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    renderMultiSelection({
        onChange: changeSpy,
        value: [1],
    });

    setStoreItems([{id: 22}, {id: 23}]);
    expect(changeSpy).toHaveBeenCalledWith([22, 23]);
    expect(changeSpy).toHaveBeenCalledTimes(1);

    act(() => {
        unrelatedObservable.set(55);
    });

    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should render selected item in disabled state if it fulfills passed itemDisabledCondition', () => {
    mockMultiSelectionStore([
        {id: 1, status: 'active'},
        {id: 2, status: 'inactive'},
    ]);

    renderMultiSelection({
        itemDisabledCondition: 'status == "inactive"',
        resourceKey: 'pages',
        value: [1, 2],
    });

    expect(getLatestItemProps(1).disabled).toEqual(false);
    expect(getLatestItemProps(2).disabled).toEqual(true);
});

test('Should render selected item in disabled state if passed disabledIds contain id of item', () => {
    mockMultiSelectionStore([
        {id: 1},
        {id: 2},
    ]);

    renderMultiSelection({
        disabledIds: [2, 4],
        resourceKey: 'pages',
        value: [1, 2],
    });

    expect(getLatestItemProps(1).disabled).toEqual(false);
    expect(getLatestItemProps(2).disabled).toEqual(true);
});

test('Pass correct allowRemoveWhileDisabled prop to Item of MultiItemSelection', () => {
    mockMultiSelectionStore([{id: 1}]);

    renderMultiSelection({
        allowDeselectForDisabledItems: true,
        resourceKey: 'pages',
        value: [1],
    });

    expect(getLatestItemProps(1).allowRemoveWhileDisabled).toEqual(true);
});

test('PublishIndicator should be rendered if necessary', () => {
    mockMultiSelectionStore([
        {
            id: 1,
            published: '2020-11-16',
            publishedState: true,
        },
        {
            id: 2,
            published: '2020-11-16',
            publishedState: false,
        },
        {
            id: 3,
            published: null,
            publishedState: false,
        },
    ]);

    renderMultiSelection({
        resourceKey: 'pages',
        value: [1, 2, 3],
    });

    expect(mockPublishIndicatorProps).toEqual([
        expect.objectContaining({
            draft: true,
            published: true,
        }),
        expect.objectContaining({
            draft: true,
            published: false,
        }),
    ]);
});
