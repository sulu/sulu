// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Form, Input} from 'sulu-admin-bundle/components';
import SingleSelect from 'sulu-admin-bundle/components/SingleSelect';
import SingleMediaSelection from '../../../SingleMediaSelection';
import MediaLinkTypeOverlay from '../../overlays/MediaLinkTypeOverlay';

jest.mock('sulu-admin-bundle/components', () => {
    const FormMock: any = jest.fn(({children}) => <div>{children}</div>);
    FormMock.Field = jest.fn(({children}) => <div>{children}</div>);

    return {
        Dialog: jest.fn(({children}) => <div>{children}</div>),
        Form: FormMock,
        Input: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/components/SingleSelect', () => {
    const SingleSelectMock: any = jest.fn(({children}) => <div>{children}</div>);
    SingleSelectMock.Option = jest.fn(() => null);

    return SingleSelectMock;
});

jest.mock('../../../SingleMediaSelection', () => jest.fn(() => null));

const options = {
    resourceKey: 'media',
    displayProperties: ['title'],
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

function getLatestSingleMediaSelectionProps() {
    const calls = (SingleMediaSelection: any).mock.calls;
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
    render(<MediaLinkTypeOverlay {...createProps()} />);

    expect(getFormFieldProps('sulu_admin.link_url').required).toEqual(true);
    expect(getLatestSingleMediaSelectionProps()).toEqual(expect.objectContaining({
        onChange: expect.any(Function),
        value: {displayOption: undefined, id: undefined},
    }));
});

test('Render overlay with invalid href type', () => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(<MediaLinkTypeOverlay {...createProps({href: '1234'})} />))
            .toThrow('The id of a media should always be a number!');
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Render overlay with anchor enabled', () => {
    const onAnchorChange = jest.fn();
    render(<MediaLinkTypeOverlay {...createProps({onAnchorChange})} />);

    expect(getFormFieldProps('sulu_admin.link_anchor')).toBeDefined();
    expect(getLatestInputProps().onChange).toBe(onAnchorChange);
});

test('Render overlay with target enabled', () => {
    const onTargetChange = jest.fn();
    render(<MediaLinkTypeOverlay {...createProps({onTargetChange})} />);

    expect(getFormFieldProps('sulu_admin.link_target').required).toEqual(true);
    expect(getLatestSingleSelectProps().onChange).toBe(onTargetChange);
    expect((SingleSelect.Option: any).mock.calls.map(([props]) => props.value))
        .toEqual(['_blank', '_self', '_parent', '_top']);
});

test('Render overlay with title enabled', () => {
    const onTitleChange = jest.fn();
    render(<MediaLinkTypeOverlay {...createProps({onTitleChange})} />);

    expect(getFormFieldProps('sulu_admin.link_title')).toBeDefined();
    expect(getLatestInputProps().onChange).toBe(onTitleChange);
});

test('Delegate only id to onHrefChange method', () => {
    const hrefChangeSpy = jest.fn();
    render(<MediaLinkTypeOverlay {...createProps({onHrefChange: hrefChangeSpy, onTitleChange: jest.fn()})} />);

    getLatestSingleMediaSelectionProps().onChange({id: 1}, undefined);
    expect(hrefChangeSpy).toBeCalledWith(1, undefined);
});
