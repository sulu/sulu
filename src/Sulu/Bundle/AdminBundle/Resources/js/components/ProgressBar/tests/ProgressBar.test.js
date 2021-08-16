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

test('The component should render with progress style as default', () => {
    const view = mount(
        <ProgressBar
            max={10}
            value={5}
        />
    );

    expect(view.find('.progressBar').hasClass('progress'));
    expect(view.find('.progressBar').text()).toBe('50%');
});

test('The component should render with success style', () => {
    const view = mount(
        <ProgressBar
            max={10}
            type="success"
            value={10}
        />
    );

    expect(view.find('.progressBar').hasClass('success'));
    expect(view.find('.progressBar').text()).toBe('100%');
});

test('The component should render with warning style', () => {
    const view = mount(
        <ProgressBar
            max={10}
            type="warning"
            value={0}
        />
    );

    expect(view.find('.progressBar').hasClass('warning'));
    expect(view.find('.progressBar').text()).toBe('0%');
});

test('The component should render with error style', () => {
    const view = mount(
        <ProgressBar
            max={10}
            type="error"
            value={3}
        />
    );

    expect(view.find('.progressBar').hasClass('error'));
    expect(view.find('.progressBar').text()).toBe('30%');
});
