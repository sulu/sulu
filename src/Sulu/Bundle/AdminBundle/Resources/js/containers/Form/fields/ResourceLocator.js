// @flow
import React from 'react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import Requester from '../../../services/Requester';
import type {FieldTypeProps} from '../../../types';

const PART_TAG = 'sulu.rlp.part';

export default class ResourceLocator extends React.Component<FieldTypeProps<string>> {
    constructor(props: FieldTypeProps<string>) {
        super(props);

        const {dataPath, onChange, fieldTypeOptions, formInspector, value} = this.props;

        const {generationUrl} = fieldTypeOptions;
        if (typeof generationUrl !== 'string') {
            throw new Error('The "generationUrl" fieldTypeOption must be a string!');
        }

        formInspector.addFinishFieldHandler((finishedFieldDataPath, finishedFieldSchemaPath) => {
            if (formInspector.id) {
                // do not generate resource locator if on edit form
                return;
            }

            if (formInspector.isFieldModified(dataPath)) {
                return;
            }

            const {tags: finishedFieldTags} = formInspector.getSchemaEntryByPath(finishedFieldSchemaPath);
            if (!finishedFieldTags || !finishedFieldTags.some((tag) => tag.name === PART_TAG)) {
                return;
            }

            const parts = formInspector.getValuesByTag(PART_TAG)
                .filter((part) => part !== null && part !== undefined);

            if (parts.length === 0) {
                return;
            }

            Requester.post(
                generationUrl,
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
        onFinish();
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
