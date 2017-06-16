// @flow
import React from 'react';
import ViewRenderer from '../ViewRenderer';

export default class Application extends React.Component {
    render() {
        return (<ViewRenderer name="hello_world" />);
    }
}
