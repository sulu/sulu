// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import fieldRegistry from '../../../Form/registries/fieldRegistry';
import FormInspector from '../../../Form/FormInspector';
import ProductFamilyAttributes from '../ProductFamilyAttributes';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key, parameters) => parameters ? key + ':' + JSON.stringify(parameters) : key),
}));

jest.mock('../../../Form/FormInspector', () => jest.fn());

// The registry hands back a plain checkbox for every type, enough to see the value round-trip.
jest.mock('../../../Form/registries/fieldRegistry', () => ({
    get: jest.fn(() => function Field(props) {
        const React = require('react');

        return React.createElement('input', {
            checked: !!props.value,
            disabled: props.disabled,
            onChange: (event) => props.onChange(event.target.checked),
            type: 'checkbox',
        });
    }),
    getOptions: jest.fn(() => ({})),
}));

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(function() {
    mockExtendObservable(this, {items: [], loading: false});
    this.set = jest.fn((items) => {
        this.items = items;
    });
    this.loadItems = jest.fn(() => {
        this.loading = true;
    });
}));

const mockOverlayItems = [
    {group: 'g1', groupName: 'General', id: 'a1', name: 'Size', position: 1},
    {group: 'g1', groupName: 'General', id: 'a4', name: 'Colour', position: 2},
];

jest.mock('../../../MultiListOverlay', () => function MultiListOverlay(props) {
    const React = require('react');

    if (!props.open) {
        return null;
    }

    return React.createElement(
        'button',
        {onClick: () => props.onConfirm(mockOverlayItems), type: 'button'},
        'confirm-overlay'
    );
});

const ITEMS = [
    {group: 'g2', groupName: 'Marketing', id: 'a3', name: 'Season', position: 0},
    {group: 'g1', groupName: 'General', id: 'a1', name: 'Size', position: 1},
    {group: 'g1', groupName: 'General', id: 'a2', name: 'Fabric', position: 0},
];

const VALUE = [
    {id: 'a3', required: false, variantSpecific: false},
    {id: 'a1', required: false, variantSpecific: true},
    {id: 'a2', required: true, variantSpecific: false},
];

// $FlowFixMe
const formInspector: FormInspector = new FormInspector();

const formProps = {
    dataPath: '/attributes',
    formInspector,
    listKey: 'attributes',
    resourceKey: 'attributes',
    router: undefined,
    schemaPath: '/attributes',
};

function renderComponent(value = VALUE, onChange = jest.fn()) {
    const view = render(
        <ProductFamilyAttributes
            {...formProps}
            locale={observable.box('en')}
            onChange={onChange}
            value={value}
        />
    );
    const MultiSelectionStore = require('../../../../stores/MultiSelectionStore');
    // $FlowFixMe
    const store = MultiSelectionStore.mock.instances[0];
    store.items = ITEMS;

    return {...view, store};
}

// The form hands the changed value straight back as the new prop; mirror that for the overlay tests.
type ControlledProps = {onChange: Function, value: Array<Object>};

class ControlledProductFamilyAttributes extends React.Component<ControlledProps, {value: Array<Object>}> {
    state = {value: this.props.value};

    handleChange = (value: Array<Object>) => {
        this.setState({value});
        this.props.onChange(value);
    };

    render() {
        return (
            <ProductFamilyAttributes
                {...formProps}
                locale={observable.box('en')}
                onChange={this.handleChange}
                value={this.state.value}
            />
        );
    }
}

function renderControlled(value = VALUE, onChange = jest.fn()) {
    const view = render(<ControlledProductFamilyAttributes onChange={onChange} value={value} />);
    const MultiSelectionStore = require('../../../../stores/MultiSelectionStore');
    // $FlowFixMe
    const store = MultiSelectionStore.mock.instances[0];
    store.items = ITEMS;

    return {...view, store};
}

// Every card starts collapsed, so a test that reads the rows opens the cards first.
async function expandAllCards() {
    for (const toggle of screen.getAllByLabelText('su-expand-vertical')) {
        await userEvent.click(toggle);
    }
}

function getRow(name: string) {
    // $FlowFixMe
    return screen.getByText(name).closest('tr');
}

function getCard(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('section');
}

function getCardHeader(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('header');
}

test('loads the selected attributes from the resource', () => {
    renderComponent();

    const MultiSelectionStore = require('../../../../stores/MultiSelectionStore');

    expect(MultiSelectionStore).toHaveBeenCalledWith('attributes', ['a3', 'a1', 'a2'], expect.anything(), 'ids');
});

test('renders the flag columns through the registered checkbox field type', async() => {
    renderComponent();
    await expandAllCards();

    expect(fieldRegistry.get).toHaveBeenCalledWith('checkbox');
    expect(within(getRow('Size')).getAllByRole('checkbox')).toHaveLength(2);
});

test('orders group cards alphabetically by group name', () => {
    renderComponent();

    const headings = screen.getAllByText(/^(General|Marketing)$/).map((node) => node.textContent);

    expect(headings).toEqual(['General', 'Marketing']);
});

test('orders attributes inside a group by position', async() => {
    renderComponent();
    await expandAllCards();

    // Collapsible's root is <section role="switch">, so this scopes the query to one card.
    const general = screen.getByText('General').closest('[role="switch"]');
    const names = within(general).getAllByText(/^(Fabric|Size)$/).map((node) => node.textContent);

    expect(names).toEqual(['Fabric', 'Size']);
});

test('renders no cards and no collapse toggle when the value is empty', () => {
    renderComponent([]);

    expect(screen.queryByText('General')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.collapse_all')).not.toBeInTheDocument();
});

test('shows the attribute count per group', () => {
    renderComponent();

    expect(screen.getByText('sulu_admin.attribute_count:{"count":2}')).toBeInTheDocument();
});

test('skips an id whose attribute no longer resolves', () => {
    renderComponent([...VALUE, {id: 'gone', required: false, variantSpecific: false}]);

    expect(screen.queryByText('gone')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.attribute_count:{"count":2}')).toBeInTheDocument();
});

test('toggling required emits the updated value', async() => {
    const handleChange = jest.fn();

    renderComponent(VALUE, handleChange);
    await expandAllCards();

    await userEvent.click(within(getRow('Size')).getAllByRole('checkbox')[0]);

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'a1', required: true, variantSpecific: true},
        {id: 'a2', required: true, variantSpecific: false},
    ]);
});

test('toggling variant emits the updated value', async() => {
    const handleChange = jest.fn();

    renderComponent(VALUE, handleChange);
    await expandAllCards();

    await userEvent.click(within(getRow('Size')).getAllByRole('checkbox')[1]);

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'a1', required: false, variantSpecific: false},
        {id: 'a2', required: true, variantSpecific: false},
    ]);
});

test('removing a row emits the value without it', async() => {
    const handleChange = jest.fn();

    renderComponent(VALUE, handleChange);
    await expandAllCards();

    await userEvent.click(within(getRow('Size')).getByRole('button', {name: 'sulu_admin.delete'}));

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'a2', required: true, variantSpecific: false},
    ]);
});

test('removing a group removes every resolved entry in it and keeps unresolved ids', async() => {
    const handleChange = jest.fn();

    renderComponent([...VALUE, {id: 'gone', required: false, variantSpecific: false}], handleChange);

    await userEvent.click(within(getCardHeader('General')).getByRole('button', {name: 'sulu_admin.delete'}));

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'gone', required: false, variantSpecific: false},
    ]);
});

test('confirming the overlay replaces the selection, keeping flags of entries already present', async() => {
    const handleChange = jest.fn();

    const {store} = renderControlled(VALUE, handleChange);

    await userEvent.click(screen.getByText('sulu_admin.choose_attributes'));
    await userEvent.click(screen.getByText('confirm-overlay'));

    expect(store.set).toHaveBeenCalledWith(mockOverlayItems);
    expect(store.loadItems).toHaveBeenCalledTimes(0);
    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a1', required: false, variantSpecific: true},
        {id: 'a4', required: false, variantSpecific: false},
    ]);
});

test('renders the overlay rows without a reload', async() => {
    const {store} = renderControlled();

    await userEvent.click(screen.getByText('sulu_admin.choose_attributes'));
    await userEvent.click(screen.getByText('confirm-overlay'));
    await expandAllCards();

    expect(screen.queryByText('Marketing')).not.toBeInTheDocument();
    expect(within(getCard('General')).getByText('Colour')).toBeInTheDocument();
    expect(store.loadItems).not.toHaveBeenCalled();
});

function rerenderWithValue(rerender, value) {
    rerender(
        <ProductFamilyAttributes
            {...formProps}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={value}
        />
    );
}

test('loads the attributes when the value receives an id the store has not been asked for', () => {
    const {rerender, store} = renderComponent();

    rerenderWithValue(rerender, [...VALUE, {id: 'a9', required: false, variantSpecific: false}]);

    expect(store.loadItems).toHaveBeenCalledWith(['a3', 'a1', 'a2', 'a9']);
});

test('does not reload when the value only loses entries or changes flags', () => {
    const {rerender, store} = renderComponent();

    rerenderWithValue(rerender, [
        {id: 'a3', required: true, variantSpecific: false},
        {id: 'a1', required: false, variantSpecific: true},
    ]);

    expect(store.loadItems).not.toHaveBeenCalled();
});

test('does not reload again for an id the resource did not return', () => {
    const {rerender, store} = renderComponent();
    const value = [...VALUE, {id: 'gone', required: false, variantSpecific: false}];

    rerenderWithValue(rerender, value);
    rerenderWithValue(rerender, value.map((entry) => ({...entry, required: true})));

    expect(store.loadItems).toHaveBeenCalledTimes(1);
});
