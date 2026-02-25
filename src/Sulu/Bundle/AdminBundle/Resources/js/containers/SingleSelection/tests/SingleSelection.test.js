// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import SingleSelection from '../SingleSelection';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import publishIndicatorStyles from '../../../components/PublishIndicator/publishIndicator.scss';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/SingleListOverlay', () => jest.fn(() => null));

jest.mock('../../../components/SingleItemSelection', () => jest.fn(function SingleItemSelectionMock(props) {
    const {children, disabled, leftButton, onRemove} = props;
    const handleOpenOverlayClick = leftButton.onClick;
    const handleRemoveItemClick = onRemove;

    return (
        <div>
            <button disabled={disabled} onClick={handleOpenOverlayClick} type="button">open-overlay</button>
            {onRemove && <button onClick={handleRemoveItemClick} type="button">remove-item</button>}
            {children}
        </div>
    );
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn());

let mockInitialStoreItem;
let mockInitialStoreLoading;

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = id === null ? null : {id};
    });
    this.clear = jest.fn(() => {
        this.item = null;
    });

    mockExtendObservable(this, {
        item: mockInitialStoreItem,
        loading: mockInitialStoreLoading,
    });
}));

const SingleItemSelectionMock: any = jest.requireMock('../../../components/SingleItemSelection');
const SingleListOverlayMock: any = jest.requireMock('../../../containers/SingleListOverlay');
const SingleSelectionStoreMock: any = jest.requireMock('../../../stores/SingleSelectionStore');

const getStore = () => SingleSelectionStoreMock.mock.instances[SingleSelectionStoreMock.mock.instances.length - 1];
const getPublishIndicators = () => Array.from(document.querySelectorAll(`.${publishIndicatorStyles.publishIndicator}`))
    .filter((element) => !element.querySelector(`.${publishIndicatorStyles.publishIndicator}`));

beforeEach(() => {
    jest.clearAllMocks();
    mockInitialStoreItem = undefined;
    mockInitialStoreLoading = false;
});

test('Show with passed emptyText and icon', () => {
    const {asFragment} = render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Test"
            icon="su-document"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle=""
            resourceKey="test"
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render with selected item', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('test', 3, locale, undefined);

    const store = getStore();
    act(() => {
        store.item = {
            id: 3,
            name: 'Name',
            value: 'Value',
        };
    });

    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
    expect(getLatestMockProps(SingleItemSelectionMock)).toEqual(expect.objectContaining({
        id: 3,
        value: {id: 3, name: 'Name', value: 'Value'},
    }));
});

test('Render with selected item in disabled state', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    const store = getStore();
    act(() => {
        store.item = {
            id: 3,
            name: 'Name',
            value: 'Value',
        };
    });

    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
    expect(getLatestMockProps(SingleItemSelectionMock).disabled).toEqual(true);
});

test('Pass resourceKey and locale to SingleListOverlay', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test_list"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(getLatestMockProps(SingleListOverlayMock).locale).toEqual(locale);
    expect(getLatestMockProps(SingleListOverlayMock).resourceKey).toEqual('test');
    expect(getLatestMockProps(SingleListOverlayMock).listKey).toEqual('test_list');
    expect(getLatestMockProps(SingleListOverlayMock).options).toEqual(undefined);
});

test('Pass options to SingleListOverlay and SingleSelectionStore', () => {
    render(
        <SingleSelection
            adapter="table"
            detailOptions={{'ghost-content': true}}
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test_list"
            listOptions={{value: 'Test'}}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('test', 3, undefined, {'ghost-content': true});
    expect(getLatestMockProps(SingleListOverlayMock).options).toEqual({value: 'Test'});
});

test('Pass disabledIds and itemDisabledCondition to SingleListOverlay', () => {
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 2, 3]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            itemDisabledCondition='status == "inactive"'
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(getLatestMockProps(SingleListOverlayMock).disabledIds).toEqual([1, 2, 3]);
    expect(getLatestMockProps(SingleListOverlayMock).itemDisabledCondition).toEqual('status == "inactive"');
});

test('Should open and close an overlay', async() => {
    const user = userEvent.setup();
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    await user.click(screen.getByRole('button', {name: 'open-overlay'}));
    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(SingleListOverlayMock).onClose();
    });
    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
});

test('Should not open an overlay on button click when disabled', async() => {
    const user = userEvent.setup();
    render(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'open-overlay'}));
    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
});

test('Should call onChange callback with null if current item is set to null', () => {
    const changeSpy = jest.fn();
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    act(() => {
        getStore().item = null;
    });

    expect(changeSpy).toBeCalledWith(null, null);
});

test('Should call onChange callback if a new item was selected', () => {
    const changeSpy = jest.fn();
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    act(() => {
        getLatestMockProps(SingleItemSelectionMock).leftButton.onClick();
    });
    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(SingleListOverlayMock).onConfirm({id: 6});
    });
    expect(getStore().loadItem).toBeCalledWith(6);
    expect(changeSpy).toBeCalledWith(6, {id: 6});
    expect(getLatestMockProps(SingleListOverlayMock).open).toEqual(false);
});

test('Should not call onChange callback if same item was selected', () => {
    const changeSpy = jest.fn();
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={6}
        />
    );

    act(() => {
        getLatestMockProps(SingleListOverlayMock).onConfirm({id: 6});
    });
    expect(changeSpy).not.toBeCalled();
});

test('Should load item if value prop changes', () => {
    const {rerender} = render(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

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
    expect(getStore().loadItem).toBeCalledWith(3);
});

test('Should call onItemClick callback when item is clicked', () => {
    const itemClickSpy = jest.fn();
    mockInitialStoreItem = {id: 1};

    render(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    getLatestMockProps(SingleItemSelectionMock).onItemClick(1, {id: 1});
    expect(itemClickSpy).toBeCalledWith(1, {id: 1});
});

test('Should remove item when remove button is clicked', async() => {
    const user = userEvent.setup();
    mockInitialStoreItem = {name: 'Name', value: 'Value'};
    render(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    await user.click(screen.getByRole('button', {name: 'remove-item'}));
    expect(getStore().clear).toBeCalledWith();
});

test('Should not call onChange callback if component props change', () => {
    const changeSpy = jest.fn();
    const {rerender} = render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    rerender(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
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
    expect(changeSpy).not.toBeCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    render(
        <SingleSelection
            adapter="table"
            disabled={true}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).disabled).toEqual(true);
    expect(getLatestMockProps(SingleItemSelectionMock).emptyText).toEqual('nothing');
});

test('Pass correct itemDisabled prop to SingleItemSelection component', () => {
    const {rerender} = render(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            itemDisabledCondition='status == "inactive"'
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );
    expect(getLatestMockProps(SingleItemSelectionMock).itemDisabled).toEqual(false);

    act(() => {
        getStore().item = {id: 1, status: 'inactive'};
    });
    expect(getLatestMockProps(SingleItemSelectionMock).itemDisabled).toEqual(true);

    rerender(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 3, 5]}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );
    expect(getLatestMockProps(SingleItemSelectionMock).itemDisabled).toEqual(true);
});

test('Set loading prop of SingleItemSelection component if store is loading', () => {
    mockInitialStoreLoading = true;
    render(
        <SingleSelection
            adapter="table"
            disabled={true}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).loading).toEqual(true);
    expect(SingleListOverlayMock).toHaveBeenCalledTimes(0);
});

test('Pass correct allowRemoveWhileItemDisabled prop to SingleItemSelection component', () => {
    render(
        <SingleSelection
            adapter="table"
            allowDeselectForDisabledItems={true}
            disabledIds={[1, 3, 5]}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(getLatestMockProps(SingleItemSelectionMock).allowRemoveWhileItemDisabled).toEqual(true);
});

test('PublishIndicator should not be rendered if not necessary', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    act(() => {
        getStore().item = {
            id: 1,
            name: 'Name',
            published: '2020-11-16',
            publishedState: true,
        };
    });

    expect(getPublishIndicators()).toHaveLength(0);
});

test('PublishIndicator should be rendered as draft if necessary', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    act(() => {
        getStore().item = {
            id: 1,
            name: 'Name',
            published: '2020-11-16',
            publishedState: false,
        };
    });

    const publishIndicators = getPublishIndicators();
    expect(publishIndicators).toHaveLength(1);
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.draft}`)).not.toBeNull();
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.published}`)).not.toBeNull();
});

test('PublishIndicator should be rendered as unpublished if necessary', () => {
    const locale = observable.box('en');
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    act(() => {
        getStore().item = {
            id: 1,
            name: 'Name',
            published: null,
            publishedState: false,
        };
    });

    const publishIndicators = getPublishIndicators();
    expect(publishIndicators).toHaveLength(1);
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.draft}`)).not.toBeNull();
    expect(publishIndicators[0].querySelector(`.${publishIndicatorStyles.published}`)).toBeNull();
});
