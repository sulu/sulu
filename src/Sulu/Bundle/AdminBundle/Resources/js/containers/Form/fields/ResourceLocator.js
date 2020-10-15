// @flow
import React from 'react';
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../containers/ResourceLocatorHistory';
import Requester from '../../../services/Requester';
import type {FieldTypeProps} from '../../../types';
import {translate} from '../../../utils/Translator';
import Button from '../../../components/Button';
import resourceLocatorStyles from './resourceLocator.scss';

const PART_TAG = 'sulu.rlp.part';

const HOMEPAGE_RESOURCE_LOCATOR = '/';

@observer
class ResourceLocator extends React.Component<FieldTypeProps<?string>> {
    @observable mode: string;
    @observable enableRefreshButton: boolean = false;

    constructor(props: FieldTypeProps<?string>) {
        super(props);

        const {
            dataPath,
            fieldTypeOptions: {
                generationUrl,
                modeResolver,
            },
            formInspector,
            value: initialValue,
        } = this.props;

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

        if (initialValue === HOMEPAGE_RESOURCE_LOCATOR) {
            return;
        }

        formInspector.addFinishFieldHandler(action((finishedFieldDataPath, finishedFieldSchemaPath) => {
            const {tags: finishedFieldTags} = formInspector.getSchemaEntryByPath(finishedFieldSchemaPath) || {};
            if (!finishedFieldTags || !finishedFieldTags.some((tag) => tag.name === PART_TAG)) {
                return;
            }

            if (initialValue === undefined && !formInspector.isFieldModified(dataPath)) {
                this.refreshResourceLocator();
            } else {
                this.enableRefreshButton = true;
            }
        }));
    }

    refreshResourceLocator = () => {
        const {
            onChange,
            fieldTypeOptions: {
                generationUrl,
                resourceStorePropertiesToRequest = {},
            },
            formInspector,
        } = this.props;

        const partEntries = formInspector.getPathsByTag(PART_TAG)
            .map((path: string) => [path, formInspector.getValueByPath(path)])
            .filter(([, value: mixed]) => !!value)
            .map(([path: string, value: mixed]) => {
                // path is a jsonpointer but the api controller requires property names
                if (path.startsWith('/')) {
                    return [path.substr(1), value];
                }

                return [path, value];
            });

        if (partEntries.length === 0) {
            return;
        }

        const requestOptions = {...formInspector.options};

        Object.entries(resourceStorePropertiesToRequest).forEach(([propertyName, parameterName]) => {
            const propertyValue = toJS(formInspector.getValueByPath('/' + propertyName));
            if (propertyValue !== undefined) {
                requestOptions[parameterName] = propertyValue;
            }
        });

        Requester.post(
            generationUrl,
            {
                parts: Object.fromEntries(partEntries),
                resourceKey: formInspector.resourceKey,
                locale: formInspector.locale ? formInspector.locale.get() : undefined,
                ...requestOptions,
            }
        ).then(action((response) => {
            this.enableRefreshButton = false;
            onChange(response.resourcelocator);
        }));
    };

    handleBlur = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    handleRefreshButtonClick = () => {
        this.refreshResourceLocator();
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
            <div>
                <ResourceLocatorComponent
                    disabled={!!disabled}
                    id={dataPath}
                    mode={this.mode}
                    onBlur={this.handleBlur}
                    onChange={onChange}
                    value={value}
                />
                <div className={resourceLocatorStyles.buttonContainer}>
                    <Button
                        className={resourceLocatorStyles.refreshButton}
                        disabled={!this.enableRefreshButton}
                        icon="su-sync"
                        onClick={this.handleRefreshButtonClick}
                        skin="link"
                    >
                        {translate('sulu_admin.refresh_url')}
                    </Button>
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
            </div>
        );
    }
}

export default ResourceLocator;
