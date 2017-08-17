// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';

class Form extends React.PureComponent<*> {
    @observable dirty = false;

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

export default withToolbar(Form, function() {
    return [
        {
            title: translate('sulu_admin.save'),
            icon: 'floppy-o',
            enabled: this.dirty,
            onClick: () => {
                this.setDirty(false);
            },
        },
        {
            title: translate('sulu_admin.delete'),
            icon: 'trash-o',
            onClick: () => {
                this.setDirty(true);
            },
        },
    ];
});
