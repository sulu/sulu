// @flow
import React from 'react';
import {observer} from 'mobx-react';
import Item from './Item';
import toolbarStore from './stores/ToolbarStore';
import toolbarStyles from './toolbar.scss';

@observer
export default class Toolbar extends React.PureComponent<*> {
    render() {
        return (
            <header className={toolbarStyles.toolbar}>
                <nav>
                    {toolbarStore.items.map((item) => (<Item key={item.title} {...item} />))}
                </nav>
            </header>
        );
    }
}
