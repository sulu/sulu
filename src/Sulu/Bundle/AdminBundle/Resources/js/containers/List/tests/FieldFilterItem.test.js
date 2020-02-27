// @flow
import React from 'react';
import {mount} from 'enzyme';
import FieldFilterItem from '../FieldFilterItem';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render FieldFilterItem with a FieldFilterType', () => {
    const setValueSpy = jest.fn();

    const listFieldFilterType = jest.fn(() => ({
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <div>This is the form node</div>),
        setValue: setValueSpy,
    }));

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const fieldFilterItem = mount(
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
        />
    );

    expect(fieldFilterItem.render()).toMatchSnapshot();
    expect(listFieldFilterTypeRegistry.get).toBeCalledWith('text');
    expect(listFieldFilterType).toBeCalledWith(expect.any(Function), {value: 'Test'}, 'Test');
    expect(setValueSpy).toBeCalledWith('Test');
});

test('Pass callbacks to correct props', () => {
    const clickSpy = jest.fn();
    const closeSpy = jest.fn();
    const deleteSpy = jest.fn();

    const fieldFilterItem = mount(
        <FieldFilterItem
            column="salutation"
            filterType="text"
            filterTypeParameters={{value: 'Test'}}
            label="Salutation"
            onChange={jest.fn()}
            onClick={clickSpy}
            onClose={closeSpy}
            onDelete={deleteSpy}
            open={true}
            value="Test"
        />
    );

    fieldFilterItem.find('Chip Icon[name="su-times"]').simulate('click');
    expect(deleteSpy).toBeCalledWith('salutation');

    fieldFilterItem.find('Backdrop').prop('onClick')();
    expect(closeSpy).toBeCalledWith();

    fieldFilterItem.find('Chip button').simulate('click');
    expect(clickSpy).toBeCalledWith('salutation');
});

test('Update value and reset when FieldFilterItem is closed without confirming', () => {
    const changeSpy = jest.fn();
    const setValueSpy = jest.fn();

    const listFieldFilterType = jest.fn((onChange) => ({
        onChange,
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <input id="test-input" onChange={onChange} />),
        setValue: setValueSpy,
    }));

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const fieldFilterItem = mount(
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

    fieldFilterItem.find('#test-input').prop('onChange')('test-value');
    expect(setValueSpy).toBeCalledWith('test-value');

    setValueSpy.mockReset();
    fieldFilterItem.setProps({open: false});
    fieldFilterItem.setProps({open: true});

    expect(changeSpy).not.toBeCalledWith('salutation', 'test-value');
    expect(setValueSpy).toBeCalledWith('Test');
});

test('Update value and call onChange when FieldFilterItem is confirmed', () => {
    const changeSpy = jest.fn();
    const setValueSpy = jest.fn();

    const listFieldFilterType = jest.fn((onChange) => ({
        onChange,
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <input id="test-input" onChange={onChange} />),
        setValue: setValueSpy,
    }));

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    const fieldFilterItem = mount(
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

    fieldFilterItem.find('#test-input').prop('onChange')('test-value');

    fieldFilterItem.find('Button').prop('onClick')();

    expect(changeSpy).toBeCalledWith('salutation', 'test-value');
    expect(setValueSpy).toBeCalledWith('test-value');
});

test('Return correct value node when value changes', () => {
    const promise1 = Promise.resolve('First promise');
    const promise2 = Promise.resolve('Second promise');
    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getValueNode = jest.fn().mockReturnValueOnce(promise1).mockReturnValueOnce(promise2);
        getFormNode = jest.fn(() => <div>This is the form node</div>);
        setValue = jest.fn();
    });

    const fieldFilterItem = mount(
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
            value="Test"
        />
    );

    return promise1.then(() => {
        expect(fieldFilterItem.find('Chip').text()).toEqual('Salutation: First promise');

        fieldFilterItem.setProps({value: 'Test 2'});

        return promise2.then(() => {
            expect(fieldFilterItem.find('Chip').text()).toEqual('Salutation: Second promise');
        });
    });
});

test('Call disposers when unmounted', () => {
    const fieldFilterItem = mount(
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
            value={undefined}
        />
    );

    const valueNodeDisposer = jest.fn();
    fieldFilterItem.instance().valueNodeDisposer = valueNodeDisposer;

    fieldFilterItem.unmount();

    expect(valueNodeDisposer).toBeCalledWith();
});
