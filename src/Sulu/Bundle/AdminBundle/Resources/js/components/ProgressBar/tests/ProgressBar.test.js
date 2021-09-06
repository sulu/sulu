// @flow
import {render, mount} from 'enzyme';
import React from 'react';
import ProgressBar from '../ProgressBar';

test('The component should render', () => {
    const view = render(
        <ProgressBar
            max={10}
            value={7}
        />
    );

    expect(view).toMatchSnapshot();
});

test('The component should render with progress skin as default', () => {
    const view = mount(
        <ProgressBar
            max={10}
            value={5}
        />
    );

    expect(view.find('.progressBar').prop('max')).toBe(10);
    expect(view.find('.progressBar').prop('value')).toBe(5);
    expect(view.find('.progressBar').hasClass('progress'));
    expect(view.find('.progressBar').text()).toBe('50%');
});

test('The component should render with success skin', () => {
    const view = mount(
        <ProgressBar
            max={10}
            skin="success"
            value={10}
        />
    );

    expect(view.find('.progressBar').prop('max')).toBe(10);
    expect(view.find('.progressBar').prop('value')).toBe(10);
    expect(view.find('.progressBar').hasClass('success'));
    expect(view.find('.progressBar').text()).toBe('100%');
});

test('The component should render with warning skin', () => {
    const view = mount(
        <ProgressBar
            max={10}
            skin="warning"
            value={0}
        />
    );

    expect(view.find('.progressBar').prop('max')).toBe(10);
    expect(view.find('.progressBar').prop('value')).toBe(0);
    expect(view.find('.progressBar').hasClass('warning'));
    expect(view.find('.progressBar').text()).toBe('0%');
});

test('The component should render with error skin', () => {
    const view = mount(
        <ProgressBar
            max={10}
            skin="error"
            value={3}
        />
    );

    expect(view.find('.progressBar').prop('max')).toBe(10);
    expect(view.find('.progressBar').prop('value')).toBe(3);
    expect(view.find('.progressBar').hasClass('error'));
    expect(view.find('.progressBar').text()).toBe('30%');
});

test('The component should render with max 0', () => {
    const view = mount(
        <ProgressBar
            max={0}
            skin="error"
            value={0}
        />
    );

    expect(view.find('.progressBar').prop('max')).toBe(1);
    expect(view.find('.progressBar').prop('value')).toBe(0);
    expect(view.find('.progressBar').hasClass('error'));
    expect(view.find('.progressBar').text()).toBe('0%');
});
