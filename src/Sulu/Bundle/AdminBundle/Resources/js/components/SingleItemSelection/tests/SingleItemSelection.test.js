// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import SingleItemSelection from '../SingleItemSelection';

test('Render with given children prop', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(<SingleItemSelection leftButton={leftButton}>Test Item</SingleItemSelection>)).toMatchSnapshot();
});

test('Render in disabled state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection disabled={true} leftButton={leftButton}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in loading state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} loading={true}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render in invalid state', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} valid={false}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render with given onRemove prop', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection leftButton={leftButton} onRemove={jest.fn()}>Test Item</SingleItemSelection>
    )).toMatchSnapshot();
});

test('Render with emptyText if no children have been passed', () => {
    const leftButton = {
        icon: 'su-page',
        onClick: jest.fn(),
    };

    expect(render(
        <SingleItemSelection emptyText="Nothing!" leftButton={leftButton} onRemove={jest.fn()} />
    )).toMatchSnapshot();
});

test('Call onClick callback if button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} />);

    singleItemSelection.find('button').prop('onClick')();

    expect(leftButton.onClick).toBeCalledWith();
});

test('Call onRemove callback if remove button is clicked', () => {
    const leftButton = {
        icon: 'su-document',
        onClick: jest.fn(),
    };

    const removeSpy = jest.fn();
    const singleItemSelection = shallow(<SingleItemSelection leftButton={leftButton} onRemove={removeSpy} />);

    singleItemSelection.find('.button').prop('onClick')();

    expect(leftButton.onClick).toBeCalledWith();
});
