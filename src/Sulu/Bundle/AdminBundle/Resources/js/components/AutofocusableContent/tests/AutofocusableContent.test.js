// @flow
import {render, screen} from '@testing-library/react';
import React, {Fragment} from 'react';
import AutofocusableContent from '../AutofocusableContent';

jest.mock('../../../utils/DOM/afterElementsRendered', () => jest.fn((callback) => callback()));

test.each([
    [
        'the first input when multiple inputs',
        <Fragment>
            <input data-testid="focused-element" type="text" />
            <input type="text" />
        </Fragment>,
    ],
    [
        'the textarea when it comes before other inputs',
        <Fragment>
            <textarea data-testid="focused-element" />
            <input type="text" />
        </Fragment>,
    ],
    [
        'the select when it is the only focusable',
        <select data-testid="focused-element">
            <option value="a">A</option>
        </select>,
    ],
    [
        'the first focusable input, skipping hidden and disabled',
        <Fragment>
            <input data-testid="hidden-input" type="hidden" />
            <input data-testid="disabled-input" type="text" disabled />
            <input data-testid="focused-element" type="text" />
        </Fragment>,
    ],
])('The component should focus %s when it mounts', (description, children) => {
    render(<AutofocusableContent>{children}</AutofocusableContent>);

    expect(screen.getByTestId('focused-element')).toHaveFocus();
});

test('The component should not throw when there is no focusable element', () => {
    expect(() => {
        render(
            <AutofocusableContent>
                <div>No focusable elements here</div>
            </AutofocusableContent>
        );
    }).not.toThrow();
});
