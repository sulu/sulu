// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer/types';

@observer
class Form extends React.PureComponent<ViewProps> {
    @observable dirty = false;

    @observable selectValue;

    @observable localeValue = 'en';

    @action setDirty(dirty) {
        this.dirty = dirty;
    }

    @action setSelectValue(value) {
        this.selectValue = value;
    }

    @action setLocaleValue(value) {
        this.localeValue = value;
    }

    render() {
        return (
            <div>
                <h1>Form</h1>
                <a href="#/snippets/">To the List</a>
            </div>
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
            value: this.localeValue,
            onChange: (value) => {
                this.setLocaleValue(value);
            },
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
        items: [
            {
                type: 'dropdown',
                label: 'Save',
                icon: 'floppy-o',
                disabled: !this.dirty,
                options: [
                    {
                        label: 'Save as draft',
                        onClick: () => {
                            this.setDirty(false);
                        },
                    },
                    {
                        label: 'Save and publish',
                        disabled: true,
                    },
                    {
                        label: 'Publish',
                        onClick: () => {},
                    },
                ],
            },
            {
                type: 'select',
                icon: 'star',
                value: this.selectValue,
                label: 'Choose',
                onChange: (optionVal) => {
                    this.setSelectValue(optionVal);
                },
                options: [
                    {
                        value: 1,
                        label: '1',
                    },
                    {
                        value: 2,
                        label: '2',
                    },
                    {
                        value: 3,
                        label: '3',
                    },
                ],
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {
                    this.setDirty(true);
                },
            },
            {
                type: 'dropdown',
                icon: 'ellipsis-h',
                options: [
                    {
                        label: 'Export',
                        onClick: () => {
                            this.setDirty(false);
                        },
                    },
                    {
                        label: 'Import',
                        disabled: true,
                    },
                    {
                        label: 'Update',
                        onClick: () => {},
                    },
                ],
            },
        ],
    };
});
