// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import TextFieldFilterType from '../../fieldFilterTypes/TextFieldFilterType';

test('Render with value of undefined', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    const {asFragment} = render(textFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Render with value', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, {eq: 'Filter'});
    const {asFragment} = render(textFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Render with value set by setValue', () => {
    const textFieldFilterType = new TextFieldFilterType(jest.fn(), {}, undefined);
    textFieldFilterType.setValue({eq: 'New value'});
    const {asFragment} = render(textFieldFilterType.getFormNode());

    expect(asFragment()).toMatchSnapshot();
});

test('Call onChange handler with new value', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const textFieldFilterType = new TextFieldFilterType(changeSpy, {}, undefined);

    render(textFieldFilterType.getFormNode());

    await user.click(screen.getByRole('textbox'));
    await user.paste('value');

    expect(changeSpy).toHaveBeenCalledWith({eq: 'value'});
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
