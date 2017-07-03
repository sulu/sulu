// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {toolbarStore} from '../../containers/Toolbar';

export default class Form extends React.PureComponent {
    @observable dirty = false;

    componentWillMount() {
        autorun(() => {
            toolbarStore.setItems([
                {
                    title: 'Save',
                    icon: 'floppy-o',
                    enabled: this.dirty,
                    onClick: () => {
                        this.setDirty(false);
                    },
                },
                {
                    title: 'Delete',
                    icon: 'trash-o',
                    onClick: () => {
                        this.setDirty(true);
                    },
                },
            ]);
        });
    }

    @action
    setDirty(dirty: boolean) {
        this.dirty = dirty;
    }

    render() {
        return (
            <h1>Form</h1>
        );
    }
}
