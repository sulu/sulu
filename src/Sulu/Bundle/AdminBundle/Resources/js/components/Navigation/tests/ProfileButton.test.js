//@flow
import {mount} from 'enzyme';
import React from 'react';
import ProfileButton from '../ProfileButton';

test('The component should render with all available props and handle clicks correctly', () => {
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <ProfileButton
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            userImage="http://lorempixel.com/200/200"
            username="John Travolta"
        />
    );
    expect(navigation.render()).toMatchSnapshot();

    navigation.find('button.menuButton').at(0).simulate('click');
    expect(handleProfileClick).toBeCalled();

    navigation.find('button.menuButton').at(2).simulate('click');
    expect(handleLogoutClick).toBeCalled();
});
