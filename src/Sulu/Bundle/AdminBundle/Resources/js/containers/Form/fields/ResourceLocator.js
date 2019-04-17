// @flow
import React from 'react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../containers/ResourceLocatorHistory';
import Requester from '../../../services/Requester';
import type {FieldTypeProps} from '../../../types';
import resourceLocatorStyles from './resourceLocator.scss';

const PART_TAG = 'sulu.rlp.part';

export default class ResourceLocator extends React.Component<FieldTypeProps<?string>> {
    constructor(props: FieldTypeProps<?string>) {
        super(props);

        const {dataPath, onChange, fieldTypeOptions, formInspector, value} = this.props;

        const {generationUrl} = fieldTypeOptions;
        if (typeof generationUrl !== 'string') {
            throw new Error('The "generationUrl" fieldTypeOption must be a string!');
        }

        formInspector.addFinishFieldHandler((finishedFieldDataPath, finishedFieldSchemaPath) => {
            if (value !== undefined) {
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
                    locale: formInspector.locale ? formInspector.locale.get() : undefined,
                    ...formInspector.options,
                }
            ).then((response) => {
                onChange(response.resourcelocator);
            });
        });
    }

    handleBlur = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    render() {
        const {
            dataPath,
            disabled,
            formInspector,
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

        return (
            <div className={resourceLocatorStyles.resourceLocatorContainer}>
                <div className={resourceLocatorStyles.resourceLocator}>
                    <ResourceLocatorComponent
                        disabled={!!disabled}
                        id={dataPath}
                        mode={mode}
                        onBlur={this.handleBlur}
                        onChange={onChange}
                        value={value}
                    />
                </div>
                {formInspector.id &&
                    <div className={resourceLocatorStyles.resourceLocatorHistory}>
                        <ResourceLocatorHistory
                            id={formInspector.id}
                            options={{language: formInspector.locale, webspace: formInspector.options.webspace}}
                            resourceKey="page_routes"
                        />
                    </div>
                }
            </div>
        );
    }
}
