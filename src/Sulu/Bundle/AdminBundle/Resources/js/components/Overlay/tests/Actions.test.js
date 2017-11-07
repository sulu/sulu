/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Actions from '../Actions';

test('The component should render', () => {
    const actions = [
        {title: 'Action 1', onClick: () => {}},
        {title: 'Action 2', onClick: () => {}},
    ];
    const component = render(<Actions actions={actions} />);
    expect(component).toMatchSnapshot();
});

test('The component should call the corresponding callback when an action is clicked', () => {
    const actions = [
        {title: 'Action 1', onClick: jest.fn()},
        {title: 'Action 2', onClick: jest.fn()},
    ];
    const component = shallow(<Actions actions={actions} />);
    component.find('Button').first().simulate('click');
    expect(actions[0].onClick).toBeCalled();
    expect(actions[1].onClick).not.toBeCalled();
});
