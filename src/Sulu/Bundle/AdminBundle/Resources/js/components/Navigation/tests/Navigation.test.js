//@flow
import {mount} from 'enzyme';
import React from 'react';
import Navigation from '../Navigation';

test('The component should render and handle clicks correctly', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            onItemClick={jest.fn()}
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            title="sulu.io"
            username="John Terence Maximilian Travolta"
        >
            <Navigation.Item icon="su-search" onClick={handleNavigationClick} title="Search" value="search" />
            <Navigation.Item icon="fa-bullseye" onClick={handleNavigationClick} title="Webspaces" value="webspaces" />
        </Navigation>
    );
    expect(navigation.render()).toMatchSnapshot();

    navigation.find('.userProfile button').at(1).simulate('click');
    expect(handleLogoutClick).toBeCalled();

    navigation.find('.noUserImage').simulate('click');
    navigation.find('.userProfile button').at(0).simulate('click');
    expect(handleProfileClick).toHaveBeenCalledTimes(2);
});

test('The component should render with all available props and handle clicks correctly', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handlePinClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            appVersion="1.0.0"
            appVersionLink="http://link.com"
            onItemClick={handleNavigationClick}
            onLogoutClick={handleLogoutClick}
            onPinToggle={handlePinClick}
            onProfileClick={handleProfileClick}
            pinned={false}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            title="sulu.io"
            userImage="http://lorempixel.com/200/200"
            username="John Travolta"
        >
            <Navigation.Item icon="su-search" title="Search" value="search" />
            <Navigation.Item icon="su-webspace" title="Webspaces" value="webspaces" />
            <Navigation.Item icon="fa-image" title="Media" value="media" />
            <Navigation.Item icon="fa-newspaper-o" title="Article" value="articles" />
            <Navigation.Item
                icon="fa-sticky-note-o"
                title="Snippets"
                value="snippets"
            />
            <Navigation.Item icon="su-user-1" title="Contact" value="contact">
                <Navigation.Item title="Contact 1" value="contact_1" />
                <Navigation.Item active={true} title="Contact 2" value="contact_2" />
                <Navigation.Item title="Contact 3" value="contact_3" />
            </Navigation.Item>
            <Navigation.Item icon="fa-gear" title="Settings" value="settings">
                <Navigation.Item title="Setting 1" value="setting_1" />
                <Navigation.Item title="Setting 2" value="setting_2" />
                <Navigation.Item title="Setting 3" value="setting_3" />
            </Navigation.Item>
        </Navigation>
    );
    expect(navigation.render()).toMatchSnapshot();

    navigation.find('.userProfile button').at(1).simulate('click');
    expect(handleLogoutClick).toBeCalled();

    navigation.find('button.pin').simulate('click');
    expect(handlePinClick).toBeCalled();

    navigation.find('.userContent img').simulate('click');
    navigation.find('.userProfile button').at(0).simulate('click');
    expect(handleProfileClick).toHaveBeenCalledTimes(2);
});

test('The expanded prop should be set correct automatically', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            appVersion="1.0.0"
            appVersionLink="http://link.com"
            onItemClick={handleNavigationClick}
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            title="sulu.io"
            userImage="http://lorempixel.com/200/200"
            username="John Travolta"
        >
            <Navigation.Item icon="su-user-1" title="Contact" value="contact">
                <Navigation.Item title="Contact 1" value="contact_1" />
                <Navigation.Item active={true} title="Contact 2" value="contact_2" />
                <Navigation.Item title="Contact 3" value="contact_3" />
            </Navigation.Item>
            <Navigation.Item icon="fa-gear" title="Settings" value="settings">
                <Navigation.Item title="Setting 1" value="setting_1" />
                <Navigation.Item title="Setting 2" value="setting_2" />
                <Navigation.Item title="Setting 3" value="setting_3" />
            </Navigation.Item>
        </Navigation>
    );

    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(true);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(false);

    navigation.find('Item[value="settings"] .title').simulate('click');
    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(false);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(true);
});

test('The expanded prop should be set correct automatically when children change', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            appVersion="1.0.0"
            appVersionLink="http://link.com"
            onItemClick={handleNavigationClick}
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            title="sulu.io"
            userImage="http://lorempixel.com/200/200"
            username="John Travolta"
        >
            <Navigation.Item icon="su-user-1" title="Contact" value="contact">
                <Navigation.Item title="Contact 1" value="contact_1" />
                <Navigation.Item active={true} title="Contact 2" value="contact_2" />
                <Navigation.Item title="Contact 3" value="contact_3" />
            </Navigation.Item>
            <Navigation.Item icon="fa-gear" title="Settings" value="settings">
                <Navigation.Item title="Setting 1" value="setting_1" />
                <Navigation.Item title="Setting 2" value="setting_2" />
                <Navigation.Item title="Setting 3" value="setting_3" />
            </Navigation.Item>
        </Navigation>
    );

    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(true);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(false);

    navigation.setProps({
        children: [
            <Navigation.Item icon="su-user-1" key="1" title="Contact" value="contact">
                <Navigation.Item title="Contact 1" value="contact_1" />
                <Navigation.Item title="Contact 2" value="contact_2" />
                <Navigation.Item title="Contact 3" value="contact_3" />
            </Navigation.Item>,
            <Navigation.Item icon="fa-gear" key="2" title="Settings" value="settings">
                <Navigation.Item title="Setting 1" value="setting_1" />
                <Navigation.Item active={true} title="Setting 2" value="setting_2" />
                <Navigation.Item title="Setting 3" value="setting_3" />
            </Navigation.Item>,
        ],
    });

    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(false);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(true);
});
