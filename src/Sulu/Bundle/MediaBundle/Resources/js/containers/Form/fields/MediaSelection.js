// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import userStore from 'sulu-admin-bundle/stores/UserStore';
import {observable} from 'mobx';
import MultiMediaSelection from '../../MultiMediaSelection';
import type {Value} from '../../MultiMediaSelection';

@observer
class MediaSelection extends React.Component<FieldTypeProps<Value>> {
    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, schemaOptions, value} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
        } = schemaOptions;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        if (displayOptions && !Array.isArray(displayOptions)) {
            throw new Error('The "displayOptions" option has to be an Array if set.');
        }

        const displayOptionValues = displayOptions
            ? displayOptions
                .filter((displayOption) => displayOption.value === true)
                .map(({name}) => {
                    switch (name) {
                        case 'leftTop':
                        case 'top':
                        case 'rightTop':
                        case 'left':
                        case 'middle':
                        case 'right':
                        case 'leftBottom':
                        case 'bottom':
                        case 'rightBottom':
                            break;
                        default:
                            throw new Error(
                                'The children of "displayOptions" contains the invalid value "' + (name || '') + '".'
                            );
                    }
                    return name;
                })
            : [];

        return (
            <MultiMediaSelection
                disabled={!!disabled}
                displayOptions={displayOptionValues}
                locale={locale}
                onChange={this.handleChange}
                value={value ? value : undefined}
            />
        );
    }
}

export default MediaSelection;
