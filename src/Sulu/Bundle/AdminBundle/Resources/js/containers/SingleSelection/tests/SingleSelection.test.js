// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import SingleSelection from '../SingleSelection';

let mockSingleListOverlayProps: Object = {};
let mockSingleItemSelectionProps: Object = {};
let mockPublishIndicatorProps: Object = {};
let mockSingleSelectionStoreInstances: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../utils/Translator');

jest.mock('../../../containers/SingleListOverlay', () => jest.fn((props) => {
    mockSingleListOverlayProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-open': props.open ? 'true' : 'false',
            'data-testid': 'single-list-overlay',
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
                onClick: () => props.onConfirm({id: 6}),
                type: 'button',
            },
            'Confirm'
        )
    );
}));

jest.mock('../../../components/SingleItemSelection', () => jest.fn((props) => {
    mockSingleItemSelectionProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-disabled': props.disabled ? 'true' : 'false',
            'data-testid': 'single-item-selection',
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
        mockReact.createElement(
            'button',
            {
                'aria-label': 'item',
                onClick: () => props.onItemClick && props.onItemClick(props.id, props.value),
                type: 'button',
            },
            props.children || props.emptyText
        ),
        props.onRemove && mockReact.createElement(
            'button',
            {
                'aria-label': 'remove',
                onClick: props.onRemove,
                type: 'button',
            },
            'Remove'
        )
    );
}));

jest.mock('../../../components/PublishIndicator', () => jest.fn((props) => {
    mockPublishIndicatorProps = props;

    return mockReact.createElement(
        'div',
        {
            'data-draft': props.draft ? 'true' : 'false',
            'data-published': props.published ? 'true' : 'false',
            'data-testid': 'publish-indicator',
        }
    );
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn());

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function(resourceKey, value, locale, detailOptions) {
    this.resourceKey = resourceKey;
    this.value = value;
    this.locale = locale;
    this.detailOptions = detailOptions;
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = {id};
    });
    this.clear = jest.fn(() => {
        this.item = undefined;
    });

    mockExtendObservable(this, {
        item: undefined,
        loading: false,
    });

    mockSingleSelectionStoreInstances.push(this);
}));

beforeEach(() => {
    jest.clearAllMocks();
    mockSingleListOverlayProps = {};
    mockSingleItemSelectionProps = {};
    mockPublishIndicatorProps = {};
    mockSingleSelectionStoreInstances = [];
});

function renderSingleSelection(props: Object = {}) {
    return render(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="Nothing"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
            {...props}
        />
    );
}

function getSingleSelectionStore() {
    return mockSingleSelectionStoreInstances[0];
}

function setStoreItem(item) {
    act(() => {
        (getSingleSelectionStore(): any).item = item;
    });
}

function setStoreLoading(loading) {
    act(() => {
        (getSingleSelectionStore(): any).loading = loading;
    });
}

test('Show with passed emptyText and icon', () => {
    renderSingleSelection({
        emptyText: 'Test',
        icon: 'su-document',
        value: undefined,
    });

    expect(screen.getByText('Test')).toBeInTheDocument();
    expect(mockSingleItemSelectionProps.leftButton.icon).toEqual('su-document');
});

test('Render with selected item', async() => {
    const locale = observable.box('en');

    renderSingleSelection({
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        locale,
        value: 3,
    });

    expect(SingleSelectionStore).toHaveBeenCalledWith('test', 3, locale, undefined);

    setStoreItem({
        id: 3,
        name: 'Name',
        value: 'Value',
    });

    expect(await screen.findByText('Name')).toBeInTheDocument();
    expect(screen.getByText('Value')).toBeInTheDocument();
    expect(mockSingleListOverlayProps.open).toEqual(false);
});

test('Render with selected item in disabled state', async() => {
    renderSingleSelection({
        disabled: true,
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        value: 3,
    });

    setStoreItem({
        id: 3,
        name: 'Name',
        value: 'Value',
    });

    expect(await screen.findByText('Name')).toBeInTheDocument();
    expect(screen.getByTestId('single-item-selection')).toHaveAttribute('data-disabled', 'true');
    expect(mockSingleListOverlayProps.open).toEqual(false);
});

test('Pass resourceKey and locale to SingleListOverlay', () => {
    const locale = observable.box('en');

    renderSingleSelection({
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        listKey: 'test_list',
        locale,
        value: 3,
    });

    expect(mockSingleListOverlayProps.locale).toEqual(locale);
    expect(mockSingleListOverlayProps.resourceKey).toEqual('test');
    expect(mockSingleListOverlayProps.listKey).toEqual('test_list');
    expect(mockSingleListOverlayProps.options).toEqual(undefined);
});

test('Pass options to SingleListOverlay and SingleSelectionStore', () => {
    renderSingleSelection({
        detailOptions: {'ghost-content': true},
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        listKey: 'test_list',
        listOptions: {value: 'Test'},
        value: 3,
    });

    expect(SingleSelectionStore).toHaveBeenCalledWith('test', 3, undefined, {'ghost-content': true});
    expect(mockSingleListOverlayProps.options).toEqual({value: 'Test'});
});

test('Pass disabledIds to SingleListOverlay', () => {
    renderSingleSelection({
        disabledIds: [1, 2, 3],
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        value: 3,
    });

    expect(mockSingleListOverlayProps.disabledIds).toEqual([1, 2, 3]);
});

test('Pass itemDisabledCondition to SingleListOverlay', () => {
    renderSingleSelection({
        displayProperties: ['name', 'value'],
        emptyText: 'Nothing',
        icon: 'su-test',
        itemDisabledCondition: 'status == "inactive"',
        value: 3,
    });

    expect(mockSingleListOverlayProps.itemDisabledCondition).toEqual('status == "inactive"');
});

test('Should open and close an overlay', async() => {
    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        value: 3,
    });

    await userEvent.click(screen.getByLabelText('left-button'));

    await waitFor(() => expect(mockSingleListOverlayProps.open).toEqual(true));

    await userEvent.click(screen.getByLabelText('overlay-close'));

    await waitFor(() => expect(mockSingleListOverlayProps.open).toEqual(false));
});

test('Should not open an overlay on button-click when disabled', async() => {
    renderSingleSelection({
        disabled: true,
        emptyText: 'Nothing',
        icon: 'su-test',
        value: 3,
    });

    expect(mockSingleListOverlayProps.open).toEqual(false);

    await userEvent.click(screen.getByLabelText('left-button'));

    expect(mockSingleListOverlayProps.open).toEqual(false);
});

test('Should call the onChange callback with null if the current item does not exist and set to null', async() => {
    const changeSpy = jest.fn();

    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 3,
    });

    setStoreItem(null);

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith(null, null));
});

test('Should call the onChange callback if a new item was selected', async() => {
    const changeSpy = jest.fn();

    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 3,
    });

    await userEvent.click(screen.getByLabelText('left-button'));
    await waitFor(() => expect(mockSingleListOverlayProps.open).toEqual(true));

    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect((getSingleSelectionStore(): any).loadItem).toHaveBeenCalledWith(6);
    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith(6, {id: 6}));
    expect(mockSingleListOverlayProps.open).toEqual(false);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 3,
    });

    // disable load-item mock that would overwrite the item property of the store mock
    (getSingleSelectionStore(): any).loadItem = jest.fn();

    // change callback should be called when item of the store mock changes
    setStoreItem({id: 7});
    expect(changeSpy).toHaveBeenCalledWith(7, {id: 7});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    act(() => {
        unrelatedObservable.set(55);
    });
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the same item was selected', async() => {
    const changeSpy = jest.fn();

    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 6,
    });

    await userEvent.click(screen.getByLabelText('overlay-confirm'));

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should load the item if value prop changes', () => {
    const {rerender} = renderSingleSelection({
        emptyText: 'nothing',
        listKey: 'snippets',
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    rerender(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={3}
        />
    );

    expect((getSingleSelectionStore(): any).loadItem).toHaveBeenCalledWith(3);
});

test('Should call the onItemClick callback when an item when the item is clicked', async() => {
    const itemClickSpy = jest.fn();

    renderSingleSelection({
        emptyText: 'nothing',
        listKey: 'snippets',
        onItemClick: itemClickSpy,
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    setStoreItem({id: 1});
    await userEvent.click(screen.getByLabelText('item'));

    expect(itemClickSpy).toHaveBeenCalledWith(1, {id: 1});
});

test('Should remove an item when the remove button is clicked', async() => {
    renderSingleSelection({
        emptyText: 'nothing',
        listKey: 'snippets',
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    setStoreItem({
        name: 'Name',
        value: 'Value',
    });

    await userEvent.click(screen.getByLabelText('remove'));

    expect((getSingleSelectionStore(): any).clear).toHaveBeenCalledWith();
});

test('Should call the onChange callback if the value of the selection-store changes', async() => {
    const changeSpy = jest.fn();

    renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 3,
    });

    setStoreItem({id: 6});

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith(6, {id: 6}));
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = renderSingleSelection({
        emptyText: 'Nothing',
        icon: 'su-test',
        onChange: changeSpy,
        value: 3,
    });

    rerender(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="New Empty Text"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    renderSingleSelection({
        disabled: true,
        emptyText: 'nothing',
        listKey: 'snippets',
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    expect(mockSingleItemSelectionProps.disabled).toEqual(true);
    expect(mockSingleItemSelectionProps.emptyText).toEqual('nothing');
});

test(
    'Pass correct itemDisabled prop to SingleItemSelection component when item fulfills itemDisabledCondition',
    async() => {
        renderSingleSelection({
            emptyText: 'nothing',
            itemDisabledCondition: 'status == "inactive"',
            listKey: 'snippets',
            overlayTitle: 'Selection',
            resourceKey: 'snippets',
            value: 3,
        });

        expect(mockSingleItemSelectionProps.itemDisabled).toEqual(false);

        setStoreItem({
            id: 3,
            status: 'inactive',
        });

        await waitFor(() => expect(mockSingleItemSelectionProps.itemDisabled).toEqual(true));
    }
);

test(
    'Pass correct itemDisabled prop to SingleItemSelection component when disabledIds contains id of item',
    async() => {
        renderSingleSelection({
            disabledIds: [1, 3, 5],
            emptyText: 'nothing',
            listKey: 'snippets',
            overlayTitle: 'Selection',
            resourceKey: 'snippets',
            value: 3,
        });

        expect(mockSingleItemSelectionProps.itemDisabled).toEqual(false);

        setStoreItem({
            id: 3,
            status: 'inactive',
        });

        await waitFor(() => expect(mockSingleItemSelectionProps.itemDisabled).toEqual(true));
    }
);

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', async() => {
    renderSingleSelection({
        disabled: true,
        emptyText: 'nothing',
        listKey: 'snippets',
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    expect(mockSingleItemSelectionProps.loading).toEqual(false);

    setStoreLoading(true);

    await waitFor(() => expect(mockSingleItemSelectionProps.loading).toEqual(true));
    expect(screen.queryByTestId('single-list-overlay')).not.toBeInTheDocument();
});

test('Pass correct allowRemoveWhileItemDisabled prop to SingleItemSelection component', () => {
    renderSingleSelection({
        allowDeselectForDisabledItems: true,
        disabledIds: [1, 3, 5],
        emptyText: 'nothing',
        listKey: 'snippets',
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
        value: 1,
    });

    expect(mockSingleItemSelectionProps.allowRemoveWhileItemDisabled).toEqual(true);
});

test('PublishIndicator should not be rendered if not necessary', async() => {
    const locale = observable.box('en');

    renderSingleSelection({
        displayProperties: ['name'],
        emptyText: 'Nothing',
        icon: 'su-test',
        locale,
        value: 1,
    });

    setStoreItem({
        id: 1,
        name: 'Name',
        published: '2020-11-16',
        publishedState: true,
    });

    expect(await screen.findByText('Name')).toBeInTheDocument();
    expect(screen.queryByTestId('publish-indicator')).not.toBeInTheDocument();
});

test('PublishIndicator should be rendered as draft if necessary', async() => {
    const locale = observable.box('en');

    renderSingleSelection({
        displayProperties: ['name'],
        emptyText: 'Nothing',
        icon: 'su-test',
        locale,
        value: 1,
    });

    setStoreItem({
        id: 1,
        name: 'Name',
        published: '2020-11-16',
        publishedState: false,
    });

    expect(await screen.findByTestId('publish-indicator')).toBeInTheDocument();
    expect(mockPublishIndicatorProps.draft).toBe(true);
    expect(mockPublishIndicatorProps.published).toBe(true);
});

test('PublishIndicator should be rendered as unpublished if necessary', async() => {
    const locale = observable.box('en');

    renderSingleSelection({
        displayProperties: ['name'],
        emptyText: 'Nothing',
        icon: 'su-test',
        locale,
        value: 1,
    });

    setStoreItem({
        id: 1,
        name: 'Name',
        published: null,
        publishedState: false,
    });

    expect(await screen.findByTestId('publish-indicator')).toBeInTheDocument();
    expect(mockPublishIndicatorProps.draft).toBe(true);
    expect(mockPublishIndicatorProps.published).toBe(false);
});
