// @flow
import React from 'react';
import {ViewRenderer} from './ViewRegistry';
import Router from './Routing/Router';

type Props = {
    router: Router,
};

export default class Application extends React.Component {
    props: Props;

    render() {
        return (
            <ViewRenderer
                key={this.props.router.currentRoute.name}
                name={this.props.router.currentRoute.view}
                parameters={this.props.router.currentParameters} />
        );
    }
}
