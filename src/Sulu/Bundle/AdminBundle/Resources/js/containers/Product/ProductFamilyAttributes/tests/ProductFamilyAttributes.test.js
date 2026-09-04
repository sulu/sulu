// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import ProductFamilyAttributes from '../ProductFamilyAttributes';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key, parameters) => parameters ? key + ':' + JSON.stringify(parameters) : key),
}));

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(function() {
    mockExtendObservable(this, {items: [], loading: false});
    // eslint-disable-next-line testing-library/prefer-explicit-assert -- store method, not a query
    this.getById = jest.fn((id) => this.items.find((item) => item.id === id));
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

function renderComponent(value = VALUE, onChange = jest.fn()) {
    const view = render(
        <ProductFamilyAttributes locale={observable.box('en')} onChange={onChange} value={value} />
    );
    const MultiSelectionStore = require('../../../../stores/MultiSelectionStore');
    // $FlowFixMe
    const store = MultiSelectionStore.mock.instances[0];
    store.items = ITEMS;

    return {...view, store};
}

function getRow(name: string) {
    // $FlowFixMe
    return screen.getByText(name).closest('tr');
}

function getCard(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('section');
}

test('requests the fields needed to resolve names and groups', () => {
    renderComponent();

    const MultiSelectionStore = require('../../../../stores/MultiSelectionStore');

    expect(MultiSelectionStore).toHaveBeenCalledWith(
        'attributes',
        ['a3', 'a1', 'a2'],
        expect.anything(),
        'ids',
        {fields: 'id,name,group,groupName,position'}
    );
});

test('orders group cards alphabetically by group name', () => {
    renderComponent();

    const headings = screen.getAllByText(/^(General|Marketing)$/).map((node) => node.textContent);

    expect(headings).toEqual(['General', 'Marketing']);
});

test('orders attributes inside a group by position', () => {
    renderComponent();

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

    expect(screen.getByText('sulu_product.attribute_count:{"count":2}')).toBeInTheDocument();
});

test('skips an id whose attribute no longer resolves', () => {
    renderComponent([...VALUE, {id: 'gone', required: false, variantSpecific: false}]);

    expect(screen.queryByText('gone')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_product.attribute_count:{"count":2}')).toBeInTheDocument();
});

test('toggling required emits the updated value', async() => {
    const handleChange = jest.fn();

    renderComponent(VALUE, handleChange);

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

    await userEvent.click(within(getRow('Size')).getByLabelText('su-trash-alt'));

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'a2', required: true, variantSpecific: false},
    ]);
});

test('removing a group removes every resolved entry in it and keeps unresolved ids', async() => {
    const handleChange = jest.fn();

    renderComponent([...VALUE, {id: 'gone', required: false, variantSpecific: false}], handleChange);

    await userEvent.click(within(getCard('General')).getByLabelText('sulu_admin.delete'));

    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a3', required: false, variantSpecific: false},
        {id: 'gone', required: false, variantSpecific: false},
    ]);
});

test('confirming the overlay replaces the selection, keeping flags of entries already present', async() => {
    const handleChange = jest.fn();

    const {store} = renderComponent(VALUE, handleChange);

    await userEvent.click(screen.getByText('sulu_product.add_attributes_overlay_title'));
    await userEvent.click(screen.getByText('confirm-overlay'));

    expect(store.loadItems).toHaveBeenCalledWith(['a1', 'a4']);
    expect(handleChange).toHaveBeenCalledWith([
        {id: 'a1', required: false, variantSpecific: true},
        {id: 'a4', required: false, variantSpecific: false},
    ]);
});
