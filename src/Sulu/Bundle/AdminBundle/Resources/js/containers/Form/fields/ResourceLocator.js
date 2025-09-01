// @flow
import React, {Fragment} from 'react';
import {action, comparer, computed, observable, reaction, toJS} from 'mobx';
import {observer} from 'mobx-react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../containers/ResourceLocatorHistory';
import Requester from '../../../services/Requester';
import {translate} from '../../../utils/Translator';
import Button from '../../../components/Button';
import userStore from '../../../stores/userStore';
import resourceLocatorStyles from './resourceLocator.scss';
import type {FieldTypeProps} from '../../../types';

const PART_TAG = 'sulu.rlp.part';

const HOMEPAGE_RESOURCE_LOCATOR = '/';

@observer
class ResourceLocator extends React.Component<FieldTypeProps<?string>> {
    @observable mode: string;
    @observable inputChanged: boolean = false;
    @observable inputChangedSinceRefresh: boolean = false;
    @observable partsChangedSinceRefresh: boolean = false;

    partsChangeDisposer: ?() => mixed;

    @computed get parts(): {[string]: mixed} {
        const {
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

        return Object.fromEntries(partEntries);
    }

    @computed get enableAutoGeneration(): boolean {
        const {
            formInspector: {
                id,
            },
        } = this.props;

        return !id && !this.inputChanged && Object.keys(this.parts).length > 0;
    }

    @computed get enableRefreshButton(): boolean {
        if (this.enableAutoGeneration) {
            return false;
        }

        return (this.inputChangedSinceRefresh || this.partsChangedSinceRefresh) && Object.keys(this.parts).length > 0;
    }

    constructor(props: FieldTypeProps<?string>) {
        super(props);

        const {
            fieldTypeOptions: {
                generationUrl,
                modeResolver,
            },
            formInspector,
            value,
        } = this.props;

        if (!modeResolver) {
            throw new Error('The "modeResolver" must be a function returning a promise with the desired mode');
        }

        modeResolver(this.props).then(action((mode) => this.mode = mode));

        if (value === HOMEPAGE_RESOURCE_LOCATOR) {
            return;
        }

        if (!generationUrl) {
            return;
        }

        if (typeof generationUrl !== 'string') {
            throw new Error('The "generationUrl" fieldTypeOption must be a string!');
        }

        this.partsChangeDisposer = reaction(
            () => (this.parts),
            action(() => {
                this.partsChangedSinceRefresh = true;
            }),
            {equals: comparer.structural}
        );

        formInspector.addFinishFieldHandler(action((finishedFieldDataPath, finishedFieldSchemaPath) => {
            const {tags: finishedFieldTags} = formInspector.getSchemaEntryByPath(finishedFieldSchemaPath) || {};
            if (!finishedFieldTags || !finishedFieldTags.some((tag) => tag.name === PART_TAG)) {
                return;
            }

            if (this.enableAutoGeneration) {
                this.refreshResourceLocator();
            }
        }));
    }

    componentWillUnmount() {
        if (this.partsChangeDisposer) {
            this.partsChangeDisposer();
        }
    }

    @action refreshResourceLocator = () => {
        const {
            fieldTypeOptions: {
                generationUrl,
                resourceStorePropertiesToRequest = {},
            },
            formInspector,
            onChange,
            schemaOptions: {
                route_schema: {
                    value: routeSchema,
                } = {},
            } = {},
        } = this.props;

        const requestOptions = {...formInspector.options};

        Object.entries(resourceStorePropertiesToRequest).forEach(([propertyName, parameterName]) => {
            const propertyValue = toJS(formInspector.getValueByPath('/' + propertyName));
            if (propertyValue !== undefined) {
                requestOptions[parameterName] = propertyValue;
            }
        });

        this.inputChangedSinceRefresh = false;
        this.partsChangedSinceRefresh = false;

        Requester.post(
            generationUrl,
            {
                parts: this.parts,
                resourceKey: formInspector.resourceKey,
                locale: formInspector.locale ? formInspector.locale.get() : userStore.contentLocale,
                id: formInspector.id,
                routeSchema,
                ...requestOptions,
            }
        ).then(action((response) => {
            onChange(response.resourcelocator);
        }));
    };

    handleInputBlur = () => {
        const {onFinish} = this.props;
        onFinish();
    };

    @action handleInputChange = (value: ?string) => {
        const {onChange} = this.props;

        this.inputChanged = true;
        this.inputChangedSinceRefresh = true;

        onChange(value);
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
            value,
        } = this.props;

        if (value === HOMEPAGE_RESOURCE_LOCATOR) {
            return '/';
        }

        return (
            <Fragment>
                <ResourceLocatorComponent
                    disabled={!!disabled}
                    id={dataPath}
                    locale={formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale)}
                    mode={this.mode}
                    onBlur={this.handleInputBlur}
                    onChange={this.handleInputChange}
                    value={value}
                />
                <div className={resourceLocatorStyles.buttonsContainer}>
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
                            locale: formInspector.locale ? formInspector.locale.get() : userStore.contentLocale,
                            resourceKey: formInspector.resourceKey,
                            webspace: formInspector.options.webspace,
                            ...options,
                        }}
                        resourceKey={historyResourceKey}
                    />
                </div>
            </Fragment>
        );
    }
}

export default ResourceLocator;
