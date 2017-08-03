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
    return {
        buttons: [
            {
                value: translate('sulu_admin.save'),
                icon: 'floppy-o',
                disabled: !this.dirty,
                setValueOnChange: true,
                onChange: (item) => {
                    if (item.value === 'Save as draft') {
                        this.setDirty(false);
                    }
                },
                options: [
                    {
                        value: 'Save as draft',
                        disabled: true,
                    },
                    {
                        value: 'Save and publish',
                        selected: true,
                    },
                    {
                        value: 'Publish',
                    },
                ]
            },
            {
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {
                    this.setDirty(true);
                },
            },
        ]
    }
});
