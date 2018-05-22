// @flow
import React from 'react';
import {Navigation as NavigationComponent} from '../../components';
import Router from '../../services/Router/Router';
import navigationRegistry from './registries/NavigationRegistry';
import type {NavigationItem} from './types';

type Props = {
    onNavigate: (route: string) => void,
    router: Router,
};

const SULU_CHANGELOG_URL = 'https://github.com/sulu/sulu/releases';

export default class Navigation extends React.Component<Props> {
    handleNavigationItemClick = (value: string) => {
        const navigationItem = navigationRegistry.get(value);

        this.props.router.navigate(navigationItem.mainRoute);
        this.props.onNavigate(navigationItem.mainRoute);
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
        const navigationItems = navigationRegistry.getAll();

        return (
            <NavigationComponent
                onLogoutClick={this.handleLogoutClick} // TODO: Get this dynamically from server
                onProfileClick={this.handleProfileEditClick} // TODO: Get this data from logged in user
                suluVersion="2.0.0-RC1" // TODO: Get this dynamically from server
                suluVersionLink={SULU_CHANGELOG_URL}
                title="Sulu"
                username="Hikaru Sulu"
            >
                {navigationItems.map((navigationItem: NavigationItem) => (
                    <NavigationComponent.Item
                        active={this.isItemActive(navigationItem)}
                        icon={navigationItem.icon}
                        key={navigationItem.id}
                        onClick={this.handleNavigationItemClick}
                        title={navigationItem.label}
                        value={navigationItem.id}
                    >
                        {Array.isArray(navigationItem.items) &&
                            navigationItem.items.map((subNavigationItem) => (
                                <NavigationComponent.Item
                                    active={this.isItemActive(subNavigationItem)}
                                    key={subNavigationItem.id}
                                    onClick={this.handleNavigationItemClick}
                                    title={subNavigationItem.label}
                                    value={subNavigationItem.id}
                                />
                            ))
                        }
                    </NavigationComponent.Item>
                ))}
            </NavigationComponent>
        );
    }
}
