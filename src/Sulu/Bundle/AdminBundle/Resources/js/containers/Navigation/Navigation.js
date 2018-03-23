// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Loader from '../../components/Loader';

type Props = {
    store: FormStore,
    onSubmit: (action: ?string) => void,
};

@observer
export default class Navigation extends React.Component<Props> {
    handleLogoutClick = () => {
        // TODO: Logout user here.
    };

    handleProfileEditClick = () => {
        // TODO: Open profile edit overlay here.
    };

    renderNavigation() {
        const {router} = this.props;

        return (
            <Navigation
                title="Sulu" // TODO: Get this dynamically from server
                username="Hikaru Sulu" // TODO: Get this data from logged in user
                suluVersion="2.0.0-RC1" // TODO: Get this dynamically from server
                suluVersionLink={SULU_CHANGELOG_URL}
                onLogoutClick={this.handleLogoutClick}
                onProfileClick={this.handleProfileEditClick}
            >
                {routes.map((route) => (
                    <Navigation.Item
                        key={route.key ? route.key : ''}
                        value={route.key ? route.key : ''}
                        title={route.name}
                        icon={route.icon}
                        active={route.key && router.route ? (router.route.name === route.key) : false}
                        onClick={this.handleNavigationItemClick}
                    >
                        {Array.isArray(route.items) &&
                        route.items.map((subRoute) => (
                            <Navigation.Item
                                key={subRoute.key}
                                value={subRoute.key}
                                title={subRoute.name}
                                active={router.route ? router.route.name === subRoute.key : false}
                                onClick={this.handleNavigationItemClick}
                            />
                        ))
                        }
                    </Navigation.Item>
                ))}
            </Navigation>
        );
    }
}
