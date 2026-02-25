// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {comparer, extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiSelection from '../MultiSelection';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import multiItemSelectionButtonStyles from '../../../components/MultiItemSelection/button.scss';
import multiItemSelectionItemStyles from '../../../components/MultiItemSelection/item.scss';
import publishIndicatorStyles from '../../../components/PublishIndicator/publishIndicator.scss';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/MultiListOverlay', () => jest.fn(() => null));

let mockInitialStoreItems;

jest.mock('../../../stores/MultiSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((items) => {
        this.items = items;
    });
    this.move = jest.fn();
    this.removeById = jest.fn();
    this.loadItems = jest.fn();
    this.setRequestParameters = jest.fn();

    mockExtendObservable(this, {
        items: mockInitialStoreItems,
        loading: false,
    });
}));

const MultiListOverlayMock: any = jest.requireMock('../../../containers/MultiListOverlay');
const MultiSelectionStoreMock: any = jest.requireMock('../../../stores/MultiSelectionStore');

const getStore = () => (
    MultiSelectionStoreMock.mock.instances[MultiSelectionStoreMock.mock.instances.length - 1]
);
const getOverlayToggleButton = (): HTMLButtonElement => {
    const button = document.querySelector(`button.${multiItemSelectionButtonStyles.left}`);
    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('Expected MultiItemSelection left button to be rendered');
    }

    return button;
};
const getRenderedItems = (): Array<HTMLElement> => Array.from(
    document.querySelectorAll(`.${multiItemSelectionItemStyles.item}`)
);
const getClickableItems = (): Array<HTMLElement> => Array.from(
    document.querySelectorAll(`.${multiItemSelectionItemStyles.content}[role="button"]`)
);
const getItemRemoveButtons = (): Array<HTMLElement> => Array.from(
    document.querySelectorAll(`.${multiItemSelectionItemStyles.buttons} > button`)
);
const getPublishIndicators = () => Array.from(document.querySelectorAll(`.${publishIndicatorStyles.publishIndicator}`))
    .filter((element) => !element.querySelector(`.${publishIndicatorStyles.publishIndicator}`));

beforeEach(() => {
    jest.clearAllMocks();
    mockInitialStoreItems = [];
});

test('Show with passed icon and label', () => {
    mockInitialStoreItems = [{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2'}, {id: 5, title: 'Title 5'}];
    const {asFragment} = render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            icon="su-document"
            label="Select Snippets"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Pass correct props to MultiItemSelection component', () => {
    mockInitialStoreItems = [{id: 1}];
    render(
        <MultiSelection
            adapter="table"
            disabled={true}
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            sortable={false}
        />
    );

    expect(getOverlayToggleButton()).toBeDisabled();

    const firstItem = getRenderedItems()[0];
    if (!firstItem) {
        throw new Error('Expected first rendered item');
    }
    const dragHandle = firstItem.querySelector(`.${multiItemSelectionItemStyles.dragHandle}`);
    expect(dragHandle).not.toBeNull();
    expect(dragHandle).not.toHaveClass(multiItemSelectionItemStyles.sortable);
});

test('Render selected item in disabled state when disabledIds contain its id', () => {
    mockInitialStoreItems = [{id: 1}, {id: 2}];
    render(
        <MultiSelection
            adapter="table"
            disabledIds={[2, 4]}
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    const renderedItems = getRenderedItems();
    expect(renderedItems[0]).not.toHaveClass(multiItemSelectionItemStyles.disabled);
    expect(renderedItems[1]).toHaveClass(multiItemSelectionItemStyles.disabled);
});

test('Pass locale/options/disabledIds/itemDisabledCondition to MultiListOverlay', () => {
    const locale = observable.box('de');
    render(
        <MultiSelection
            adapter="table"
            disabledIds={[1, 2, 4]}
            itemDisabledCondition='status == "inactive"'
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={{types: 'test'}}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    expect(getLatestMockProps(MultiListOverlayMock).locale.get()).toEqual('de');
    expect(getLatestMockProps(MultiListOverlayMock).options).toEqual({types: 'test'});
    expect(getLatestMockProps(MultiListOverlayMock).disabledIds).toEqual([1, 2, 4]);
    expect(getLatestMockProps(MultiListOverlayMock).itemDisabledCondition).toEqual('status == "inactive"');
});

test('Construct MultiSelectionStore with correct parameters', () => {
    const locale = observable.box('en');
    render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={{key: 'value-1'}}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1, 2, 5]}
        />
    );

    expect(MultiSelectionStore).toBeCalledWith('snippets', [1, 2, 5], locale, 'ids', {key: 'value-1'});
});

test('Update requestParameters and reload items when options prop changes', () => {
    const locale = observable.box('en');
    const {rerender} = render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={{key: 'value-1'}}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1, 2, 5]}
        />
    );

    expect(getStore().setRequestParameters).not.toBeCalled();
    expect(getStore().loadItems).not.toBeCalled();

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

    expect(getStore().setRequestParameters).toBeCalledWith({key: 'value-2'});
    expect(getStore().loadItems).toBeCalledWith([1, 2, 5]);
});

test('Do not reload items when options value has not changed', () => {
    const locale = observable.box('en');
    const options = observable.box({key: 'value-1'}, {equals: comparer.structural});
    const {rerender} = render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            listKey="snippets"
            locale={locale}
            onChange={jest.fn()}
            options={options.get()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[]}
        />
    );

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

    expect(getStore().setRequestParameters).not.toBeCalled();
    expect(getStore().loadItems).not.toBeCalled();
});

test('Should open and close overlay', async() => {
    const user = userEvent.setup();
    render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    expect(getLatestMockProps(MultiListOverlayMock).open).toEqual(false);
    await user.click(getOverlayToggleButton());
    expect(getLatestMockProps(MultiListOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(MultiListOverlayMock).onClose();
    });
    expect(getLatestMockProps(MultiListOverlayMock).open).toEqual(false);
});

test('Should not open overlay on click when disabled', async() => {
    const user = userEvent.setup();
    render(
        <MultiSelection
            adapter="table"
            disabled={true}
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    await user.click(getOverlayToggleButton());
    expect(getLatestMockProps(MultiListOverlayMock).open).toEqual(false);
});

test('Should call store.set when confirm is triggered', () => {
    const selectedItems = [{id: 3}, {id: 7}, {id: 2}];

    render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    act(() => {
        getLatestMockProps(MultiListOverlayMock).onConfirm(selectedItems);
    });
    expect(getStore().set).toBeCalledWith(selectedItems);
    expect(getLatestMockProps(MultiListOverlayMock).open).toEqual(false);
});

test('Should call onItemClick callback when item is clicked', () => {
    const itemClickSpy = jest.fn();
    const item1 = {id: 1, title: 'Title 1'};
    const item2 = {id: 2, title: 'Title 2'};
    mockInitialStoreItems = [item1, item2];

    render(
        <MultiSelection
            adapter="table"
            displayProperties={['id', 'title']}
            icon="su-document"
            label="Select Snippets"
            listKey="snippets"
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
        />
    );

    const clickableItems = getClickableItems();
    clickableItems[0].click();
    expect(itemClickSpy).toHaveBeenLastCalledWith(1, item1);
    clickableItems[1].click();
    expect(itemClickSpy).toHaveBeenLastCalledWith(2, item2);
});

test('Should load items if value prop changes', () => {
    const {rerender} = render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1]}
        />
    );

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
    expect(getStore().loadItems).toBeCalledWith([1, 3]);
});

test('Should remove and reorder items via MultiItemSelection callbacks', () => {
    mockInitialStoreItems = [{id: 3}, {id: 7}, {id: 9}];
    const multiSelectionRef = React.createRef();

    render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            ref={multiSelectionRef}
            resourceKey="snippets"
            value={[3, 7, 9]}
        />
    );

    const removeButtons = getItemRemoveButtons();
    removeButtons[1].click();
    expect(getStore().removeById).toBeCalledWith(7);

    act(() => {
        (multiSelectionRef.current: any).handleSorted(1, 2);
    });
    expect(getStore().move).toBeCalledWith(1, 2);
});

test('Should call onChange callback if selection-store value changes', () => {
    const changeSpy = jest.fn();
    render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1]}
        />
    );

    act(() => {
        getStore().items = [{id: 22}, {id: 23}];
    });
    expect(changeSpy).toBeCalledWith([22, 23]);
});

test('Should not call onChange callback if component props change', () => {
    const changeSpy = jest.fn();
    const {rerender} = render(
        <MultiSelection
            adapter="table"
            listKey="snippets"
            onChange={changeSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={[1]}
        />
    );

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
    expect(changeSpy).not.toBeCalled();
});

test('Should render selected item disabled if it fulfills itemDisabledCondition', () => {
    mockInitialStoreItems = [{id: 1, status: 'active'}, {id: 2, status: 'inactive'}];
    render(
        <MultiSelection
            adapter="table"
            itemDisabledCondition='status == "inactive"'
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    const renderedItems = getRenderedItems();
    expect(renderedItems[0]).not.toHaveClass(multiItemSelectionItemStyles.disabled);
    expect(renderedItems[1]).toHaveClass(multiItemSelectionItemStyles.disabled);
});

test('Pass correct allowRemoveWhileDisabled prop to MultiItemSelection.Item', () => {
    mockInitialStoreItems = [{id: 1}];
    render(
        <MultiSelection
            adapter="table"
            allowDeselectForDisabledItems={true}
            disabledIds={[1]}
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 5, 8]}
        />
    );

    expect(getItemRemoveButtons()).toHaveLength(1);
});

test('PublishIndicator should be rendered when necessary', () => {
    mockInitialStoreItems = [
        {id: 1, published: '2020-11-16', publishedState: true},
        {id: 2, published: '2020-11-16', publishedState: false},
        {id: 3, published: null, publishedState: false},
    ];

    render(
        <MultiSelection
            adapter="table"
            listKey="pages"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="pages"
            value={[1, 2, 3]}
        />
    );

    const publishIndicators = getPublishIndicators();

    expect(publishIndicators).toHaveLength(2);
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.draft}`)).not.toBeNull();
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.published}`)).not.toBeNull();
    expect(publishIndicators[1].querySelector(`.${publishIndicatorStyles.draft}`)).not.toBeNull();
    expect(publishIndicators[1].querySelector(`.${publishIndicatorStyles.published}`)).toBeNull();
});
