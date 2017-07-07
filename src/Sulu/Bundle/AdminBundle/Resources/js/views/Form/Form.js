// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {withToolbar} from '../../containers/Toolbar';
import translator from '../../services/Translator';

class Form extends React.PureComponent {
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
            title: translator.translate('sulu_admin.save'),
            icon: 'floppy-o',
            enabled: this.dirty,
            onClick: () => {
                this.setDirty(false);
            },
        },
        {
            title: translator.translate('sulu_admin.delete'),
            icon: 'trash-o',
            onClick: () => {
                this.setDirty(true);
            },
        },
    ];
});
