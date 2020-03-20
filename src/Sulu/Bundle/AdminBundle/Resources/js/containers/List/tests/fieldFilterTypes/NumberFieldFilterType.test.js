// @flow
import {mount, render} from 'enzyme';
import NumberFieldFilterType from '../../fieldFilterTypes/NumberFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test.each([
    [undefined],
    [{eq: 6}],
    [{lt: 2}],
    [{gt: 7}],
])('Render with value of "%s"', (value) => {
    const numberFieldFilterType = new NumberFieldFilterType(jest.fn(), {}, value);
    expect(render(numberFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Call onChange handler with a default operator when undefined is passed', () => {
    const changeSpy = jest.fn();
    new NumberFieldFilterType(changeSpy, {}, undefined);

    expect(changeSpy).toBeCalledWith({eq: undefined});
});

test.each([
    [{eq: 8}, 'lt', {lt: 8}],
    [{lt: 9}, 'gt', {gt: 9}],
    [{gt: 3}, 'eq', {eq: 3}],
])('Call onChange handler when the value was "%s" and the "%s" operator is chosen', (value, operator, newValue) => {
    const changeSpy = jest.fn();
    const numberFieldFilterType = new NumberFieldFilterType(changeSpy, {}, value);
    const numberFieldFilterTypeForm = mount(numberFieldFilterType.getFormNode());

    numberFieldFilterTypeForm.find('SingleSelect').prop('onChange')(operator);

    expect(changeSpy).toBeCalledWith(newValue);
});

test.each([
    [{eq: 8}, 3, {eq: 3}],
    [{lt: 9}, 4, {lt: 4}],
    [{gt: 3}, 9, {gt: 9}],
])('Call onChange handler when the value was "%s" and the number was changed to "%s"', (value, number, newValue) => {
    const changeSpy = jest.fn();
    const numberFieldFilterType = new NumberFieldFilterType(changeSpy, {}, value);
    const numberFieldFilterTypeForm = mount(numberFieldFilterType.getFormNode());

    numberFieldFilterTypeForm.find('Input').prop('onChange')(number);

    expect(changeSpy).toBeCalledWith(newValue);
});

test.each([
    [{eq: 8}, '= 8'],
    [{lt: 3}, '< 3'],
    [{gt: 9}, '> 9'],
    [undefined, ' '],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const numberFieldFilterType = new NumberFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = numberFieldFilterType.getValueNode(value);

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
