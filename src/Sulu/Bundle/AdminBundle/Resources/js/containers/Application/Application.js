// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import React from 'react';
import Navigation from '../Navigation';
import Router from '../../services/Router';
import Sidebar, {sidebarStore} from '../Sidebar';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import {Backdrop} from '../../components';
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

    render() {
        const {router} = this.props;

        const rootClass = classNames(
            applicationStyles.root,
            {
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
            <div className={rootClass}>
                <nav className={applicationStyles.navigation}>
                    <Navigation onNavigate={this.handleNavigate} router={router} />
                </nav>
                <div className={contentClass}>
                    <Backdrop
                        fixed={false}
                        local={true}
                        onClick={this.handleNavigationButtonClick}
                        open={this.navigationVisible}
                        visible={false}
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
        );
    }
}
