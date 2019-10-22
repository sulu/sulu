// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../containers/ResourceLocatorHistory';
import Requester from '../../../services/Requester';
import type {FieldTypeProps} from '../../../types';
import resourceLocatorStyles from './resourceLocator.scss';

const PART_TAG = 'sulu.rlp.part';

const HOMEPAGE_RESOURCE_LOCATOR = '/';

@observer
class ResourceLocator extends React.Component<FieldTypeProps<?string>> {
    @observable mode: string;

    constructor(props: FieldTypeProps<?string>) {
        super(props);

        const {dataPath, onChange, fieldTypeOptions, formInspector, value} = this.props;
        const {generationUrl, modeResolver} = fieldTypeOptions;

        if (!modeResolver) {
            throw new Error('The "modeResolver" must be a function returning a promise with the desired mode');
        }

        modeResolver(this.props).then(action((mode) => this.mode = mode));

        if (!generationUrl) {
            return;
        }

        if (typeof generationUrl !== 'string') {
            throw new Error('The "generationUrl" fieldTypeOption must be a string!');
        }

        if (value === HOMEPAGE_RESOURCE_LOCATOR) {
            return;
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
        if (!this.mode) {
            return null;
        }

        const {
            fieldTypeOptions: {
                historyResourceKey,
                options = {},
            },
        } = this.props;

        if (!historyResourceKey || typeof historyResourceKey !== 'string') {
            throw new Error('The "historyResourceKey" field type option must be set to a string!');
        }

        if (typeof options !== 'object') {
            throw new Error('The "options" field type must be an object if given!');
        }

        const {
            dataPath,
            disabled,
            formInspector,
            onChange,
            value,
        } = this.props;

        if (value === HOMEPAGE_RESOURCE_LOCATOR) {
            return '/';
        }

        return (
            <div className={resourceLocatorStyles.resourceLocatorContainer}>
                <div className={resourceLocatorStyles.resourceLocator}>
                    <ResourceLocatorComponent
                        disabled={!!disabled}
                        id={dataPath}
                        mode={this.mode}
                        onBlur={this.handleBlur}
                        onChange={onChange}
                        value={value}
                    />
                </div>
                {formInspector.id &&
                    <div className={resourceLocatorStyles.resourceLocatorHistory}>
                        <ResourceLocatorHistory
                            id={formInspector.id}
                            options={{
                                locale: formInspector.locale,
                                resourceKey: formInspector.resourceKey,
                                webspace: formInspector.options.webspace,
                                ...options,
                            }}
                            resourceKey={historyResourceKey}
                        />
                    </div>
                }
            </div>
        );
    }
}

export default ResourceLocator;
