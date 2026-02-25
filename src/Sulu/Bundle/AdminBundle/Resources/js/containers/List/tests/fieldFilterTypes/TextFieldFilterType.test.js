// @flow
import TextFieldFilterType from '../../fieldFilterTypes/TextFieldFilterType';

test('Return form node with value of undefined', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);

    expect(textFieldFilterType.getFormNode().props.value).toEqual(undefined);
    expect(textFieldFilterType.getFormNode().props.onChange).toEqual(expect.any(Function));
    expect(textFieldFilterType.getFormNode().props.inputRef).toEqual(expect.any(Function));
});

test('Return form node with value', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, {eq: 'Filter'});

    expect(textFieldFilterType.getFormNode().props.value).toEqual('Filter');
});

test('Return form node with value set by setValue', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    textFieldFilterType.setValue({eq: 'New value'});

    expect(textFieldFilterType.getFormNode().props.value).toEqual('New value');
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const textFieldFilterType = new TextFieldFilterType(changeSpy, {}, undefined);
    const formNode: any = textFieldFilterType.getFormNode();

    formNode.props.onChange('value', ({}: any));

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
