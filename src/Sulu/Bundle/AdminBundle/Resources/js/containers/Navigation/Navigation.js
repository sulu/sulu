// @flow
import React from 'react';
import {Navigation as NavigationComponent} from '../../components';
import Router from '../../services/Router';
import userStore from '../../stores/UserStore';
import navigationRegistry from './registries/NavigationRegistry';
import type {NavigationItem} from './types';

type Props = {
    router: Router,
    onNavigate: (route: string) => void,
    onLogout: () => void,
};

const SULU_CHANGELOG_URL = 'https://github.com/sulu/sulu/releases';

export default class Navigation extends React.Component<Props> {
    handleNavigationItemClick = (value: string) => {
        const navigationItem = navigationRegistry.get(value);

        this.props.router.navigate(navigationItem.mainRoute);
        this.props.onNavigate(navigationItem.mainRoute);
    };

    handleProfileEditClick = () => {
        // TODO: Open profile edit overlay here.
    };

    isItemActive = (navigationItem: NavigationItem) => {
        const {router} = this.props;

        if (!router.route) {
            return false;
        }

        return (navigationItem.mainRoute && router.route.name === navigationItem.mainRoute) ||
            (navigationItem.childRoutes && navigationItem.childRoutes.includes(router.route.name));
    };

    getUsername() {
        if (!userStore.loggedIn || !userStore.contact) {
            return '';
        }

        return userStore.contact.fullName;
    }

    getUserImage() {
        if (!userStore.loggedIn || !userStore.contact || !userStore.contact.avatar) {
            return undefined;
        }

        return userStore.contact.avatar.thumbnails['sulu-50x50'];
    }

    render() {
        const navigationItems = navigationRegistry.getAll();

        return (
            <NavigationComponent
                title="Sulu" // TODO: Get this dynamically from server
                username={this.getUsername()}
                userImage={this.getUserImage()}
                suluVersion="2.0.0-RC1" // TODO: Get this dynamically from server
                suluVersionLink={SULU_CHANGELOG_URL}
                onLogoutClick={this.props.onLogout}
                onProfileClick={this.handleProfileEditClick}
            >
                {navigationItems.map((navigationItem: NavigationItem) => (
                    <NavigationComponent.Item
                        key={navigationItem.id}
                        value={navigationItem.id}
                        title={navigationItem.label}
                        icon={navigationItem.icon}
                        active={this.isItemActive(navigationItem)}
                        onClick={this.handleNavigationItemClick}
                    >
                        {Array.isArray(navigationItem.items) &&
                            navigationItem.items.map((subNavigationItem) => (
                                <NavigationComponent.Item
                                    key={subNavigationItem.id}
                                    value={subNavigationItem.id}
                                    title={subNavigationItem.label}
                                    active={this.isItemActive(subNavigationItem)}
                                    onClick={this.handleNavigationItemClick}
                                />
                            ))
                        }
                    </NavigationComponent.Item>
                ))}
            </NavigationComponent>
        );
    }
}
