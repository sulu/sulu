// @flow
import BooleanFieldFilterType from '../../fieldFilterTypes/BooleanFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test.each([
    [true],
    [false],
    [undefined],
])('Return form node with a value of "%s"', (value) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, value);
    const formNode = booleanFieldFilterType.getFormNode();

    expect(formNode.props.checked).toEqual(value || false);
    expect(formNode.props.onChange).toEqual(expect.any(Function));
});

test('Return form node with value set by setValue', () => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, false);
    booleanFieldFilterType.setValue(true);

    expect(booleanFieldFilterType.getFormNode().props.checked).toEqual(true);
});

test('Call onChange handler with false as a default value if undefined is given', () => {
    const changeSpy = jest.fn();
    new BooleanFieldFilterType(changeSpy, {}, undefined);

    expect(changeSpy).toBeCalledWith(false);
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const booleanFieldFilterType = new BooleanFieldFilterType(changeSpy, {}, false);
    const formNode: any = booleanFieldFilterType.getFormNode();

    formNode.props.onChange(true);

    expect(changeSpy).toBeCalledWith(true);
});

test.each([
    [true, 'sulu_admin.yes'],
    [false, 'sulu_admin.no'],
    [undefined, null],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = booleanFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
