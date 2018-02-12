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
            title="sulu.io"
            username="John Terence Maximilian Travolta"
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
        >
            <Navigation.Item value="search" title="Search" icon="su-search" onClick={handleNavigationClick} />
            <Navigation.Item value="webspaces" title="Webspaces" icon="fa-bullseye" onClick={handleNavigationClick} />
        </Navigation>
    );
    expect(navigation.render()).toMatchSnapshot();

    navigation.find('.userProfile button').simulate('click');
    expect(handleLogoutClick).toBeCalled();

    navigation.find('.noUserImage').simulate('click');
    navigation.find('.userProfile span').at(0).simulate('click');
    expect(handleProfileClick).toHaveBeenCalledTimes(2);
});

test('The component should render with all available props and handle clicks correctly', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            title="sulu.io"
            username="John Travolta"
            userImage={'http://lorempixel.com/200/200'}
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            appVersion="1.0.0"
            appVersionLink="http://link.com"
        >
            <Navigation.Item value="search" title="Search" icon="su-search" onClick={handleNavigationClick} />
            <Navigation.Item value="webspaces" title="Webspaces" icon="su-webspace" onClick={handleNavigationClick} />
            <Navigation.Item value="media" title="Media" icon="fa-image" onClick={handleNavigationClick} />
            <Navigation.Item value="articles" title="Article" icon="fa-newspaper-o" onClick={handleNavigationClick} />
            <Navigation.Item
                value="snippets"
                title="Snippets"
                icon="fa-sticky-note-o"
                onClick={handleNavigationClick}
            />
            <Navigation.Item value="contact" title="Contact" icon="su-user-1">
                <Navigation.Item value="contact_1" onClick={handleNavigationClick} title="Contact 1" />
                <Navigation.Item value="contact_2" onClick={handleNavigationClick} title="Contact 2" active={true} />
                <Navigation.Item value="contact_3" onClick={handleNavigationClick} title="Contact 3" />
            </Navigation.Item>
            <Navigation.Item value="settings" title="Settings" icon="fa-gear">
                <Navigation.Item value="setting_1" onClick={handleNavigationClick} title="Setting 1" />
                <Navigation.Item value="setting_2" onClick={handleNavigationClick} title="Setting 2" />
                <Navigation.Item value="setting_3" onClick={handleNavigationClick} title="Setting 3" />
            </Navigation.Item>
        </Navigation>
    );
    expect(navigation.render()).toMatchSnapshot();

    navigation.find('.userProfile button').simulate('click');
    expect(handleLogoutClick).toBeCalled();

    navigation.find('.userContent img').simulate('click');
    navigation.find('.userProfile span').at(0).simulate('click');
    expect(handleProfileClick).toHaveBeenCalledTimes(2);
});

test('The expanded prop should be set correct automatically', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const navigation = mount(
        <Navigation
            title="sulu.io"
            username="John Travolta"
            userImage={'http://lorempixel.com/200/200'}
            onLogoutClick={handleLogoutClick}
            onProfileClick={handleProfileClick}
            suluVersion="2.0.0-RC1"
            suluVersionLink="http://link.com"
            appVersion="1.0.0"
            appVersionLink="http://link.com"
        >
            <Navigation.Item value="search" title="Search" icon="su-search" onClick={handleNavigationClick} />
            <Navigation.Item value="webspaces" title="Webspaces" icon="su-webspace" onClick={handleNavigationClick} />
            <Navigation.Item value="media" title="Media" icon="fa-image" onClick={handleNavigationClick} />
            <Navigation.Item value="articles" title="Article" icon="fa-newspaper-o" onClick={handleNavigationClick} />
            <Navigation.Item
                value="snippets"
                title="Snippets"
                icon="fa-sticky-note-o"
                onClick={handleNavigationClick}
            />
            <Navigation.Item value="contact" title="Contact" icon="su-user-1">
                <Navigation.Item value="contact_1" onClick={handleNavigationClick} title="Contact 1" />
                <Navigation.Item value="contact_2" onClick={handleNavigationClick} title="Contact 2" active={true} />
                <Navigation.Item value="contact_3" onClick={handleNavigationClick} title="Contact 3" />
            </Navigation.Item>
            <Navigation.Item value="settings" title="Settings" icon="fa-gear">
                <Navigation.Item value="setting_1" onClick={handleNavigationClick} title="Setting 1" />
                <Navigation.Item value="setting_2" onClick={handleNavigationClick} title="Setting 2" />
                <Navigation.Item value="setting_3" onClick={handleNavigationClick} title="Setting 3" />
            </Navigation.Item>
        </Navigation>
    );

    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(true);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(false);

    navigation.find('Item[value="settings"] Item .title').simulate('click');
    expect(navigation.find('Item[value="contact"]').instance().props.expanded).toBe(false);
    expect(navigation.find('Item[value="settings"]').instance().props.expanded).toBe(true);
});
