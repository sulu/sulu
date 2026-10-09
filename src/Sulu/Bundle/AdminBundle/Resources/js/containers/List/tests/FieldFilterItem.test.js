// @flow
import React from 'react';
import {autorun} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import FieldFilterItem from '../FieldFilterItem';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('mobx', () => {
    const actualMobx = jest.requireActual('mobx');

    return {
        ...actualMobx,
        autorun: jest.fn(actualMobx.autorun),
    };
});

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator');

class FilterInput extends React.Component<Object> {
    handleChange = (event) => {
        this.props.onChange(event.currentTarget.value);
    };

    render() {
        return <input aria-label="filter-input" onChange={this.handleChange} />;
    }
}

function createFilterType(overrides: Object = {}) {
    const setValue = overrides.setValue || jest.fn();
    const confirm = overrides.confirm || jest.fn();
    const destroy = overrides.destroy || jest.fn();
    const getValueNode = overrides.getValueNode || jest.fn((value) => Promise.resolve('The value is ' + value));
    const getFormNodeFactory = overrides.getFormNode || jest.fn(() => <div>This is the form node</div>);

    return jest.fn((onChange, filterTypeParameters, value, options) => ({
        confirm,
        destroy,
        getFormNode: jest.fn(() => getFormNodeFactory(onChange)),
        getValueNode,
        onChange,
        options,
        setValue,
        value,
    }));
}

function renderFieldFilterItem(props: Object = {}) {
    return render(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
            {...props}
        />
    );
}

beforeEach(() => {
    jest.clearAllMocks();
    (autorun: any).mockImplementation(jest.requireActual('mobx').autorun);
    listFieldFilterTypeRegistry.get.mockReturnValue(createFilterType());
    listFieldFilterTypeRegistry.getOptions.mockReturnValue({});
});

test('Render FieldFilterItem with a FieldFilterType', async() => {
    const setValueSpy = jest.fn();
    const listFieldFilterType = createFilterType({setValue: setValueSpy});
    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    renderFieldFilterItem();

    expect(screen.getByText('This is the form node')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /Salutation:/})).toBeInTheDocument();
    expect(listFieldFilterTypeRegistry.get).toHaveBeenCalledWith('text');
    expect(listFieldFilterType).toHaveBeenCalledWith(expect.any(Function), {value: 'Test'}, 'Test', {});
    expect(setValueSpy).toHaveBeenCalledWith('Test');
    expect(await screen.findByText(/The value is Test/)).toBeInTheDocument();
});

test('Close when esc button is pressed', () => {
    const closeSpy = jest.fn();

    renderFieldFilterItem({onClose: closeSpy});

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('Do not close when esc button is pressed if was not opened', () => {
    const closeSpy = jest.fn();

    renderFieldFilterItem({onClose: closeSpy, open: false});

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toHaveBeenCalled();
});

test('Close when esc button is pressed if initially was closed but has been opened in the mean time', () => {
    const closeSpy = jest.fn();
    const {rerender} = renderFieldFilterItem({onClose: closeSpy, open: false});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={closeSpy}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('Do not close when esc button is pressed if initially was opened but has been closed already', () => {
    const closeSpy = jest.fn();
    const {rerender} = renderFieldFilterItem({onClose: closeSpy, open: true});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={closeSpy}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toHaveBeenCalled();
});

test('Change when enter button is pressed', () => {
    const changeSpy = jest.fn();
    const listFieldFilterType = createFilterType();
    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    renderFieldFilterItem({onChange: changeSpy});

    expect(changeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('enter');
    expect(changeSpy).toHaveBeenCalled();
});

test('Do not change when enter button is pressed if was not opened', () => {
    const changeSpy = jest.fn();

    renderFieldFilterItem({onChange: changeSpy, open: false});

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Change when enter button is pressed if initially was closed but has been opened in the mean time', () => {
    const changeSpy = jest.fn();
    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: false});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    Mousetrap.trigger('enter');
    expect(changeSpy).toHaveBeenCalled();
});

test('Do not change when enter button is pressed if initially was opened but has been closed already', () => {
    const changeSpy = jest.fn();
    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: true});

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Pass callbacks to correct props', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();
    const closeSpy = jest.fn();
    const deleteSpy = jest.fn();

    renderFieldFilterItem({
        onClick: clickSpy,
        onClose: closeSpy,
        onDelete: deleteSpy,
    });

    await user.click(screen.getByLabelText('su-times'));
    expect(deleteSpy).toHaveBeenCalledWith('salutation');

    await user.click(screen.getByTestId('backdrop'));
    expect(closeSpy).toHaveBeenCalledWith();

    await user.click(screen.getByRole('button', {name: /Salutation:/}));
    expect(clickSpy).toHaveBeenCalledWith('salutation');
});

test('Update value and reset when FieldFilterItem is closed without confirming', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const setValueSpy = jest.fn();

    const listFieldFilterType = createFilterType({
        getFormNode: jest.fn((onChange) => <FilterInput onChange={onChange} />),
        setValue: setValueSpy,
    });

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = renderFieldFilterItem({onChange: changeSpy, open: true, value: 'Test'});

    await user.type(screen.getByLabelText('filter-input'), 'test-value');
    expect(setValueSpy).toHaveBeenCalledWith('test-value');

    setValueSpy.mockReset();

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={false}
            value="Test"
        />
    );
    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={changeSpy}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test"
        />
    );

    expect(changeSpy).not.toHaveBeenCalledWith('salutation', 'test-value');
    expect(setValueSpy).toHaveBeenCalledWith('Test');
});

test('Update value and call onChange when FieldFilterItem is confirmed', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const confirmSpy = jest.fn();
    const setValueSpy = jest.fn();

    const listFieldFilterType = createFilterType({
        confirm: confirmSpy,
        getFormNode: jest.fn((onChange) => <FilterInput onChange={onChange} />),
        setValue: setValueSpy,
    });

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    renderFieldFilterItem({onChange: changeSpy, value: 'Test'});

    await user.type(screen.getByLabelText('filter-input'), 'test-value');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(changeSpy).toHaveBeenCalledWith('salutation', 'test-value');
    expect(confirmSpy).toHaveBeenCalledWith();
    expect(setValueSpy).toHaveBeenCalledWith('test-value');
});

test('Return correct value node when value changes', async() => {
    const promise1 = Promise.resolve('First promise');
    const promise2 = Promise.resolve('Second promise');
    const getValueNode = jest.fn().mockReturnValueOnce(promise1).mockReturnValueOnce(promise2);
    const listFieldFilterType = createFilterType({
        getValueNode,
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {rerender} = renderFieldFilterItem({
        filterTypeParameters: null,
        value: 'Test',
    });

    expect(await screen.findByText(/First promise/)).toBeInTheDocument();

    rerender(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={null}
            label="Salutation"
            onChange={jest.fn()}
            onClick={jest.fn()}
            onClose={jest.fn()}
            onDelete={jest.fn()}
            open={true}
            value="Test 2"
        />
    );

    expect(await screen.findByText(/Second promise/)).toBeInTheDocument();
});

test('Call disposers when unmounted', () => {
    const valueNodeDisposer = jest.fn();
    const valueDisposer = jest.fn();
    const fieldFilterTypeDestroyer = jest.fn();
    (autorun: any)
        .mockImplementationOnce((callback) => {
            callback();
            return valueDisposer;
        })
        .mockImplementationOnce((callback) => {
            callback();
            return valueNodeDisposer;
        });

    const listFieldFilterType = createFilterType({
        destroy: fieldFilterTypeDestroyer,
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
    });
    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const {unmount} = renderFieldFilterItem({
        filterTypeParameters: null,
        value: undefined,
    });

    unmount();

    expect(valueNodeDisposer).toHaveBeenCalledWith();
    expect(valueDisposer).toHaveBeenCalledWith();
    expect(fieldFilterTypeDestroyer).toHaveBeenCalled();
});
