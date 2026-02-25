// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import Mousetrap from 'mousetrap';
import FieldFilterItem from '../FieldFilterItem';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const createListFieldFilterType = (overrides = {}) => jest.fn(() => ({
    confirm: jest.fn(),
    destroy: jest.fn(),
    getFormNode: jest.fn(() => <div>This is the form node</div>),
    getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
    setValue: jest.fn(),
    ...overrides,
}));

const createProps = (props: Object = {}) => ({
    column: 'salutation',
    filterType: 'text',
    filterTypeParameters: {value: 'Test'},
    label: 'Salutation',
    onChange: jest.fn(),
    onClick: jest.fn(),
    onClose: jest.fn(),
    onDelete: jest.fn(),
    open: true,
    value: 'Test',
    ...props,
});

afterEach(() => {
    Mousetrap.reset();
    jest.clearAllMocks();
});

test('Render FieldFilterItem with a FieldFilterType', () => {
    const setValueSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType({setValue: setValueSpy});

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    render(<FieldFilterItem {...createProps()} />);

    expect(listFieldFilterTypeRegistry.get).toBeCalledWith('text');
    expect(listFieldFilterType).toBeCalledWith(expect.any(Function), {value: 'Test'}, 'Test', {});
    expect(setValueSpy).toBeCalledWith('Test');
    expect(screen.getByRole('button', {name: /Salutation:/})).toBeInTheDocument();
    expect(document.querySelector('.spinner')).toBeInTheDocument();
});

test('Close when esc button is pressed', () => {
    const closeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);
    render(<FieldFilterItem {...createProps({onClose: closeSpy, open: true})} />);

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('Do not close when esc button is pressed if was not opened', () => {
    const closeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);
    render(<FieldFilterItem {...createProps({onClose: closeSpy, open: false})} />);

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
});

test('Close when esc button is pressed if initially was closed but has been opened in the mean time', () => {
    const closeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = render(<FieldFilterItem {...createProps({onClose: closeSpy, open: false})} />);
    rerender(<FieldFilterItem {...createProps({onClose: closeSpy, open: true})} />);

    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalled();
});

test('Do not close when esc button is pressed if initially was opened but has been closed already', () => {
    const closeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = render(<FieldFilterItem {...createProps({onClose: closeSpy, open: true})} />);
    rerender(<FieldFilterItem {...createProps({onClose: closeSpy, open: false})} />);

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toBeCalled();
});

test('Change when enter button is pressed', () => {
    const changeSpy = jest.fn();
    const confirmSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType({confirm: confirmSpy});

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);
    render(<FieldFilterItem {...createProps({onChange: changeSpy, open: true})} />);

    expect(changeSpy).not.toBeCalled();
    Mousetrap.trigger('enter');
    expect(confirmSpy).toBeCalledWith();
    expect(changeSpy).toBeCalledWith('salutation', 'Test');
});

test('Do not change when enter button is pressed if was not opened', () => {
    const changeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);
    render(<FieldFilterItem {...createProps({onChange: changeSpy, open: false})} />);

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toBeCalled();
});

test('Change when enter button is pressed if initially was closed but has been opened in the mean time', () => {
    const changeSpy = jest.fn();
    const confirmSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType({confirm: confirmSpy});

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = render(<FieldFilterItem {...createProps({onChange: changeSpy, open: false})} />);
    rerender(<FieldFilterItem {...createProps({onChange: changeSpy, open: true})} />);

    Mousetrap.trigger('enter');
    expect(confirmSpy).toBeCalledWith();
    expect(changeSpy).toBeCalledWith('salutation', 'Test');
});

test('Do not change when enter button is pressed if initially was opened but has been closed already', () => {
    const changeSpy = jest.fn();
    const listFieldFilterType = createListFieldFilterType();

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = render(<FieldFilterItem {...createProps({onChange: changeSpy, open: true})} />);
    rerender(<FieldFilterItem {...createProps({onChange: changeSpy, open: false})} />);

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toBeCalled();
});
