// @flow
import React from 'react';
import {mount} from 'enzyme';
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
    expect(listFieldFilterTypeRegistry.get).toHaveBeenCalledWith('text');
    expect(listFieldFilterType).toHaveBeenCalledWith(expect.any(Function), {value: 'Test'}, 'Test', {});
    expect(setValueSpy).toHaveBeenCalledWith('Test');
});

test('Close when esc button is pressed', () => {
    const closeSpy = jest.fn();

    mount(
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

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('Do not close when esc button is pressed if was not opened', () => {
    const closeSpy = jest.fn();

    mount(
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

test('Close when esc button is pressed if initially was closed but has been opened in the mean time', () => {
    const closeSpy = jest.fn();

    const fieldFilterItem = mount(
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

    fieldFilterItem.setProps({open: true});

    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalled();
});

test('Do not close when esc button is pressed if initially was opened but has been closed already', () => {
    const closeSpy = jest.fn();

    const fieldFilterItem = mount(
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

    fieldFilterItem.setProps({open: false});

    Mousetrap.trigger('esc');
    expect(closeSpy).not.toHaveBeenCalled();
});

test('Change when enter button is pressed', () => {
    const changeSpy = jest.fn();

    const listFieldFilterType = jest.fn(() => ({
        confirm: jest.fn(),
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <div>This is the form node</div>),
        setValue: jest.fn(),
    }));

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    mount(
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

    expect(changeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('enter');
    expect(changeSpy).toHaveBeenCalled();
});

test('Do not change when enter button is pressed if was not opened', () => {
    const changeSpy = jest.fn();

    const listFieldFilterType = jest.fn(() => ({
        confirm: jest.fn(),
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <div>This is the form node</div>),
        setValue: jest.fn(),
    }));

    listFieldFilterTypeRegistry.get.mockReturnValue(listFieldFilterType);

    mount(
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

test('Change when enter button is pressed if initially was closed but has been opened in the mean time', () => {
    const changeSpy = jest.fn();

    const listFieldFilterType = jest.fn(() => ({
        confirm: jest.fn(),
        getValueNode: jest.fn((value) => Promise.resolve('The value is ' + value)),
        getFormNode: jest.fn(() => <div>This is the form node</div>),
        setValue: jest.fn(),
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
            open={false}
            value="Test"
        />
    );

    fieldFilterItem.setProps({open: true});

    Mousetrap.trigger('enter');
    expect(changeSpy).toHaveBeenCalled();
});

test('Do not change when enter button is pressed if initially was opened but has been closed already', () => {
    const changeSpy = jest.fn();

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

    fieldFilterItem.setProps({open: false});

    Mousetrap.trigger('enter');
    expect(changeSpy).not.toHaveBeenCalled();
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
    expect(deleteSpy).toHaveBeenCalledWith('salutation');

    fieldFilterItem.find('Backdrop').prop('onClick')();
    expect(closeSpy).toHaveBeenCalledWith();

    fieldFilterItem.find('Chip button').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith('salutation');
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
    expect(setValueSpy).toHaveBeenCalledWith('test-value');

    setValueSpy.mockReset();
    fieldFilterItem.setProps({open: false});
    fieldFilterItem.setProps({open: true});

    expect(changeSpy).not.toHaveBeenCalledWith('salutation', 'test-value');
    expect(setValueSpy).toHaveBeenCalledWith('Test');
});

test('Update value and call onChange when FieldFilterItem is confirmed', () => {
    const changeSpy = jest.fn();
    const confirmSpy = jest.fn();
    const setValueSpy = jest.fn();

    const listFieldFilterType = jest.fn((onChange) => ({
        onChange,
        confirm: confirmSpy,
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

    expect(changeSpy).toHaveBeenCalledWith('salutation', 'test-value');
    expect(confirmSpy).toHaveBeenCalledWith();
    expect(setValueSpy).toHaveBeenCalledWith('test-value');
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
    listFieldFilterTypeRegistry.get.mockReturnValue(class {
        getValueNode = jest.fn((value) => Promise.resolve('The value is ' + value));
        getFormNode = jest.fn(() => <div>This is the form node</div>);
        setValue = jest.fn();
        destroy = jest.fn();
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
            value={undefined}
        />
    );

    const valueNodeDisposer = jest.fn();
    const valueDisposer = jest.fn();
    const fieldFilterTypeDestroyer = jest.fn();
    fieldFilterItem.instance().valueNodeDisposer = valueNodeDisposer;
    fieldFilterItem.instance().valueDisposer = valueDisposer;
    fieldFilterItem.instance().fieldFilterType.destroy = fieldFilterTypeDestroyer;

    fieldFilterItem.unmount();

    expect(valueNodeDisposer).toHaveBeenCalledWith();
    expect(valueDisposer).toHaveBeenCalledWith();
    expect(fieldFilterTypeDestroyer).toHaveBeenCalled();
});
