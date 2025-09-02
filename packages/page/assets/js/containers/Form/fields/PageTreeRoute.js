// @flow
import React, {Component, Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {userStore} from 'sulu-admin-bundle/stores';
import {Grid} from 'sulu-admin-bundle/components';
import {SingleSelection} from 'sulu-admin-bundle/containers';
import {ResourceLocator} from 'sulu-route-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {PageTreeRouteValue} from '../../types.js';

type Props = FieldTypeProps<?PageTreeRouteValue>;

@observer
class PageTreeRoute extends Component<Props> {
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

    get pageValue(): ?string {
        const {value} = this.props;

        if (value && value.page && value.page.uuid) {
            return value.page.uuid;
        }

        return null;
    }

    get suffixValue(): ?string {
        const {value} = this.props;

        if (value && value.suffix) {
            return value.suffix;
        }

        return null;
    }

    handlePageChange = (value: ?string | number, page: ?Object = {
        path: null,
    }): void => {
        const {onFinish} = this.props;

        const uuid = (value && value.toString()) || undefined;
        const path = (page && page.url) || undefined;

        this.handleChange({
            ...this.props.value,
            page: {
                uuid,
                path,
            },
        });

        onFinish();
    };

    handleSuffixChange = (value: ?string): void => {
        this.handleChange({
            ...this.props.value,
            suffix: value,
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
            data,
            dataPath,
            defaultType,
            disabled,
            fieldTypeOptions,
            formInspector,
            onFinish,
            onSuccess,
            router,
            schemaOptions,
            schemaPath,
            types,
        } = this.props;

        return (
            <Fragment>
                <Grid>
                    <Grid.Item colSpan={5}>
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

                    <Grid.Item colSpan={7}>
                        <ResourceLocator
                            data={data}
                            dataPath={dataPath}
                            defaultType={defaultType}
                            disabled={disabled}
                            error={undefined}
                            fieldTypeOptions={{
                                historyResourceKey: 'routes',
                                options: {
                                    history: true,
                                },
                                ...fieldTypeOptions,
                            }}
                            formInspector={formInspector}
                            label={undefined}
                            maxOccurs={1}
                            minOccurs={1}
                            onChange={this.handleSuffixChange}
                            onFinish={onFinish}
                            onSuccess={onSuccess}
                            router={router}
                            schemaOptions={schemaOptions}
                            schemaPath={schemaPath}
                            showAllErrors={false}
                            types={types}
                            value={this.suffixValue}
                        />
                    </Grid.Item>
                </Grid>
            </Fragment>
        );
    }
}

export default PageTreeRoute;
