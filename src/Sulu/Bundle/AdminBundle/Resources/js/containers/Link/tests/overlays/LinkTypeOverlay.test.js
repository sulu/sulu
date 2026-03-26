// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Form from '../../../../components/Form';
import Input from '../../../../components/Input';
import SingleSelect from '../../../../components/SingleSelect';
import SingleSelection from '../../../SingleSelection';
import LinkTypeOverlay from '../../overlays/LinkTypeOverlay';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/Dialog', () => jest.fn(({children}) => <div>{children}</div>));
jest.mock('../../../../components/Form', () => {
    const FormMock: any = jest.fn(({children}) => <div>{children}</div>);
    FormMock.Field = jest.fn(({children}) => <div>{children}</div>);

    return FormMock;
});
jest.mock('../../../../components/Input', () => jest.fn(() => null));
jest.mock('../../../../components/SingleSelect', () => {
    const SingleSelectMock: any = jest.fn(({children}) => <div>{children}</div>);
    SingleSelectMock.Option = jest.fn(() => null);

    return SingleSelectMock;
});
jest.mock('../../../SingleSelection', () => jest.fn(() => null));

const options = {
    displayProperties: ['title'],
    emptyText: 'No page selected',
    icon: 'su-document',
    listAdapter: 'column_list',
    overlayTitle: 'Choose page',
    resourceKey: 'pages',
    targets: ['_blank', '_self', '_parent', '_top'],
};

function createProps(overrides: Object = {}) {
    return {
        href: undefined,
        onCancel: jest.fn(),
        onConfirm: jest.fn(),
        onHrefChange: jest.fn(),
        open: true,
        options,
        ...overrides,
    };
}

function getLatestSingleSelectionProps() {
    const calls = (SingleSelection: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestInputProps() {
    const calls = (Input: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestSingleSelectProps() {
    const calls = (SingleSelect: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getFormFieldProps(label: string) {
    const fieldCalls = (Form.Field: any).mock.calls
        .map(([props]) => props)
        .reverse();

    const fieldProps = fieldCalls.find((props) => props.label === label);
    if (!fieldProps) {
        throw new Error('Expected Form.Field with label "' + label + '"');
    }

    return fieldProps;
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render overlay with minimal config', () => {
    render(<LinkTypeOverlay {...createProps()} />);

    expect(getFormFieldProps('sulu_admin.link_url').required).toEqual(true);
    expect(getLatestSingleSelectionProps()).toEqual(expect.objectContaining({
        adapter: 'column_list',
        displayProperties: ['title'],
        emptyText: 'No page selected',
        icon: 'su-document',
        listKey: 'pages',
        onChange: expect.any(Function),
        overlayTitle: 'Choose page',
        resourceKey: 'pages',
        value: undefined,
    }));
});

test('Render overlay without options', () => {
    const linkTypeOverlay = new LinkTypeOverlay(createProps({options: undefined}));

    expect(() => linkTypeOverlay.render())
        .toThrow('The LinkTypeOverlay needs some options in order to work!');
});

test('Render overlay with query enabled', () => {
    const onQueryChange = jest.fn();
    render(<LinkTypeOverlay {...createProps({onQueryChange})} />);

    expect(getFormFieldProps('sulu_admin.link_query')).toBeDefined();
    expect(getLatestInputProps().onChange).toBe(onQueryChange);
});

test('Render overlay with anchor enabled', () => {
    const onAnchorChange = jest.fn();
    render(<LinkTypeOverlay {...createProps({onAnchorChange})} />);

    expect(getFormFieldProps('sulu_admin.link_anchor')).toBeDefined();
    expect(getLatestInputProps().onChange).toBe(onAnchorChange);
});

test('Render overlay with target enabled', () => {
    const onTargetChange = jest.fn();
    render(<LinkTypeOverlay {...createProps({onTargetChange})} />);

    expect(getFormFieldProps('sulu_admin.link_target').required).toEqual(true);
    expect(getLatestSingleSelectProps().onChange).toBe(onTargetChange);
    expect((SingleSelect.Option: any).mock.calls.map(([props]) => props.value))
        .toEqual(['_blank', '_self', '_parent', '_top']);
});

test('Render overlay with title enabled', () => {
    const onTitleChange = jest.fn();
    render(<LinkTypeOverlay {...createProps({onTitleChange})} />);

    expect(getFormFieldProps('sulu_admin.link_title')).toBeDefined();
    expect(getLatestInputProps().onChange).toBe(onTitleChange);
});
