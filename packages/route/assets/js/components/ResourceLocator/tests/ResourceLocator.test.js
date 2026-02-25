// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import bindValueToOnChange from 'sulu-admin-bundle/utils/TestHelper/bindValueToOnChange';
import ResourceLocator from '../ResourceLocator';

const createProps = (overrides = {}): any => ({
    locale: observable.box('en'),
    mode: 'tree_full_edit',
    onBlur: jest.fn(),
    onChange: jest.fn(),
    value: '/parent',
    ...overrides,
});

const getInput = () => screen.getByRole('textbox');

const typeValue = async(input, value) => {
    await userEvent.clear(input);

    if (value) {
        await userEvent.type(input, value);
    }
};

const renderBoundResourceLocator = (overrides = {}) => render(
    bindValueToOnChange(<ResourceLocator {...createProps(overrides)} />)
);

test('ResourceLocator should render with type full', () => {
    const {asFragment} = render(<ResourceLocator {...createProps({mode: 'tree_full_edit', value: '/parent'})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type full and a value of undefined', () => {
    const {asFragment} = render(<ResourceLocator {...createProps({mode: 'tree_full_edit', value: undefined})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const {asFragment} = render(<ResourceLocator {...createProps({mode: 'tree_leaf_edit', value: '/parent/child'})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf and a value of undefined', () => {
    const {asFragment} = render(<ResourceLocator {...createProps({mode: 'tree_leaf_edit', value: undefined})} />);

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should render when disabled', () => {
    const {asFragment} = render(<ResourceLocator
        {...createProps({
            disabled: true,
            mode: 'tree_full_edit',
            value: '/parent',
        })}
    />);

    expect(asFragment()).toMatchSnapshot();
});

test('ResourceLocator should update the split leaf representation when value changes', () => {
    const {rerender} = render(
        <ResourceLocator {...createProps({mode: 'tree_leaf_edit', value: '/child'})} />
    );

    expect(screen.getByText('/')).toBeInTheDocument();
    expect(getInput()).toHaveValue('child');

    rerender(<ResourceLocator {...createProps({mode: 'tree_leaf_edit', value: '/child/test'})} />);
    expect(screen.getByText('/child/')).toBeInTheDocument();
    expect(getInput()).toHaveValue('test');
});

test('ResourceLocator should call the onChange callback when the input changes with type full', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent'});

    await typeValue(getInput(), 'parent-new');

    expect(onChange).toHaveBeenLastCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'child-new');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-new');
});

test('ResourceLocator should replace typed slash with dash in leaf mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'child/');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-');
});

test('ResourceLocator should replace typed space with dash in leaf mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'child test child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-test-child');
});

test('ResourceLocator should replace typed space with dash in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/child test child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-test-child');
});

test('ResourceLocator should replace multiple slashes with one in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent///child//test/child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child/test/child');
});

test('ResourceLocator should call the onChange callback and replace multiple dashes with one in leaf mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'child--- a /// test');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-a-test');
});

test('ResourceLocator should call the onChange callback and replace multiple dashes with one in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/child---child--test-child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child-child-test-child');
});

test('ResourceLocator should replace dash before and after slash in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/-child-/-test-/-child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child/test/child');
});

test('ResourceLocator should call the onChange callback and remove dash at the beginning in leaf mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), '-child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove dash at the beginning in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), '-parent/child');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove special characters in leaf mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'c!h"i$&()=$%`l`#+.,d%');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback and remove special characters in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/chi!ld/te"§st/ch;:il%§d%');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child/test/child');
});

test('ResourceLocator should replace capital letters with lower case in leaf mode before calling onChange', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'CHILD');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should replace capital letters with lower case in full mode before calling onChange', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/CHILD');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should replace capitals with invalid locale code', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({
        locale: observable.box('de_CH'),
        mode: 'tree_leaf_edit',
        onChange,
        value: '/parent/child',
    });

    await typeValue(getInput(), 'CHILD');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback when a slash is typed in full mode', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent/child'});

    await typeValue(getInput(), 'parent/child/');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child/');
});

test('ResourceLocator should call the onChange callback with undefined if no input is given', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_leaf_edit', onChange, value: '/url'});

    await typeValue(getInput(), '');

    expect(onChange).toHaveBeenLastCalledWith(undefined);
});

test('ResourceLocator should call the onChange callback and replace "/" with "-"', async() => {
    const onChange = jest.fn();

    renderBoundResourceLocator({mode: 'tree_full_edit', onChange, value: '/parent'});

    await typeValue(getInput(), 'parent/child/');

    expect(onChange).toHaveBeenLastCalledWith('/parent/child/');
});

test('ResourceLocator should call the onBlur callback when the Input finishes editing', async() => {
    const onBlur = jest.fn();

    render(<ResourceLocator {...createProps({mode: 'tree_leaf_edit', onBlur, value: '/some/url'})} />);

    await userEvent.click(getInput());
    await userEvent.tab();

    expect(onBlur).toHaveBeenCalled();
});
