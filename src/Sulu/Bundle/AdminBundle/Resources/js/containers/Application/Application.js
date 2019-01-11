// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable, autorun, computed} from 'mobx';
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
    appVersion: ?string,
    router: Router,
    suluVersion: string,
    title: ?string,
};

type NavigationState = 'pinned' | 'hidden' | 'visible';

@observer
export default class Application extends React.Component<Props> {
    @observable navigationState: NavigationState;

    @computed get navigationPinned() {
        return this.navigationState === 'pinned';
    }

    @computed get navigationVisible() {
        return this.navigationPinned || this.navigationState === 'visible';
    }

    @action setNavigationState(state: NavigationState) {
        this.navigationState = state;
    }

    set navigationPinned(value: boolean) {
        this.setNavigationState(value ? 'pinned' : 'hidden');
    }

    set navigationVisible(value: boolean) {
        if (this.navigationPinned) {
            log.warn('Changing the visibility of the navigation is not allowed while navigation is pinned!');
            return;
        }

        this.setNavigationState(value ? 'visible' : 'hidden');
    }

    navigationPinnedDisposer: () => void;

    constructor() {
        super();

        this.navigationPinned = userStore.getPersistentSetting(NAVIGATION_PINNED_SETTING_KEY);

        this.navigationPinnedDisposer = autorun(
            () => userStore.setPersistentSetting(NAVIGATION_PINNED_SETTING_KEY, this.navigationPinned)
        );
    }

    componentWillUnmount() {
        this.navigationPinnedDisposer();
    }

    toggleNavigation() {
        this.navigationVisible = !this.navigationVisible;
    }

    toggleNavigationPinned() {
        this.navigationPinned = !this.navigationPinned;
    }

    handleNavigationButtonClick = () => {
        this.toggleNavigation();
    };

    handlePinToggle = () => {
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
        const {appVersion, router, suluVersion, title} = this.props;
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
                                appVersion={appVersion}
                                onLogout={this.handleLogout}
                                onNavigate={this.handleNavigate}
                                onPinToggle={this.handlePinToggle}
                                pinned={this.navigationPinned}
                                router={router}
                                suluVersion={suluVersion}
                                title={title}
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
