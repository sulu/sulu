// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable, autorun} from 'mobx';
import classNames from 'classnames';
import React, {Fragment} from 'react';
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

const USER_SETTING_PREFIX = 'sulu_admin.application';
const USER_SETTING_NAVIGATION_PINNED = 'navigation_pinned';

function getNavigationPinnedSettingKey() {
    return USER_SETTING_PREFIX + '.' + USER_SETTING_NAVIGATION_PINNED;
}

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

        this.pinNavigation(!!userStore.getPersistentSetting(getNavigationPinnedSettingKey()));

        this.navigationPinnedDisposer = autorun(
            () => userStore.setPersistentSetting(getNavigationPinnedSettingKey(), this.navigationPinned ? 1 : 0)
        );
    }

    @action toggleNavigation() {
        if (this.navigationPinned) {
            return;
        }

        this.navigationVisible = !this.navigationVisible;
    }

    @action pinNavigation(value?: boolean) {
        if (value !== undefined) {
            this.navigationPinned = value;
        } else {
            this.navigationPinned = !this.navigationPinned;
        }

        if (this.navigationPinned) {
            this.navigationVisible = true;
        } else {
            this.navigationVisible = false;
        }
    }

    componentWillUnmount() {
        this.navigationPinnedDisposer();
    }

    handleNavigationButtonClick = () => {
        this.toggleNavigation();
    };

    handleNavigationPin = () => {
        this.pinNavigation();
    };

    handleNavigate = () => {
        this.toggleNavigation();
    };

    handleLoginSuccess = () => {
        this.props.router.reload();
    };

    handleLogout = () => {
        userStore.logout().then(() => {
            this.pinNavigation(false);
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
                                onLogout={this.handleLogout}
                                onNavigate={this.handleNavigate}
                                onPin={this.handleNavigationPin}
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
                                        navigationPinned={this.navigationPinned}
                                        onNavigationButtonClick={this.handleNavigationButtonClick}
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
