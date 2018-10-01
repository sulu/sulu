// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable, autorun, intercept, observe} from 'mobx';
import type {IValueWillChange, IValueDidChange} from 'mobx';
import classNames from 'classnames';
import React, {Fragment} from 'react';
import log from 'loglevel';
import Navigation from '../Navigation';
import Router from '../../services/Router';
import initializer from '../../services/Initializer';
import Sidebar, {sidebarStore} from '../Sidebar';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import userStore from '../../stores/UserStore';
import {Backdrop} from '../../components';
import Login from '../Login';
import applicationStyles from './application.scss';

const NAVIGATION_PINNED_SETTING_KEY = 'sulu_admin.application.navigation_pinned';

type Props = {
    router: Router,
};

@observer
export default class Application extends React.Component<Props> {
    @observable navigationVisible: boolean = false;
    @observable navigationPinned: boolean = false;

    navigationPinnedDisposer: () => void;

    constructor() {
        super();

        intercept(this, 'navigationVisible', (change: IValueWillChange<boolean>) => {
            if (this.navigationPinned) {
                log('Cannot change "navigationVisible" while navigation is pinned.');
                return null;
            }

            return change;
        });

        intercept(this, 'navigationPinned', (change: IValueWillChange<boolean>) => {
            if (change.newValue) {
                this.navigationVisible = true;
            }

            return change;
        });

        observe(this, 'navigationPinned', (change: IValueDidChange<boolean>) => {
            if (!change.newValue) {
                this.navigationVisible = false;
            }
        });

        this.setNavigationPinned(!!userStore.getPersistentSetting(NAVIGATION_PINNED_SETTING_KEY));

        this.navigationPinnedDisposer = autorun(
            () => userStore.setPersistentSetting(NAVIGATION_PINNED_SETTING_KEY, this.navigationPinned ? 1 : 0)
        );
    }

    @action toggleNavigation() {
        this.navigationVisible = !this.navigationVisible;
    }

    @action setNavigationPinned(value: boolean) {
        this.navigationPinned = value;
    }

    toggleNavigationPinned() {
        this.setNavigationPinned(!this.navigationPinned);
    }

    componentWillUnmount() {
        this.navigationPinnedDisposer();
    }

    handleNavigationButtonClick = () => {
        if (!this.navigationPinned) {
            this.toggleNavigation();
        }
    };

    handleToggleNavigationPinned = () => {
        this.toggleNavigationPinned();
    };

    handleNavigate = () => {
        if (!this.navigationPinned) {
            this.toggleNavigation();
        }
    };

    handleLoginSuccess = () => {
        this.props.router.reload();
    };

    handleLogout = () => {
        userStore.logout().then(() => {
            if (this.navigationVisible && !this.navigationPinned) {
                this.toggleNavigation();
            }
        });
    };

    render() {
        const {router} = this.props;
        const {loggedIn} = userStore;

        const rootClass = classNames(
            applicationStyles.root,
            {
                [applicationStyles.visible]: loggedIn,
                [applicationStyles.navigationVisible]: this.navigationVisible,
                [applicationStyles.navigationPinned]: this.navigationPinned,
            }
        );

        const sidebarClass = classNames(
            applicationStyles.sidebar,
            {
                [applicationStyles[sidebarStore.size]]: sidebarStore.size,
            }
        );

        const contentClass = classNames(
            applicationStyles.content,
            {
                [applicationStyles.withSidebar]: sidebarStore.view,
                [applicationStyles.withPinnedNavigation]: this.navigationPinned,
            }
        );

        return (
            <Fragment>
                {!loggedIn &&
                    <Login
                        backLink="/" // TODO: Get the correct link here from the backend
                        initialized={!initializer.loading && !!initializer.initializedTranslationsLocale}
                        onLoginSuccess={this.handleLoginSuccess}
                    />
                }
                {initializer.initialized &&
                    <div className={rootClass}>
                        <nav className={applicationStyles.navigation}>
                            <Navigation
                                isPinned={this.navigationPinned}
                                onLogout={this.handleLogout}
                                onNavigate={this.handleNavigate}
                                onPinToggle={this.handleToggleNavigationPinned}
                                router={router}
                            />
                        </nav>
                        <div className={contentClass}>
                            <Backdrop
                                fixed={false}
                                local={true}
                                onClick={this.handleNavigationButtonClick}
                                open={this.navigationVisible && !this.navigationPinned}
                                visible={false}
                            />
                            <main className={applicationStyles.main}>
                                <header className={applicationStyles.header}>
                                    <Toolbar
                                        navigationOpen={this.navigationVisible}
                                        onNavigationButtonClick={
                                            this.navigationPinned
                                                ? undefined
                                                : this.handleNavigationButtonClick
                                        }
                                    />
                                </header>
                                <div className={applicationStyles.viewContainer}>
                                    {router.route &&
                                    <ViewRenderer router={router} />
                                    }
                                </div>
                            </main>
                            <Sidebar className={sidebarClass} />
                        </div>
                    </div>
                }
            </Fragment>
        );
    }
}
