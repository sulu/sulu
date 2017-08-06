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
        backButton: {
            onClick: () => {},
        },
        icons: [
            'ban',
            'flag',
        ],
        locale: {
            value: 'en',
            onChange: () => {},
            options: [
                {
                    value: 'de',
                    label: 'de',
                    disabled: true,
                },
                {
                    value: 'en',
                    label: 'en',
                },
                {
                    value: 'fr',
                    label: 'fr',
                },
            ],
        },
        buttons: [
            {
                value: translate('sulu_admin.save'),
                label: 'Choose',
                icon: 'floppy-o',
                disabled: !this.dirty,
                setValueOnChange: true,
                onChange: (optionVal) => {
                    if (optionVal === 'save_publish') {
                        this.setDirty(false);
                    }
                },
                options: [
                    {
                        value: 'save_draft',
                        label: 'Save as draft',
                        disabled: true,
                    },
                    {
                        value: 'save_publish',
                        label: 'Save and publish',
                    },
                    {
                        value: 'publish',
                        label: 'Publish',
                    },
                ],
            },
            {
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {
                    this.setDirty(true);
                },
            },
        ],
    };
});
