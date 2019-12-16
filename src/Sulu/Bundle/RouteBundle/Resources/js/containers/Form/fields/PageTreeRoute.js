// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/containers/Form/types';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {Grid} from 'sulu-admin-bundle/components';
import SingleSelection from 'sulu-admin-bundle/containers/SingleSelection';
import ResourceLocator from 'sulu-admin-bundle/components/ResourceLocator';
import ResourceLocatorHistory from 'sulu-admin-bundle/containers/ResourceLocatorHistory';
import resourceLocatorStyles from 'sulu-admin-bundle/containers/Form/fields/resourceLocator.scss';
import {translate} from 'sulu-admin-bundle/utils';
import type {PageTreeRouteValue} from '../../types.js';

type Props = FieldTypeProps<?PageTreeRouteValue>;

@observer
class PageTreeRoute extends React.Component<Props> {
    @observable mode: string;

    constructor(props: Props): void {
        super(props);

        const {
            fieldTypeOptions: {
                modeResolver,
            },
        } = props;

        if (!modeResolver) {
            throw new Error('The "modeResolver" must be a function returning a promise with the desired mode');
        }

        modeResolver(props).then(action((mode) => this.mode = mode));
    }

    get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    get showHistory(): boolean {
        const {
            formInspector: {
                id,
            },
            fieldTypeOptions: {
                historyResourceKey,
                options: {
                    history,
                },
            },
        } = this.props;

        return !!history && !!id && !!historyResourceKey;
    }

    get pageValue(): ?string {
        const {
            value: {
                page: {
                    uuid,
                } = {
                    uuid: null,
                },
            } = {
                page: {
                    uuid: null,
                },
            },
        } = this.props;

        return uuid;
    }

    get suffixValue(): ?string {
        const {
            value: {
                suffix,
            } = {
                suffix: null,
            },
        } = this.props;

        return suffix;
    }

    handlePageChange = (value: ?string, page: ?Object = {
        path: null,
    }): void => {
        this.handleChange({
            ...this.props.value,
            page: {
                uuid: value,
                path: page.path,
            },
        });
    };

    handleSuffixChange = (value: ?string): void => {
        this.handleChange({
            ...this.props.value, suffix: value,
        });
    };

    handleChange = (value: ?PageTreeRouteValue) => {
        const {onChange} = this.props;

        onChange(value);
    };

    render() {
        if (!this.mode) {
            return null;
        }

        const {
            dataPath,
            disabled,
            formInspector: {
                id,
                resourceKey,
            },
            fieldTypeOptions: {
                historyResourceKey,
                options,
            },
        } = this.props;

        return (
            <>
                <Grid>
                    <Grid.Item colSpan={this.showHistory ? 5 : 6}>
                        <SingleSelection
                            adapter="column_list"
                            disabled={!!disabled}
                            displayProperties={['url']}
                            emptyText={translate('sulu_page.no_page_selected')}
                            icon="su-document"
                            listKey="pages"
                            locale={this.locale}
                            onChange={this.handlePageChange}
                            overlayTitle={translate('sulu_page.single_selection_overlay_title')}
                            resourceKey="pages"
                            value={this.pageValue}
                        />
                    </Grid.Item>

                    <Grid.Item colSpan={this.showHistory ? 7 : 6}>
                        <div className={resourceLocatorStyles.resourceLocatorContainer}>
                            <div className={resourceLocatorStyles.resourceLocator}>
                                <ResourceLocator
                                    disabled={!!disabled}
                                    id={dataPath}
                                    mode={this.mode}
                                    onChange={this.handleSuffixChange}
                                    value={this.suffixValue}
                                />
                            </div>

                            {this.showHistory &&
                                <div className={resourceLocatorStyles.resourceLocatorHistory}>
                                    <ResourceLocatorHistory
                                        id={id}
                                        options={{
                                            locale: this.locale,
                                            resourceKey: resourceKey,
                                            ...options,
                                        }}
                                        resourceKey={historyResourceKey}
                                    />
                                </div>
                            }
                        </div>
                    </Grid.Item>
                </Grid>
            </>
        );
    }
}

export default PageTreeRoute;
