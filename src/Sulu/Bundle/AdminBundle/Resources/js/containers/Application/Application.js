// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import React from 'react';
import Navigation from '../Navigation';
import Toolbar from '../Toolbar';
import Router from '../../services/Router';
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

        return (
            <div className={rootClass}>
                <nav className={applicationStyles.navigation}>
                    <Navigation router={router} onNavigate={this.handleNavigate} />
                </nav>
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
