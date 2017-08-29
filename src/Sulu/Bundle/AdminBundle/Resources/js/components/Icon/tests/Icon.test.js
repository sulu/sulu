/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Icon from '../Icon';

test('Icon should render', () => {
    expect(render(<Icon name="save" />)).toMatchSnapshot();
});

test('Icon should render with class names', () => {
    expect(render(<Icon className="test" name="edit" />)).toMatchSnapshot();
});

test('Icon should call the callback on click', () => {
    const onClick = jest.fn();
    const icon = shallow(<Icon className="test" name="edit" onClick={onClick} />);
    icon.simulate('click');
    expect(onClick).toBeCalled();
});
