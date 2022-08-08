//@flow
import {fireEvent, render, screen} from '@testing-library/react';
import React from 'react';
import Navigation from '../Navigation';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('The component should render and handle clicks correctly', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const {container} = render(
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

    expect(container).toMatchSnapshot();
    fireEvent.click(screen.queryByText('sulu_admin.edit_profile'));
    expect(handleProfileClick).toBeCalled();
    fireEvent.click(screen.queryByText('sulu_admin.logout'));
    expect(handleLogoutClick).toBeCalled();
});

test('The component should render with all available props and handle clicks correctly', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handlePinClick = jest.fn();
    const handleProfileClick = jest.fn();

    const {container} = render(
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

    expect(container).toMatchSnapshot();

    fireEvent.click(screen.queryByLabelText('su-stick-right'));
    expect(handlePinClick).toBeCalled();

    fireEvent.click(screen.queryByText('sulu_admin.edit_profile'));
    expect(handleProfileClick).toBeCalled();

    fireEvent.click(screen.queryByText('sulu_admin.logout'));
    expect(handleLogoutClick).toBeCalled();
});

test('The expanded prop should be set correct automatically', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    render(
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

    expect(screen.getByText(/Contact 1/)).toBeInTheDocument();
    expect(screen.queryByText(/Setting 1/)).not.toBeInTheDocument();

    fireEvent.click(screen.queryByText(/Settings/));
    expect(screen.queryByText(/Contact 1/)).not.toBeInTheDocument();
    expect(screen.getByText(/Setting 1/)).toBeInTheDocument();
});

test('The expanded prop should be set correct automatically when children change', () => {
    const handleNavigationClick = jest.fn();
    const handleLogoutClick = jest.fn();
    const handleProfileClick = jest.fn();

    const {rerender} = render(
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

    expect(screen.getByText(/Contact 1/)).toBeInTheDocument();
    expect(screen.queryByText(/Setting 1/)).not.toBeInTheDocument();

    rerender(
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
                <Navigation.Item title="Contact 2" value="contact_2" />
                <Navigation.Item title="Contact 3" value="contact_3" />
            </Navigation.Item>
            <Navigation.Item icon="fa-gear" title="Settings" value="settings">
                <Navigation.Item title="Setting 1" value="setting_1" />
                <Navigation.Item active={true} title="Setting 2" value="setting_2" />
                <Navigation.Item title="Setting 3" value="setting_3" />
            </Navigation.Item>
        </Navigation>
    );

    expect(screen.queryByText(/Contact 1/)).not.toBeInTheDocument();
    expect(screen.getByText(/Setting 1/)).toBeInTheDocument();
});
