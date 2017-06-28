// @flow
import React from 'react';
import {observer} from 'mobx-react';
import toolbarStore from './stores/ToolbarStore';

@observer
export default class Toolbar extends React.PureComponent {
    render() {
        return (
            <header>
                <ul>
                    {toolbarStore.items.map((item) => (<li key={item.title}>{item.title}</li>))}
                </ul>
            </header>
        );
    }
}
