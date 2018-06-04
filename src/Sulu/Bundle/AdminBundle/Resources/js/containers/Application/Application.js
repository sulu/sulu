// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
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

type Props = {
    router: Router,
};

@observer
export default class Application extends React.Component<Props> {
    @observable navigationVisible: boolean = false;

    @action toggleNavigation() {
        this.navigationVisible = !this.navigationVisible;
    }

    handleNavigationButtonClick = () => {
        this.toggleNavigation();
    };

    handleNavigate = () => {
        this.toggleNavigation();
    };

    handleLoginSuccess = () => {
        this.props.router.reload();
    };

    handleLogout = () => {
        userStore.logout().then(() => {
            this.toggleNavigation();
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
            }
        );

        return (
            <Fragment>
                {!loggedIn &&
                    <Login
                        onLoginSuccess={this.handleLoginSuccess}
                        initialized={!initializer.loading && !!initializer.initializedTranslationsLocale}
                        backLink="/" // TODO: Get the correct link here from the backend
                    />
                }
                {initializer.initialized &&
                    <div className={rootClass}>
                        <nav className={applicationStyles.navigation}>
                            <Navigation router={router} onNavigate={this.handleNavigate} onLogout={this.handleLogout} />
                        </nav>
                        <div className={contentClass}>
                            <Backdrop
                                open={this.navigationVisible}
                                visible={false}
                                onClick={this.handleNavigationButtonClick}
                                local={true}
                                fixed={false}
                            />
                            <main className={applicationStyles.main}>
                                <header className={applicationStyles.header}>
                                    <Toolbar
                                        navigationOpen={this.navigationVisible}
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
