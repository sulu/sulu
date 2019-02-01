// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import log from 'loglevel';
import Loader from '../../components/Loader';
import Renderer from './Renderer';
import type {FormStoreInterface} from './types';
import FormInspector from './FormInspector';
import GhostDialog from './GhostDialog';

type Props = {
    onError?: (errors: Object) => void,
    onSubmit: (action: ?string) => ?Promise<Object>,
    store: FormStoreInterface,
};

@observer
export default class Form extends React.Component<Props> {
    @observable showAllErrors = false;
    @observable displayGhostDialog = false;
    displayGhostDialogDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.displayGhostDialogDisposer = autorun(() => {
            const {store} = this.props;
            const {
                data: {
                    availableLocales,
                },
                loading,
                locale,
            } = store;

            if (loading) {
                this.hideGhostDialog();
                return;
            }

            if (availableLocales && locale && !availableLocales.includes(locale.get())) {
                this.showGhostDialog();
            }
        });
    }

    componentWillUnmount() {
        this.displayGhostDialogDisposer();
    }

    @computed get formInspector(): FormInspector {
        return new FormInspector(this.props.store);
    }

    /** @public */
    @action submit = (action: ?string) => {
        const {onError, onSubmit, store} = this.props;

        this.showAllErrors = true;

        if (store.validate()) {
            return onSubmit(action);
        }

        if (onError) {
            return onError(store.errors);
        }
    };

    handleChange = (name: string, value: mixed) => {
        this.props.store.change(name, value);
    };

    @action showGhostDialog() {
        this.displayGhostDialog = true;
    }

    @action hideGhostDialog() {
        this.displayGhostDialog = false;
    }

    @action handleGhostDialogCancel = () => {
        this.hideGhostDialog();
    };

    @action handleGhostDialogConfirm = (locale: string) => {
        const {store} = this.props;

        if (!store.copyFromLocale) {
            return;
        }

        store.copyFromLocale(locale);
        this.hideGhostDialog();
    };

    handleFieldFinish = (dataPath: string, schemaPath: string) => {
        log.debug(
            'Finished editing field with dataPath "' + dataPath + '" and schemaPath "' + schemaPath + '"',
            toJS(this.formInspector.getValueByPath(dataPath))
        );
        const {store} = this.props;

        store.validate();
        this.formInspector.finishField(dataPath, schemaPath);
    };

    render() {
        const {store} = this.props;
        const {
            data: {
                availableLocales,
            },
        } = store;

        return store.loading
            ? <Loader />
            : (
                <Fragment>
                    {store.id && availableLocales &&
                        <GhostDialog
                            locales={availableLocales}
                            onCancel={this.handleGhostDialogCancel}
                            onConfirm={this.handleGhostDialogConfirm}
                            open={this.displayGhostDialog}
                        />
                    }
                    <Renderer
                        data={store.data}
                        dataPath=""
                        errors={store.errors}
                        formInspector={this.formInspector}
                        onChange={this.handleChange}
                        onFieldFinish={this.handleFieldFinish}
                        schema={store.schema}
                        schemaPath=""
                        showAllErrors={this.showAllErrors}
                    />
                </Fragment>
            );
    }
}
