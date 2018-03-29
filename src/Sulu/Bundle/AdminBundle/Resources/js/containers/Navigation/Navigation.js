// @flow
import React from 'react';
import {Navigation as NavigationComponent} from '../../components';
import Router from '../../services/Router/Router';
import navigationRegistry from './registries/NavigationRegistry';
import type {NavigationItem} from './types';

type Props = {
    router: Router,
    onNavigate: (route: string) => void,
};

const SULU_CHANGELOG_URL = 'https://github.com/sulu/sulu/releases';

export default class Navigation extends React.Component<Props> {
    handleNavigationItemClick = (value: string) => {
        this.props.router.navigate(value);
        this.props.onNavigate(value);
    };

    handleLogoutClick = () => {
        // TODO: Logout user here.
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

    render() {
        const navigationItems = navigationRegistry.get();

        return (
            <NavigationComponent
                title="Sulu" // TODO: Get this dynamically from server
                username="Hikaru Sulu" // TODO: Get this data from logged in user
                suluVersion="2.0.0-RC1" // TODO: Get this dynamically from server
                suluVersionLink={SULU_CHANGELOG_URL}
                onLogoutClick={this.handleLogoutClick}
                onProfileClick={this.handleProfileEditClick}
            >
                {navigationItems.map((navigationItem: NavigationItem) => (
                    <NavigationComponent.Item
                        key={navigationItem.id}
                        value={navigationItem.mainRoute ? navigationItem.mainRoute : navigationItem.id}
                        title={navigationItem.label}
                        icon={navigationItem.icon}
                        active={this.isItemActive(navigationItem)}
                        onClick={this.handleNavigationItemClick}
                    >
                        {Array.isArray(navigationItem.items) &&
                            navigationItem.items.map((subNavigationItem) => (
                                <NavigationComponent.Item
                                    key={subNavigationItem.id}
                                    value={subNavigationItem.mainRoute}
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
