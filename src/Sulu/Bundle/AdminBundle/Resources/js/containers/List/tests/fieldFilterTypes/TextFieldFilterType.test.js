// @flow
import {render} from '@testing-library/react';
import Input from '../../../../components/Input';
import TextFieldFilterType from '../../fieldFilterTypes/TextFieldFilterType';

jest.mock('../../../../components/Input', () => jest.fn(() => null));

function getLatestInputProps() {
    const calls = (Input: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Render with value of undefined', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    render(textFieldFilterType.getFormNode());
    expect(getLatestInputProps().value).toBeUndefined();
});

test('Render with value', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, {eq: 'Filter'});
    render(textFieldFilterType.getFormNode());
    expect(getLatestInputProps().value).toEqual('Filter');
});

test('Render with value set by setValue', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    textFieldFilterType.setValue({eq: 'New value'});
    render(textFieldFilterType.getFormNode());
    expect(getLatestInputProps().value).toEqual('New value');
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const textFieldFilterType = new TextFieldFilterType(changeSpy, {}, undefined);
    render(textFieldFilterType.getFormNode());

    getLatestInputProps().onChange('value');

    expect(changeSpy).toBeCalledWith({eq: 'value'});
});

test.each([
    ['Test1'],
    ['Test2'],
])('Return value node with value "%s"', (value) => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = textFieldFilterType.getValueNode({eq: value});

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(value);
    });
});

test('Return value node for null', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = textFieldFilterType.getValueNode(null);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(null);
    });
});
