// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import React from 'react';
import Router from '../../services/Router';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import {Navigation, Backdrop} from '../../components';
import applicationStyles from './application.scss';

const SULU_CHANGELOG_URL = 'https://github.com/sulu/sulu/releases';

const routes = [
    {
        name: 'Webspaces',
        key: 'sulu_content.webspaces',
        icon: 'su-webspace',
    },
    {
        name: 'Medias',
        key: 'sulu_media.overview',
        icon: 'su-image',
    },
    {
        name: 'Snippets',
        key: 'sulu_snippet.list',
        icon: 'fa-sticky-note-o',
    },
    {
        name: 'Contacts',
        key: 'contacts',
        icon: 'fa-user',
        items: [
            {
                name: 'Contacts',
                key: 'sulu_contact.contacts_list',
            },
            {
                name: 'Accounts',
                key: 'sulu_contact.accounts_list',
            },
        ],
    },
    {
        name: 'Settings',
        icon: 'su-settings',
        key: 'settings',
        items: [
            {
                name: 'Tags',
                key: 'sulu_tag.list',
            },
            {
                name: 'Roles',
                key: 'sulu_security.list',
            },
        ],
    },
];

type Props = {
    router: Router,
};

@observer
export default class Application extends React.Component<Props> {
    @observable navigationVisible: boolean =  false;

    @action toggleNavigation() {
        this.navigationVisible = !this.navigationVisible;
    }

    handleNavigationButtonClick = () => {
        this.toggleNavigation();
    };

    handleNavigationItemClick = (value: *) => {
        if (value && typeof value === 'string') {
            this.props.router.navigate(value);
        }
        this.toggleNavigation();
    };

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
                title="Sulu" // TODO: Get this data from logged in user
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

    render() {
        const {router} = this.props;

        const rootClass = classNames(
            applicationStyles.root,
            {
                [applicationStyles.navigationVisible]: this.navigationVisible,
            }
        );

        return (
            <div className={rootClass}>
                <nav className={applicationStyles.navigation}>{this.renderNavigation()}</nav>
                <div className={applicationStyles.content}>
                    <Backdrop
                        open={this.navigationVisible}
                        visible={false}
                        onClick={this.handleNavigationButtonClick}
                        local={true}
                        fixed={false}
                    />
                    <header className={applicationStyles.header}>
                        <Toolbar
                            navigationOpen={this.navigationVisible}
                            onNavigationButtonClick={this.handleNavigationButtonClick}
                        />
                    </header>
                    <main className={applicationStyles.main}>
                        {router.route &&
                            <ViewRenderer
                                key={router.route.name}
                                router={router}
                            />
                        }
                    </main>
                </div>
            </div>
        );
    }
}
