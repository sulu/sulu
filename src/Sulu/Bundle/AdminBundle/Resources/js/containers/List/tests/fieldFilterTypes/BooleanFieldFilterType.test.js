// @flow
import {render} from '@testing-library/react';
import Toggler from '../../../../components/Toggler';
import BooleanFieldFilterType from '../../fieldFilterTypes/BooleanFieldFilterType';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../components/Toggler', () => jest.fn(() => null));

function getLatestTogglerProps() {
    const calls = (Toggler: any).mock.calls;
    return calls[calls.length - 1][0];
}

test.each([
    [true],
    [false],
    [undefined],
])('Render with a value of "%s"', (value) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, value);
    render(booleanFieldFilterType.getFormNode());
    expect(getLatestTogglerProps().checked).toEqual(value || false);
});

test('Render with value set by setValue', () => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, false);
    booleanFieldFilterType.setValue(true);
    render(booleanFieldFilterType.getFormNode());
    expect(getLatestTogglerProps().checked).toEqual(true);
});

test('Call onChange handler with false as a default value if undefined is given', () => {
    const changeSpy = jest.fn();
    new BooleanFieldFilterType(changeSpy, {}, undefined);

    expect(changeSpy).toBeCalledWith(false);
});

test('Call onChange handler with new value', () => {
    const changeSpy = jest.fn();
    const booleanFieldFilterType = new BooleanFieldFilterType(changeSpy, {}, false);
    render(booleanFieldFilterType.getFormNode());

    getLatestTogglerProps().onChange(true);

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
