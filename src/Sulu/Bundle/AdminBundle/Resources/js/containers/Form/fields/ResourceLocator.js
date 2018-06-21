// @flow
import React from 'react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import Requester from '../../../services/Requester';
import type {FieldTypeProps} from '../../../types';

export default class ResourceLocator extends React.Component<FieldTypeProps<string>> {
    constructor(props: FieldTypeProps<string>) {
        super(props);

        const {onChange, formInspector, value} = this.props;

        formInspector.addFinishFieldHandler(() => {
            if (formInspector.id) {
                // do not generate resource locator if on edit form
                return;
            }

            const parts = formInspector.getValuesByTag('sulu.rlp.part')
                .filter((part) => part !== null && part !== undefined);

            if (parts.length === 0) {
                return;
            }

            Requester.post(
                // TODO get URL from somewhere instead of hardcoding
                '/admin/api/resourcelocators?action=generate',
                {
                    parts,
                    locale: formInspector.locale,
                    ...formInspector.options,
                }
            ).then((response) => {
                onChange(response.resourcelocator);
            });
        });

        if (value === undefined || value === '') {
            onChange('/');
        }
    }

    handleBlur = () => {
        const {onFinish} = this.props;
        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {
            onChange,
            schemaOptions: {
                mode: {
                    value: mode,
                } = {value: 'leaf'},
            } = {},
            value,
        } = this.props;

        if (mode !== 'leaf' && mode !== 'full') {
            throw new Error('The "mode" schema option must be either "leaf" or "full"!');
        }

        if (!value) {
            return null;
        }

        return (
            <ResourceLocatorComponent value={value} onChange={onChange} mode={mode} onBlur={this.handleBlur} />
        );
    }
}
