// @flow
import './global.scss';
import {observer} from 'mobx-react';
import React from 'react';
import Router from '../../services/Router';
import SplitView from '../SplitView';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import applicationStyles from './application.scss';

@observer
export default class Application extends React.PureComponent {
    props: {
        router: Router,
    };

    render() {
        return (
            <div>
                <Toolbar />
                <main className={applicationStyles.main}>
                    {this.props.router.currentRoute &&
                        <ViewRenderer
                            key={this.props.router.currentRoute.name}
                            name={this.props.router.currentRoute.view}
                            parameters={this.props.router.currentParameters} />
                    }
                </main>
                <SplitView />
            </div>
        );
    }
}
