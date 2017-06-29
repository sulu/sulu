// @flow
import React from 'react';
import {observer} from 'mobx-react';
import Router from '../../services/Router';
import SplitView from '../SplitView';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import './application.scss';

@observer
export default class Application extends React.PureComponent {
    props: {
        router: Router,
    };

    render() {
        return (
            <div>
                <Toolbar />
                <main>
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
