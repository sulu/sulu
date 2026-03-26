// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import SingleListOverlay from '../../../containers/SingleListOverlay';
import SingleSelection from '../SingleSelection';
import SingleItemSelection from '../../../components/SingleItemSelection';
import PublishIndicator from '../../../components/PublishIndicator';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/SingleListOverlay', () => jest.fn(() => <div data-testid="single-list-overlay" />));

jest.mock('../../../components/SingleItemSelection', () => {
    const React = require('react');
    const ActualSingleItemSelection = jest.requireActual('../../../components/SingleItemSelection').default;

    return jest.fn((props) => <ActualSingleItemSelection {...props} />);
});

jest.mock('../../../components/PublishIndicator', () => {
    const React = require('react');
    const ActualPublishIndicator = jest.requireActual('../../../components/PublishIndicator').default;

    return jest.fn((props) => <ActualPublishIndicator {...props} />);
});

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn());

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = {id};
    });
    this.clear = jest.fn();

    mockExtendObservable(this, {
        item: undefined,
        loading: false,
    });
}));

const getLastMockCallProps = (mockComponent) => {
    const mockCalls = mockComponent.mock.calls;

    if (mockCalls.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return mockCalls[mockCalls.length - 1][0];
};

const SingleSelectionStoreMock = (SingleSelectionStore: any);
const SingleListOverlayMock = (SingleListOverlay: any);
const SingleItemSelectionMock = (SingleItemSelection: any);
const PublishIndicatorMock = (PublishIndicator: any);

const getSingleSelectionStore = () => {
    const stores = SingleSelectionStoreMock.mock.instances;

    if (stores.length === 0) {
        throw new Error('Expected SingleSelectionStore to be instantiated');
    }

    return stores[stores.length - 1];
};

const getSingleListOverlayProps = () => getLastMockCallProps(SingleListOverlayMock);
const getSingleItemSelectionProps = () => getLastMockCallProps(SingleItemSelectionMock);
const getPublishIndicatorProps = () => getLastMockCallProps(PublishIndicatorMock);

beforeEach(() => {
    jest.clearAllMocks();
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
    const {asFragment} = render(
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

    act(() => {
        getSingleSelectionStore().item = {
            id: 3,
            name: 'Name',
            value: 'Value',
        };
    });

    expect(getSingleListOverlayProps().open).toEqual(false);
    expect(asFragment()).toMatchSnapshot();
});

test('Render with selected item in disabled state', () => {
    const locale = observable.box('en');
    const {asFragment} = render(
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

    act(() => {
        getSingleSelectionStore().item = {
            id: 3,
            name: 'Name',
            value: 'Value',
        };
    });

    expect(getSingleListOverlayProps().open).toEqual(false);
    expect(asFragment()).toMatchSnapshot();
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

    expect(getSingleListOverlayProps().locale).toEqual(locale);
    expect(getSingleListOverlayProps().resourceKey).toEqual('test');
    expect(getSingleListOverlayProps().listKey).toEqual('test_list');
    expect(getSingleListOverlayProps().options).toEqual(undefined);
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

    expect(getSingleListOverlayProps().options).toEqual({value: 'Test'});
});

test('Pass disabledIds to SingleListOverlay', () => {
    render(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 2, 3]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(getSingleListOverlayProps().disabledIds).toEqual([1, 2, 3]);
});

test('Pass itemDisabledCondition to SingleListOverlay', () => {
    render(
        <SingleSelection
            adapter="table"
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

    expect(getSingleListOverlayProps().itemDisabledCondition).toEqual('status == "inactive"');
});

test('Should open and close an overlay', () => {
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

    act(() => {
        getSingleItemSelectionProps().leftButton.onClick();
    });
    expect(getSingleListOverlayProps().open).toEqual(true);

    act(() => {
        getSingleListOverlayProps().onClose();
    });
    expect(getSingleListOverlayProps().open).toEqual(false);
});

test('Should not open an overlay on button-click when disabled', async() => {
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

    expect(getSingleListOverlayProps().open).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'su-test'}));

    expect(getSingleListOverlayProps().open).toEqual(false);
});

test('Should call the onChange callback with null if the current item does not exist and set to null', () => {
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
        getSingleSelectionStore().item = null;
    });

    expect(changeSpy).toBeCalledWith(null, null);
});

test('Should call the onChange callback if a new item was selected', () => {
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
        getSingleItemSelectionProps().leftButton.onClick();
    });
    expect(getSingleListOverlayProps().open).toEqual(true);

    act(() => {
        getSingleListOverlayProps().onConfirm({id: 6});
    });
    expect(getSingleSelectionStore().loadItem).toBeCalledWith(6);
    expect(changeSpy).toBeCalledWith(6, {id: 6});
    expect(getSingleListOverlayProps().open).toEqual(false);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

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

    // disable load-item mock that would overwrite the item property of the store mock
    getSingleSelectionStore().loadItem = jest.fn();

    // change callback should be called when item of the store mock changes
    act(() => {
        getSingleSelectionStore().item = {id: 7};
    });
    expect(changeSpy).toBeCalledWith(7, {id: 7});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the same item was selected', () => {
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

    getSingleListOverlayProps().onConfirm({id: 6});
    expect(changeSpy).not.toBeCalled();
});

test('Should load the item if value prop changes', () => {
    const props = {
        adapter: 'table',
        displayProperties: [],
        emptyText: 'nothing',
        listKey: 'snippets',
        onChange: jest.fn(),
        overlayTitle: 'Selection',
        resourceKey: 'snippets',
    };

    const {rerender} = render(
        <SingleSelection
            {...props}
            value={1}
        />
    );

    rerender(
        <SingleSelection
            {...props}
            value={3}
        />
    );

    expect(getSingleSelectionStore().loadItem).toBeCalledWith(3);
});

test('Should call the onItemClick callback when an item when the item is clicked', () => {
    const itemClickSpy = jest.fn();

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

    act(() => {
        getSingleSelectionStore().item = {id: 1};
    });
    getSingleItemSelectionProps().onItemClick(1, {id: 1});

    expect(itemClickSpy).toBeCalledWith(1, {id: 1});
});

test('Should remove an item when the remove button is clicked', () => {
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

    act(() => {
        getSingleSelectionStore().item = {
            name: 'Name',
            value: 'Value',
        };
    });
    getSingleItemSelectionProps().onRemove();
    expect(getSingleSelectionStore().clear).toBeCalledWith();
});

test('Should call the onChange callback if the value of the selection-store changes', () => {
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
        getSingleSelectionStore().item = {id: 6};
    });
    expect(changeSpy).toBeCalledWith(6, {id: 6});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();
    const props = {
        adapter: 'table',
        disabledIds: [],
        displayProperties: [],
        icon: 'su-test',
        listKey: 'test',
        onChange: changeSpy,
        overlayTitle: 'Test',
        resourceKey: 'test',
        value: 3,
    };

    const {rerender} = render(
        <SingleSelection
            {...props}
            emptyText="Nothing"
        />
    );

    rerender(
        <SingleSelection
            {...props}
            emptyText="New Empty Text"
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

    expect(getSingleItemSelectionProps().disabled).toEqual(true);
    expect(getSingleItemSelectionProps().emptyText).toEqual('nothing');
});

test('Pass correct itemDisabled prop to SingleItemSelection component when item fulfills itemDisabledCondition', () => {
    render(
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

    expect(getSingleItemSelectionProps().itemDisabled).toEqual(false);

    act(() => {
        getSingleSelectionStore().item = {
            id: 1,
            status: 'inactive',
        };
    });

    expect(getSingleItemSelectionProps().itemDisabled).toEqual(true);
});

test('Pass correct itemDisabled prop to SingleItemSelection component when disabledIds contains id of item', () => {
    render(
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

    expect(getSingleItemSelectionProps().itemDisabled).toEqual(false);

    act(() => {
        getSingleSelectionStore().item = {
            id: 3,
            status: 'inactive',
        };
    });

    expect(getSingleItemSelectionProps().itemDisabled).toEqual(true);
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
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

    expect(getSingleItemSelectionProps().loading).toEqual(false);
    act(() => {
        getSingleSelectionStore().loading = true;
    });
    expect(getSingleItemSelectionProps().loading).toEqual(true);
    expect(screen.queryByTestId('single-list-overlay')).not.toBeInTheDocument();
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

    expect(getSingleItemSelectionProps().allowRemoveWhileItemDisabled).toEqual(true);
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
        getSingleSelectionStore().item = {
            id: 1,
            name: 'Name',
            published: '2020-11-16',
            publishedState: true,
        };
    });

    expect(PublishIndicatorMock).not.toBeCalled();
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
        getSingleSelectionStore().item = {
            id: 1,
            name: 'Name',
            published: '2020-11-16',
            publishedState: false,
        };
    });

    expect(getPublishIndicatorProps().draft).toBe(true);
    expect(getPublishIndicatorProps().published).toBe(true);
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
        getSingleSelectionStore().item = {
            id: 1,
            name: 'Name',
            published: null,
            publishedState: false,
        };
    });

    expect(getPublishIndicatorProps().draft).toBe(true);
    expect(getPublishIndicatorProps().published).toBe(false);
});
