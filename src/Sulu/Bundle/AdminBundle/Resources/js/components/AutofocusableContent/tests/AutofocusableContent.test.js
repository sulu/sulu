// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import AutofocusableContent from '../AutofocusableContent';

jest.mock('../../../utils/DOM/afterElementsRendered', () => jest.fn((callback) => callback()));

test('The component should focus the first input when it mounts', () => {
    render(
        <AutofocusableContent>
            <input data-testid="first-input" type="text" />
            <input data-testid="second-input" type="text" />
        </AutofocusableContent>
    );

    const firstInput = screen.getByTestId('first-input');
    expect(firstInput).toHaveFocus();
});

test('The component should focus the first focusable element (textarea) when it comes before other inputs', () => {
    render(
        <AutofocusableContent>
            <textarea data-testid="first-field" />
            <input data-testid="second-input" type="text" />
        </AutofocusableContent>
    );

    const firstField = screen.getByTestId('first-field');
    expect(firstField).toHaveFocus();
});

test('The component should focus the first focusable element (select) when it is the only focusable', () => {
    render(
        <AutofocusableContent>
            <select data-testid="first-select">
                <option value="a">A</option>
            </select>
        </AutofocusableContent>
    );

    const firstSelect = screen.getByTestId('first-select');
    expect(firstSelect).toHaveFocus();
});

test('The component should not focus hidden or disabled inputs', () => {
    render(
        <AutofocusableContent>
            <input data-testid="hidden-input" type="hidden" />
            <input data-testid="disabled-input" type="text" disabled />
            <input data-testid="focusable-input" type="text" />
        </AutofocusableContent>
    );

    const focusableInput = screen.getByTestId('focusable-input');
    expect(focusableInput).toHaveFocus();
});

test('The component should render children inside an article with the given className', () => {
    render(
        <AutofocusableContent className="my-article-class">
            <span data-testid="child">Content</span>
        </AutofocusableContent>
    );

    const child = screen.getByTestId('child');
    expect(child.closest('article')).toHaveClass('my-article-class');
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
