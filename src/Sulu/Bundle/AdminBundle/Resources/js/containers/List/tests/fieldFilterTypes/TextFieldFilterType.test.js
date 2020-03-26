// @flow
import {mount, render} from 'enzyme';
import TextFieldFilterType from '../../fieldFilterTypes/TextFieldFilterType';

test('Render with value of undefined', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    expect(render(textFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Render with value', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, {eq: 'Filter'});
    expect(render(textFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    textFieldFilterType.setValue({eq: 'New value'});
    expect(render(textFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const textFieldFilterType = new TextFieldFilterType(changeSpy, {}, undefined);
    const textFieldFilterTypeForm = mount(textFieldFilterType.getFormNode());

    textFieldFilterTypeForm.find('Input').prop('onChange')('value');

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
