// @flow
import {mount, render} from 'enzyme';
import BooleanFieldFilterType from '../../fieldFilterTypes/BooleanFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test.each([
    [true],
    [false],
    [undefined],
])('Render with a value of "%s"', (value) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, value);
    expect(render(booleanFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, false);
    booleanFieldFilterType.setValue(true);
    expect(render(booleanFieldFilterType.getFormNode())).toMatchSnapshot();
});

test('Call onChange handler with false as a default value if undefined is given', () => {
    const changeSpy = jest.fn();
    new BooleanFieldFilterType(changeSpy, {}, undefined);

    expect(changeSpy).toBeCalledWith(false);
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const booleanFieldFilterType = new BooleanFieldFilterType(changeSpy, {}, false);
    const booleanFieldFilterTypeForm = mount(booleanFieldFilterType.getFormNode());

    booleanFieldFilterTypeForm.find('Toggler').prop('onChange')(true);

    expect(changeSpy).toBeCalledWith(true);
});

test.each([
    [true, 'sulu_admin.yes'],
    [false, 'sulu_admin.no'],
    [undefined, undefined],
])('Return value node with value "%s"', (value, expectedValueNode) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, undefined);

    const valueNodePromise = booleanFieldFilterType.getValueNode(value);

    if (expectedValueNode === undefined) {
        expect(valueNodePromise).toEqual(null);
        return;
    }

    if (!valueNodePromise) {
        throw new Error('The getValueNode function must return a promise!');
    }

    return valueNodePromise.then((valueNode) => {
        expect(valueNode).toEqual(expectedValueNode);
    });
});
