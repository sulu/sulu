/* eslint-disable testing-library/prefer-user-event */
// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import React from 'react';
import ResourceLocator from '../ResourceLocator';

function getInput() {
    return screen.getByRole('textbox');
}

function getFixedPrefix(container) {
    const fixedPrefix = container.querySelector('.fixed');

    if (!fixedPrefix) {
        throw new Error('Expected fixed prefix');
    }

    return fixedPrefix;
}

function replaceInputValue(value: string) {
    fireEvent.change(getInput(), {target: {value}});
}

test('ResourceLocator should render with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');
    const {asFragment} = render(
        <ResourceLocator locale={locale} mode="tree_full_edit" onBlur={jest.fn()} onChange={onChange} value={value} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type full and a value of undefined', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');
    const {asFragment} = render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const {asFragment} = render(
        <ResourceLocator locale={locale} mode="tree_leaf_edit" onBlur={jest.fn()} onChange={onChange} value={value} />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf and a value of undefined', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');
    const {asFragment} = render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render when disabled', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');
    const {asFragment} = render(
        <ResourceLocator
            disabled={true}
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should update the split leaf representation when value changes', () => {
    const locale = observable.box('en');
    const onChange = jest.fn();
    const {container, rerender} = render(
        <ResourceLocator locale={locale} mode="tree_leaf_edit" onBlur={jest.fn()} onChange={onChange} value="/child" />
    );

    expect(getFixedPrefix(container)).toHaveTextContent('/');
    expect(getInput().value).toEqual('child');

    rerender(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value="/child/test"
        />
    );
    expect(getFixedPrefix(container)).toHaveTextContent('/child/');
    expect(getInput().value).toEqual('test');
});

test('ResourceLocator should call the onChange callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});

test('ResourceLocator should call the onChange callback and replace a typed slash with a dash in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('child/');
    expect(onChange).toHaveBeenCalledWith('/parent/child-');
});

test('ResourceLocator should call the onChange callback and replace a typed space with a dash in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('child test child');
    expect(onChange).toHaveBeenCalledWith('/parent/child-test-child');
});

test('ResourceLocator should call the onChange callback and replace a typed space with a dash in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/child test child');
    expect(onChange).toHaveBeenCalledWith('/parent/child-test-child');
});

test('ResourceLocator should call the onChange callback and replace multiple slashes with one in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent///child//test/child');
    expect(onChange).toHaveBeenCalledWith('/parent/child/test/child');
});

test('ResourceLocator should call the onChange callback and replace multiple dashes with one in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('child--- a /// test');
    expect(onChange).toHaveBeenCalledWith('/parent/child-a-test');
});

test('ResourceLocator should call the onChange callback and replace multiple dashes with one in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/child---child--test-child');
    expect(onChange).toHaveBeenCalledWith('/parent/child-child-test-child');
});

test('ResourceLocator should call the onChange callback and replace dash before and after slash in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/-child-/-test-/-child');
    expect(onChange).toHaveBeenCalledWith('/parent/child/test/child');
});

test('ResourceLocator should call the onChange callback and remove dash at the beginning in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('-child');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove dash at the beginning in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('-parent/child');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove special characters in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('c!h"i$&()=$%`l`#+.,d%');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove special characters in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/chi!ld/te"§st/ch;:il%§d%');
    expect(onChange).toHaveBeenCalledWith('/parent/child/test/child');
});

test('ResourceLocator should replace capital letters with lower case in leaf mode before calling onChange', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('CHILD');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should replace capital letters with lower case in full mode before calling onChange', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/CHILD');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should replace capital letters even when given locale is not a valid BCP 47 code', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('de_CH');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('CHILD');
    expect(onChange).toHaveBeenCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback when a slash is typed in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/child/');
    expect(onChange).toHaveBeenCalledWith('/parent/child/');
});

test('ResourceLocator should call the onChange callback with undefined if no input is given', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');
    render(<ResourceLocator locale={locale} mode="tree_leaf_edit" onChange={onChange} value="/url" />);

    replaceInputValue('');
    expect(onChange).toHaveBeenCalledWith(undefined);
});

test('ResourceLocator should call the onChange callback and replace "/" with "-"', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_full_edit"
            onBlur={jest.fn()}
            onChange={onChange}
            value={value}
        />
    );

    replaceInputValue('parent/child/');
    expect(onChange).toHaveBeenCalledWith('/parent/child/');
});

test('ResourceLocator should call the onBlur callback when the Input finishes editing', () => {
    const finishSpy = jest.fn();
    const locale = observable.box('en');
    render(
        <ResourceLocator
            locale={locale}
            mode="tree_leaf_edit"
            onBlur={finishSpy}
            onChange={jest.fn()}
            value="/some/url"
        />
    );

    fireEvent.blur(getInput());
    expect(finishSpy).toHaveBeenCalledWith();
});
