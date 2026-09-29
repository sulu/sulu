// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import BooleanFieldFilterType from '../../fieldFilterTypes/BooleanFieldFilterType';

jest.mock('../../../../utils/Translator');

test.each([
    [true],
    [false],
    [undefined],
])('Render with a value of "%s"', (value) => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, value);
    const {asFragment} = render(booleanFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const booleanFieldFilterType = new BooleanFieldFilterType(jest.fn(), {}, false);
    booleanFieldFilterType.setValue(true);
    const {asFragment} = render(booleanFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Call onChange handler with false as a default value if undefined is given', () => {
    const changeSpy = jest.fn();
    new BooleanFieldFilterType(changeSpy, {}, undefined);

    expect(changeSpy).toHaveBeenCalledWith(false);
});

test('Call onChange handler with new value', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const booleanFieldFilterType = new BooleanFieldFilterType(changeSpy, {}, false);

    render(booleanFieldFilterType.getFormNode());

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toHaveBeenCalledWith(true, undefined);
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
